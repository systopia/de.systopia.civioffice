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
use Civi\Api4;

/**
 *
 */
class CRM_Civioffice_DocumentRenderer_LocalUnoconv extends CRM_Civioffice_DocumentRenderer
{
    const MIN_UNOCONV_VERSION = '0.7'; // todo: determine

    const UNOCONV_BINARY_PATH_SETTINGS_KEY = 'civioffice_unoconv_binary_path';
    const UNOCONV_LOCK_PATH_SETTINGS_KEY = 'civioffice_unoconv_lock_file';
    const TEMP_FOLDER_PATH_SETTINGS_KEY = 'civioffice_temp_folder_path';

    /** @var string path to the unoconv binary */
    protected $unoconv_path;

    /** @var resource handle used for the lock */
    protected $lock_file;

    /**
     * constructor
     *
     */
    public function __construct()
    {
        parent::__construct('unoconv-local', E::ts("Local Universal Office Converter (unoconv)"));
        $this->unoconv_path = Civi::settings()->get(self::UNOCONV_BINARY_PATH_SETTINGS_KEY);
        if (empty($this->unoconv_path)) {
            Civi::log()->debug("CiviOffice: Path to unoconv binary / wrapper script is missing");
            $this->unoconv_path = "";
        }
    }

    /**
     * Is this renderer currently available?
     * Tests if the binary is there and responding
     *
     * @return boolean
     *   is this renderer ready for use
     */
    public function isReady(): bool
    {
        try {
            if (empty($this->unoconv_path)) {
                // no unoconv binary or wrapper script provided
                return false;
            }

            $temp_folder = Civi::settings()->get(self::TEMP_FOLDER_PATH_SETTINGS_KEY);

            // fixme duplicated check in CRM_Civioffice_Form_DocumentRenderer_LocalUnoconvSettings->isReady() ?
            if (!is_writable($temp_folder)) {
                Civi::log()->debug("CiviOffice: Unable to create unoconv temp dir in: $temp_folder");
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
            $probe_command = "{$this->unoconv_path} --version 2>&1";
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
     * Get the output/generated mime types for this document renderer
     *
     * @return array
     *   list of mime types
     */
    public function getSupportedOutputMimeTypes(): array
    {
        return [
            CRM_Civioffice_MimeType::PDF,
            CRM_Civioffice_MimeType::DOCX
        ];
    }

    /**
     * Get a list of document mime types supported by this component
     *
     * @return array
     *   list of mime types as strings
     */
    public function getSupportedMimeTypes(): array // FIXME: Input or output mime types?
    {
        return [CRM_Civioffice_MimeType::DOCX];
    }

    /**
     * Render a document for a list of entities
     *
     * @param CRM_Civioffice_Document $document_with_placeholders
     *   the document to be rendered
     *
     * @param array $entity_ids
     *   entity ID, e.g. contact ids
     * @param \CRM_Civioffice_DocumentStore_LocalTemp $temp_store
     * @param string $target_mime_type
     * @param string $entity_type
     *   entity type, e.g. 'contact'
     *
     * @return array
     *   list of documents with target file name
     * @throws \Exception
     */
    public function render(
        $document_with_placeholders,
        array $entity_ids,
        CRM_Civioffice_DocumentStore_LocalTemp $temp_store,
        string $target_mime_type,
        string $entity_type = 'contact',
        array $live_snippets = [],
        bool $prepare_docx = false
    ): array {
        // for now DOCX is the only format being used for internal processing
        $internal_processing_format = CRM_Civioffice_MimeType::DOCX; // todo later on this can be determined by checking the $document_with_placeholders later on to allow different transition formats like .odt/.odf
        $needs_conversion = $target_mime_type != $internal_processing_format;

        // "Convert" DOCX files to DOCX in order to "repair" stuff, e.g. tokens that might have got split in the OOXML
        // due to spell checking or formatting.
        if ($prepare_docx && $internal_processing_format == CRM_Civioffice_MimeType::DOCX) {
            $lock = new CRM_Core_Lock('civicrm.office.civi_office_unoconv_local', 60, true);
            if (!$lock->acquire()) {
                throw new Exception(E::ts("Too many parallel conversions. Try using a smaller batch size"));
            }

            $temp_store_folder_path = $temp_store->getBaseFolder();
            $local_temp_store = new CRM_Civioffice_DocumentStore_LocalTemp(
                $internal_processing_format,
                $temp_store_folder_path
            );
            $prepared_document = $local_temp_store->getLocalCopyOfDocument(
                $document_with_placeholders,
                $document_with_placeholders->getName()
            );

            // Rename source DOCX files to *.docxsource for avoiding unoconv storage errors.
            exec(
                "cd $temp_store_folder_path"
                . '&& for f in *.docx; do mv -- "$f" "${f%.docx}.docxsource"; done'
            );

            $convert_command = "cd $temp_store_folder_path && {$this->unoconv_path} -v -f docx *.docxsource 2>&1";
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
                throw new Exception(E::ts("Too many parallel conversions. Try using a smaller batch size"));
            }
        }

        $tokenreplaced_documents = [];
        $temp_store_folder_path = $temp_store->getBaseFolder();
        $local_temp_store = new CRM_Civioffice_DocumentStore_LocalTemp($internal_processing_format, $temp_store_folder_path);

        $file_ending_name = CRM_Civioffice_MimeType::mapMimeTypeToFileExtension($target_mime_type);

        /*
         * Token replacement
         *
         * example tokens:
         * Hello {contact.display_name} aka {contact.first_name}!
         *
         */
        foreach ($entity_ids as $entity_id) {
            $zip = new ZipArchive();

            $new_file_name = $this->createDocumentName($entity_id, 'docx');
            $transitional_docx_document = $local_temp_store->getLocalCopyOfDocument($prepared_document ?? $document_with_placeholders, $new_file_name);

            // open xml file (like .docx) as a zip file, as in fact it is one
            $zip->open($transitional_docx_document->getAbsolutePath());
            $numberOfFiles = $zip->numFiles;
            if (empty($numberOfFiles)) throw new Exception("Unoconv: Docx (zip) file seems to be broken or path is wrong");

            // iterate through all docx components (files in zip)
            for ($i = 0; $i < $numberOfFiles; $i++) {
                // Step 1/4 unpack xml (.docx) file and handle it as a zip file as it is one
                $fileContent = $zip->getFromIndex($i);
                $fileName = $zip->getNameIndex($i);

                // Step 2/4 replace tokens
                /**
                 * TODO: Skip irrelevant parts, like binary files (images, etc.).
                 *   @url https://github.com/systopia/de.systopia.civioffice/issues/13
                 *   As a first step, we filter for XML files only.
                 */
                if (0 === substr_compare($fileName, '.xml', - strlen('.xml'))) {
                    $fileContent = $this->wrapTokensInStringWithXmlEscapeCdata($fileContent);
                    $tokenContexts = [
                        $entity_type => ['entity_id' => $entity_id],
                        'civioffice' => ['live_snippets' => $live_snippets]
                    ];
                    if ($entity_type == 'contribution') {
                        $contribution = Api4\Contribution::get()
                            ->addWhere('id', '=', $entity_id)
                            ->execute()
                            ->single();
                        $tokenContexts['contact'] = ['entity_id' => $contribution['contact_id']];
                        $tokenContexts['contribution']['entity'] = $contribution;
                    }
                    $fileContent = $this->replaceAllTokens($fileContent, $tokenContexts);
                }

                // Step 3/4 repack it again as xml (docx)
                $zip->addFromString($fileName, $fileContent);
            }

            $zip->close();

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
            exec('rm ' . $prepared_document->getAbsolutePath());
        }

        if (!$needs_conversion) {
            // We can return here and skip conversion as the transition format is equal to the output format
            return $tokenreplaced_documents;
        }

        $convert_command = "cd $temp_store_folder_path && {$this->unoconv_path} -v -f $file_ending_name *.docx 2>&1";
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
     * Takes a string with one or many {domain.context} style tokens and wraps a CDATA block around it to
     * not break xml files by using illegal symbols like: ' () & , " <>
     * Input example:  Welcome {contact.display_name} aka {contact.first_name}. Great to have you!
     * Output example: Welcome <![CDATA[{contact.display_name}]]> aka <![CDATA[{contact.first_name}]]>. Great to have you!
     * @param $string
     *
     * @return string
     *   Returns the whole string with escaped tokens
     */
    private function wrapTokensInStringWithXmlEscapeCdata($string): string
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
    private function createDocumentName($entity_id, string $file_ending_name): string
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
            $our_path = dirname($this->unoconv_path);
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
        $lock_file = Civi::settings()->get(CRM_Civioffice_DocumentRenderer_LocalUnoconv::UNOCONV_LOCK_PATH_SETTINGS_KEY);
        if ($lock_file) {
            $this->lock_file = fopen($lock_file,"r+");
            if (!flock($this->lock_file, LOCK_EX)) {
                throw new Exception(E::ts("Could not aquire unoconv lock. Sorry"));
            }
        }
    }

    /**
     * wait for the unoconv resource to become available
     */
    protected function unlock()
    {
        if ($this->lock_file) {
            if (!flock($this->lock_file, LOCK_UN)) {
                Civi::log()->debug("CiviOffice: Could not release unoconv lock.");
            }
            fclose($this->lock_file);
            $this->lock_file = null;
        }
    }
}
