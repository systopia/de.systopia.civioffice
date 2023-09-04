<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: J. Franz (franz@systopia.de)                   |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

use CRM_Civioffice_ExtensionUtil as E;
use PhpOffice\PhpWord;

/**
 *
 */
class CRM_Civioffice_DocumentRendererType_LocalUnoconv extends CRM_Civioffice_DocumentRendererType
{
    const MIN_UNOCONV_VERSION = '0.7'; // todo: determine

    const UNOCONV_BINARY_PATH_SETTINGS_KEY = 'unoconv_binary_path';
    const UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY = 'unoconv_lock_file_path';
    const PREPARE_DOCX_SETTINGS_KEY = 'prepare_docx';
    const PHPWORD_TOKENS_SETTINGS_KEY = 'phpword_tokens';

    /**
     * @var string $unoconv_binary_path
     *   The path to the unoconv binary.
     */
    protected $unoconv_binary_path;

    /**
     * @var string $unoconv_lock_file_path
     *   Path of file used for the lock.
     */
    protected $unoconv_lock_file_path;

    /**
     * @var resource $unoconv_lock_file
     *   File resource handle used for the lock.
     */
    protected $unoconv_lock_file = null;

    /**
     * @var bool $prepare_docx
     *   Whether to "prepare" DOCX files, i.e. try to repair common formatting mistakes.
     */
    protected $prepare_docx;

    /**
     * @var bool $phpword_tokens
     *   Whether to replace Live Snippet tokens using a PHPWord template processor, so that HTML in Live Snippets can be
     *   converted to OOXML.
     */
    protected $phpword_tokens;

    public function __construct($uri = null, $name = null, array &$configuration = [])
    {
        parent::__construct(
            $uri ?? 'unoconv-local',
            $name ?? E::ts('Local Universal Office Converter (unoconv)'),
            $configuration
        );
    }

    /**
     * Is this renderer currently available?
     * Tests if the binary is there and responding
     *
     * @return boolean
     *   Whether this renderer is ready for use.
     */
    public function isReady(): bool
    {
        try {
            if (empty($this->unoconv_binary_path)) {
                // no unoconv binary or wrapper script provided
                Civi::log()->debug("CiviOffice: Path to unoconv binary / wrapper script is missing");
                return false;
            }

            // get webserver user home path
            $home_folder = CRM_Civioffice_Configuration::getHomeFolder() . DIRECTORY_SEPARATOR;

            // check if ~/.cache folder exists, try to create if not
            if (!file_exists("{$home_folder}.cache")) {
                mkdir("{$home_folder}.cache");
            }
            if (!is_writable("{$home_folder}.cache")) {
                Civi::log()->debug("CiviOffice: Unoconv folder needs to be writable: {home}/.cache");
                return false;
            }

            // check if ~/.config folder exists, try to create if not
            if (!file_exists("{$home_folder}.config")) {
                mkdir("{$home_folder}.config");
            }
            if (!is_writable("{$home_folder}.config")) {
                Civi::log()->debug("CiviOffice: Unoconv folder needs to be writable: {home}/.config");
                return false;
            }

            // run a probe command
            $probe_command = "{$this->unoconv_binary_path} --version 2>&1";
            [$result_code, $output] = $this->runCommand($probe_command);

            if (!empty($result_code) && $result_code != 255) {
                Civi::log()->debug("CiviOffice: Error code {$result_code} received from unoconv. Output was: " . json_encode($output));
                return false;
            }

            $found = preg_grep('/^unoconv (\d+)\.(\d+)/i', $output);
            if (empty($found)) {
                Civi::log()->debug("CiviOffice: unoconv version number not found");
                return false;
            }
        } catch (Exception $ex) {
            Civi::log()->debug("CiviOffice: Unoconv generic exception in isReady() check");
            return false;
        }
        return true;
    }

    /**
     * Get the output/generated MIME types for this document renderer
     *
     * @return array
     *   list of MIME types
     */
    public function getSupportedOutputMimeTypes(): array
    {
        return [
            CRM_Civioffice_MimeType::PDF,
            CRM_Civioffice_MimeType::DOCX
        ];
    }

    /**
     * Get a list of document MIME types supported by this component
     *
     * @return array
     *   list of MIME types as strings
     */
    public function getSupportedMimeTypes(): array // FIXME: Input or output MIME types?
    {
        return [CRM_Civioffice_MimeType::DOCX];
    }

