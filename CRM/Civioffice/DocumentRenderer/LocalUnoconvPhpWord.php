<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
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
class CRM_Civioffice_DocumentRenderer_LocalUnoconvPhpWord extends CRM_Civioffice_DocumentRenderer_LocalUnoconv
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
        parent::__construct('unoconv-local-phpword', E::ts("Local Universal Office Converter (unoconv) implementing PhpWord"));
        $this->unoconv_path = Civi::settings()->get(self::UNOCONV_BINARY_PATH_SETTINGS_KEY);
        if (empty($this->unoconv_path)) {
            Civi::log()->debug("CiviOffice: Path to unoconv binary / wrapper script is missing");
            $this->unoconv_path = "";
        }
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
            $new_file_name = $this->createDocumentName($entity_id, 'docx');
            $transitional_docx_document = $local_temp_store->getLocalCopyOfDocument($prepared_document ?? $document_with_placeholders, $new_file_name);

            // Replace live snippet tokens using PhpWord TemplateProcessor (resp. our extending variant of it).
            try {
                $templateProcessor = new CRM_Civioffice_DocumentRenderer_LocalUnoconvPhpWord_TemplateProcessor(
                    $transitional_docx_document->getAbsolutePath()
                );
            }
            catch (PhpWord\Exception\Exception $exception) {
                throw new Exception("Unoconv: Docx (zip) file seems to be broken or path is wrong");
            }

            // Replace CiviCRM tokens with PhpWord macros (convert format from "{token}" to "${macro}").
            $templateProcessor->liveSnippetTokensToMacros();

            foreach ($live_snippets as $live_snippet_name => $live_snippet) {
                // Replace tokens in live snippets (excluding nested live snippets).
                $tokenContexts = [
                    $entity_type => ['entity_id' => $entity_id],
                ];
                $live_snippet = $this->replaceAllTokens($live_snippet, $tokenContexts);

                // Use a temporary Section element for adding the elements.
                $phpWord = PhpWord\IOFactory::load($transitional_docx_document->getAbsolutePath());
                $section = $phpWord->addSection();
                // TODO: addHtml() doesn't accept styles so added HTML elements don't get any existing styles applied.
                PhpWord\Shared\Html::addHtml($section, $live_snippet);
                // Replace live snippet macros, ...
                if (
                    count($elements = $section->getElements()) == 1
                    && is_a($elements[0],'PhpOffice\\PhpWord\\Element\\Text')
                ) {
                    // ... either as plain text (if there is only a single Text element), ...
                    $templateProcessor->setValue('civioffice.live_snippets.' . $live_snippet_name, $live_snippet);
                }
                else {
                    // ... or as HTML: Render all elements and replace the paragraph containing the macro.
                    // Note: This will remove everything else around the macro.
                    // TODO: Save and split surrounding contents and add them to the replaced block.
                    //       This would be a logical assumption, since HTML elements will always make for a new
                    //       paragraph, so that text before and after the macro would then become their own paragraphs.
                    $elements_data = '';
                    foreach ($section->getElements() as $element) {
                        $elementName = substr(get_class($element), strrpos(get_class($element), '\\') + 1);
                        $objectClass = 'PhpOffice\\PhpWord\\Writer\\Word2007\\Element\\' . $elementName;

                        $xmlWriter = new PhpWord\Shared\XMLWriter();
                        /** @var \PhpOffice\PhpWord\Writer\Word2007\Element\AbstractElement $elementWriter */
                        $elementWriter = new $objectClass($xmlWriter, $element, false);
                        $elementWriter->write();
                        $elements_data .= $xmlWriter->getData();
                    }
                    $templateProcessor->replaceXmlBlock('civioffice.live_snippets.' . $live_snippet_name, $elements_data, 'w:p');
                }
            }
            $templateProcessor->saveAs($transitional_docx_document->getAbsolutePath());

            // Replace all other tokens manually.
            // TODO: Use PhpWord template processor for these as well.
            $zip = new ZipArchive();

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
                    $fileContent = $this->replaceAllTokens($fileContent, [
                        'contact' => ['entity_id' => $entity_id],
                    ]);
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
     * Get the (localised) component description
     *
     * @return string
     *   name
     */
    public function getDescription(): string
    {
        return E::ts("This document renderer employs the <code>unoconv</code> script on your server to convert documents using LibreOffice and implements PhpWord for replacing CiviCRM tokens.");
    }
}