    /**
     * {@inheritDoc}
     */
    public static function getSettingsFormTemplate() {
        return 'CRM/Civioffice/Form/DocumentRenderer/Settings/LocalUnoconv.tpl';
    }

    /**
     * {@inheritDoc}
     */
    public function buildSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form)
    {
        $form->add(
            'text',
            'unoconv_binary_path',
            E::ts("Path to the <code>unoconv</code> executable"),
            ['class' => 'huge'],
            true
        );

        $form->add(
            'text',
            'unoconv_lock_file_path',
            E::ts("Path to a lock file"),
            ['class' => 'huge'],
            false
        );

        $form->add(
            'checkbox',
            'prepare_docx',
            E::ts('Prepare DOCX documents'),
            null,
            false
        );

        $form->add(
            'checkbox',
            'phpword_tokens',
            E::ts('Use PHPWord macros for Live Snippet tokens'),
            null,
            false
        );

        $form->setDefaults(
            [
                'unoconv_binary_path' => $this->unoconv_binary_path,
                'unoconv_lock_file_path' => $this->unoconv_lock_file_path,
                'prepare_docx' => $this->prepare_docx,
                'phpword_tokens' => $this->phpword_tokens,
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function validateSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form) {
        $unoconv_binary_path = $form->_submitValues['unoconv_binary_path'];
        $unoconv_lock_file_path = $form->_submitValues['unoconv_lock_file_path'];

        if (!file_exists($unoconv_binary_path)) {
            $form->_errors['unoconv_binary_path'] = E::ts("File does not exist. Please provide a correct filename.");
        }

        if (!empty($lockfile_to_check)) {
            if (!file_exists($unoconv_lock_file_path)) {
              if (!touch($unoconv_lock_file_path)) {
                $form->_errors['unoconv_lock_file_path'] = E::ts('Cannot create lock file');
              }
            } else if (!is_file($unoconv_lock_file_path)) {
              $form->_errors['unoconv_lock_file_path'] = E::ts('This is not a file');
            } else if (!is_writable($unoconv_lock_file_path)) {
                $form->_errors['unoconv_lock_file_path'] = E::ts(
                    'Lock file cannot be written. Please run: "chmod 777 %1"', [1 => $unoconv_lock_file_path]);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function postProcessSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form) {
        $values = $form->exportValues();

        $renderer = $form->getDocumentRenderer();
        $renderer->setConfigItem(
            CRM_Civioffice_DocumentRendererType_LocalUnoconv::UNOCONV_BINARY_PATH_SETTINGS_KEY,
            $values['unoconv_binary_path']
        );
        $renderer->setConfigItem(
            CRM_Civioffice_DocumentRendererType_LocalUnoconv::UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY,
            $values['unoconv_lock_file_path']
        );
        $renderer->setConfigItem(
            CRM_Civioffice_DocumentRendererType_LocalUnoconv::PREPARE_DOCX_SETTINGS_KEY,
            $values['prepare_docx']
        );
        $renderer->setConfigItem(
            CRM_Civioffice_DocumentRendererType_LocalUnoconv::PHPWORD_TOKENS_SETTINGS_KEY,
            $values['phpword_tokens']
        );
    }

    /**
     * {@inheritDoc}
     */
    public function render(
        $document_with_placeholders,
        array $entity_ids,
        CRM_Civioffice_DocumentStore_LocalTemp $temp_store,
        string $target_mime_type,
        string $entity_type = 'contact',
        array $live_snippets = []
    ): array {
        $this->liveSnippets = $live_snippets;
        // for now DOCX is the only format being used for internal processing
        $internal_processing_format = CRM_Civioffice_MimeType::DOCX; // todo later on this can be determined by checking the $document_with_placeholders later on to allow different transition formats like .odt/.odf
        $needs_conversion = $target_mime_type != $internal_processing_format;

        // "Convert" DOCX files to DOCX in order to "repair" stuff, e.g. tokens that might have got split in the OOXML
        // due to spell checking or formatting.
        if ($this->prepare_docx && $internal_processing_format == CRM_Civioffice_MimeType::DOCX) {
            $lock = new CRM_Core_Lock('civicrm.office.civi_office_unoconv_local', 60, true);
            if (!$lock->acquire()) {
                throw new Exception(E::ts("Too many parallel conversions. Try using a smaller batch size."));
            }

            $temp_store_folder_path = $temp_store->getBaseFolder();
            $local_temp_store = new CRM_Civioffice_DocumentStore_LocalTemp($temp_store_folder_path);
            $prepared_document = $local_temp_store->getLocalCopyOfDocument(
                $document_with_placeholders,
                $document_with_placeholders->getName()
            );

            // Rename source DOCX files to *.docxsource for avoiding unoconv storage errors.
            exec(
                "cd $temp_store_folder_path"
                . '&& for f in *.docx; do mv -- "$f" "${f%.docx}.docxsource"; done'
            );

            $convert_command = "cd $temp_store_folder_path && {$this->unoconv_binary_path} -v -f docx *.docxsource 2>&1";
            [$exec_return_code, $exec_output] = $this->runCommand($convert_command);
            exec("cd $temp_store_folder_path && rm *.docxsource");

            if ($exec_return_code != 0) {
                // something's wrong - error handling:
                $serialize_output = serialize($exec_output);
                Civi::log()->debug(
                    "CiviOffice: Exception: Return code 0 expected but $exec_return_code given: $serialize_output"
                );

                $empty_files = '';
                foreach (new DirectoryIterator($temp_store_folder_path) as $file) {
                    if ($file->isFile() && $file->getSize() == 0) {
                        $empty_files .= $file->getFilename() . ', ';
                    }
                }
                Civi::log()->debug("CiviOffice: Files are empty: $empty_files");

                throw new Exception("Unoconv: Return code 0 expected but $exec_return_code given");
            }

            if ($lock) {
                $lock->release();
            }
        }

        // only lock render process if renderer is needed
        $lock = null;
        if ($needs_conversion) {
            // currently, this execution needs to be serialised (see https://github.com/systopia/de.systopia.civioffice/issues/6)
            $lock = new CRM_Core_Lock('civicrm.office.civi_office_unoconv_local', 60, true);
            if (!$lock->acquire()) {
                throw new Exception(E::ts("Too many parallel conversions. Try using a smaller batch size."));
            }
        }

        $tokenreplaced_documents = [];
        $temp_store_folder_path = $temp_store->getBaseFolder();
        $local_temp_store = new CRM_Civioffice_DocumentStore_LocalTemp($temp_store_folder_path);

        $file_ending_name = CRM_Civioffice_MimeType::mapMimeTypeToFileExtension($target_mime_type);

        /*
         * Token replacement
         *
         * example tokens:
         * Hello {contact.display_name} aka {contact.first_name}!
         *
         * TODO: Depending on the replacement method (PhpWord template processor or RegEx), detect all tokens used in
         *       the document once and prepare all token rows, before actually replacing them by iterating through
         *       entity IDs. This will save TokenProcessor evaluations per-entity, but requires loading the document
         *       once more per rendering process, i. e. increases performance for batch processing, but makes rendering
         *       a single document slower.
         */
        foreach ($entity_ids as $entity_id) {
            $new_file_name = $this->createDocumentName($entity_id, 'docx');
            $transitional_docx_document = $local_temp_store->getLocalCopyOfDocument(
                $prepared_document ?? $document_with_placeholders,
                $new_file_name
            );
            $this->replaceTokensSingle($transitional_docx_document, $entity_type, (int) $entity_id);
            $tokenreplaced_documents[] = $temp_store->addFile($this->createDocumentName($entity_id, $file_ending_name));
        }

        /*
         * Step 4/4
         * After batch size of xml (docx) files has been processed, we need to convert these files to pdf (using unoconv)
         * - Convert batch size amount of docx files
         * - Remove docx files
         */

        /*
         * unoconv manpage: https://linux.die.net/man/1/unoconv
         *
         * Command:
         * -f = format
         *      example: pdf
         * -o = output directory
         *      example: ./output_folder_for_pdf_files
         *
         * might be interesting if file gets instantly added to a zip file instead of writing and reading it again
         * --stdout = Print converted output file to stdout.
         * unoconv -f pdf -o ./output_folder_for_pdf_files FOLDER/PATH/TO/FILENAME/*.docx
         *      example: unoconv -f pdf --stdout FOLDER/PATH/TO/FILENAME.docx
         *
         * -v for verbose mode. Returns target file format and target filepath
         */

        if (isset($prepared_document)) {
            exec('rm "' . $prepared_document->getAbsolutePath() . '"');
        }

        if (!$needs_conversion) {
            // We can return here and skip conversion as the transition format is equal to the output format
            return $tokenreplaced_documents;
        }

        $convert_command = "cd $temp_store_folder_path && {$this->unoconv_binary_path} -v -f $file_ending_name *.docx 2>&1";
        [$exec_return_code, $exec_output] = $this->runCommand($convert_command);

        if ($exec_return_code != 0) {
            // something's wrong - error handling:
            $serialize_output = serialize($exec_output);
            Civi::log()->debug("CiviOffice: Exception: Return code 0 expected but $exec_return_code given: $serialize_output");

            $empty_files = '';
            foreach (new DirectoryIterator($temp_store_folder_path) as $file) {
                if ($file->isFile() && $file->getSize() == 0) {
                    $empty_files .= $file->getFilename() . ', ';
                }
            }
            Civi::log()->debug("CiviOffice: Files are empty: $empty_files");

            throw new Exception("Unoconv: Return code 0 expected but $exec_return_code given");
        }

        // TODO: Check errors with $exec_return_code
        // todo: better cleanup solution?
        exec("cd $temp_store_folder_path && rm *.docx");

        // release lock
        if ($lock) {
            $lock->release();
        }

        return $tokenreplaced_documents;
    }

    /**
     * {@inheritDoc}
     */
    public function replaceTokens(CRM_Civioffice_Document $document, string $entity_type, array $entity_ids): void
    {
        foreach ($entity_ids as $entity_id) {
            $this->replaceTokensSingle($document, $entity_type, $entity_id);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function replaceTokensSingle(CRM_Civioffice_Document $document, string $entity_type, int $entity_id): void {
        $token_row = $this->processTokenContext($entity_type, $entity_id);

        if ($this->phpword_tokens) {
            $this->replaceTokensPhpWord($document, $token_row);
        } else {
            $this->replaceTokensRegex($document, $token_row);
        }
    }

    protected function replaceTokensPhpWord(CRM_Civioffice_Document $document, \Civi\Token\TokenRow $token_row)
    {
        try {
            $templateProcessor = new CRM_Civioffice_DocumentRendererType_LocalUnoconv_PhpWordTemplateProcessor(
                $document->getAbsolutePath()
            );
        } catch (PhpWord\Exception\Exception $exception) {
            throw new Exception("Unoconv: Docx (zip) file seems to be broken or path is wrong");
        }

        $used_tokens = $templateProcessor->civiTokensToMacros();

        // Register all tokens as token messages and evaluate.
        foreach ($used_tokens as $token => $token_params) {
            $this->tokenProcessor->addMessage($token, $token, 'text/html');
        }
        $this->tokenProcessor->evaluate();

        // Replace contained tokens.
        $used_macro_variables = $templateProcessor->getVariables();
        foreach ($used_macro_variables as $macro_variable) {
            // Format each variable as a CiviCRM token and render it.
            $rendered_token_message = $this->tokenProcessor->render('{' . $macro_variable . '}', $token_row);
            $templateProcessor->replaceHtmlToken($macro_variable, $rendered_token_message);
        }
        $templateProcessor->saveAs($document->getAbsolutePath());
    }

    protected function replaceTokensRegex(CRM_Civioffice_Document $document, \Civi\Token\TokenRow $token_row)
    {
        // Replace tokens manually.
        $zip = new ZipArchive();

        // open xml file (like .docx) as a zip file, as in fact it is one
        $zip->open($document->getAbsolutePath());
        $numberOfFiles = $zip->numFiles;
        if (empty($numberOfFiles)) {
            throw new Exception("Unoconv: Docx (zip) file seems to be broken or path is wrong");
        }

        // iterate through all docx components (files in zip)
        for ($i = 0; $i < $numberOfFiles; $i++) {
            // Step 1/4 unpack xml (.docx) file and handle it as a zip file as it is one
            $fileContent = $zip->getFromIndex($i);
            $fileName = $zip->getNameIndex($i);

            // Step 2/4 replace tokens
            /**
             * TODO: Skip irrelevant parts, like binary files (images, etc.).
             * @url https://github.com/systopia/de.systopia.civioffice/issues/13
             *   As a first step, we filter for XML files only.
             */
            if (0 === substr_compare($fileName, '.xml', -strlen('.xml'))) {
                $fileContent = $this->wrapTokensInStringWithXmlEscapeCdata($fileContent);
                $this->tokenProcessor->addMessage('document', $fileContent, 'text/plain');
                $this->tokenProcessor->evaluate();
                $fileContent = $token_row->render('document');
            }

            // Step 3/4 repack it again as xml (docx)
            $zip->addFromString($fileName, $fileContent);
        }

        $zip->close();
    }

    /**
     * Takes a string with one or many {domain.context} style tokens and wraps a CDATA block around it to
     * not break xml files by using illegal symbols like: ' () & , " <>
     * Input example:  Welcome {contact.display_name} aka {contact.first_name}. Great to have you!
     * Output example: Welcome <![CDATA[{contact.display_name}]]> aka <![CDATA[{contact.first_name}]]>. Great to have you!
     * @param $string
     *
     * @return string
     *   Returns the whole string with escaped tokens
     */
    protected function wrapTokensInStringWithXmlEscapeCdata($string): string
    {
        return preg_replace('/{([\w.]+)}/', '<![CDATA[$0]]>', $string);
    }

    /**
     * Get the URL to configure this component
     *
     * @return string
     *   URL
     */
    public function getConfigPageURL(): string
    {
        return CRM_Utils_System::url('civicrm/admin/civioffice/settings/localunoconv');
    }

    /**
     * Get the (localised) component description
     *
     * @return string
     *   name
     */
    public function getDescription(): string
    {
        return E::ts("This document renderer employs the <code>unoconv</code> script on your server to convert documents using LibreOffice.");
    }


    /**
     * @param $entity_id
     * @param string $file_ending_name
     *
     * @return string
     */
    protected function createDocumentName($entity_id, string $file_ending_name): string
    {
        return "Document-{$entity_id}.{$file_ending_name}";
    }

    /**
     * Run unoconv in the current configuration with the given command
     *
     * @param string $command
     *   the command to run
     *
     * @return array
     *   [return code, output lines]
     */
    protected function runCommand($command)
    {
        // use the lock if this is set up
        $this->lock();

        try {
            // make sure the unoconv path is in the environment
            //  see https://stackoverflow.com/a/43083964
            $our_path = dirname($this->unoconv_binary_path);
            $paths = explode(PATH_SEPARATOR, getenv('PATH'));
            if (!in_array($our_path, $paths)) {
                $paths[] = $our_path;
            }

            // finally: execute
            putenv('PATH=' . implode(PATH_SEPARATOR, $paths));
            exec($command, $exec_output, $exec_return_code);

            // exec code 255 seems to be o.k. as well...
            if ($exec_return_code == 255) {
                $exec_return_code = 0;
            }
        } catch (Exception $ex) {
            Civi::log()->debug("CiviOffice: Got execution exception: ". $ex->getMessage());
        }
        $this->unlock();

        return [$exec_return_code, $exec_output];
    }

    /**
     * wait for the unoconv resource to become available
     */
    protected function lock()
    {
        $lock_file_path = $this->unoconv_lock_file_path;
        if ($lock_file_path) {
            $this->unoconv_lock_file = fopen($lock_file_path, "r+");
            if (!flock($this->unoconv_lock_file, LOCK_EX)) {
                throw new Exception(E::ts("CiviOffice: Could not acquire unoconv lock."));
            }
        }
    }

    /**
     * wait for the unoconv resource to become available
     */
    protected function unlock()
    {
        if ($this->unoconv_lock_file) {
            if (!flock($this->unoconv_lock_file, LOCK_UN)) {
                Civi::log()->debug("CiviOffice: Could not release unoconv lock.");
            }
            fclose($this->unoconv_lock_file);
            $this->unoconv_lock_file = null;
        }
    }

    public static function supportedConfiguration(): array
    {
        return [
            self::UNOCONV_BINARY_PATH_SETTINGS_KEY,
            self::UNOCONV_LOCK_FILE_PATH_SETTINGS_KEY,
            self::PREPARE_DOCX_SETTINGS_KEY,
            self::PHPWORD_TOKENS_SETTINGS_KEY,
        ];
    }

    public static function defaultConfiguration(): array
    {
        return [
            'unoconv_binary_path' => '/usr/bin/unoconv',
            'unoconv_lock_file_path' => CRM_Civioffice_Configuration::getHomeFolder() . '/unoconv.lock',
            'prepare_docx' => false,
            'phpword_tokens' => false,
        ];
    }
}