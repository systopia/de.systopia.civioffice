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

/**
 *
 */
class CRM_Civioffice_DocumentRenderer_LocalUnoconv extends CRM_Civioffice_DocumentRenderer
{
    const MIN_UNOCONV_VERSION = '0.7'; // todo: determine

    const UNOCONV_BINARY_PATH_SETTINGS_KEY = 'civioffice_unoconv_binary_path';
    const TEMP_FOLDER_PATH_SETTINGS_KEY = 'civioffice_temp_folder_path';

    /** @var string path to the unoconv binary */
    protected $unoconv_path;

    /**
     * constructor
     *
     */
    public function __construct()
    {
        parent::__construct('unoconv-local', E::ts("Local Universal Office Converter (unoconv)"));
        $this->unoconv_path = Civi::settings()->get(self::UNOCONV_BINARY_PATH_SETTINGS_KEY);
        if (empty($this->unoconv_path)) {
            $this->unoconv_path = '/usr/bin/unoconv'; // default value
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
            exec("{$this->unoconv_path} --version", $output, $result_code);

            // todo: test version

            if (!empty($result_code)) {
                return false;
            }

            $output_line_with_version = $output[0];
            // todo: test for $MIN_UNOCONV_VERSION version. Version being tested is 0.7
            if (strpos($output_line_with_version, 'unoconv') === false) {
                return false;
            }

            $temp_folder = Civi::settings()->get(CRM_Civioffice_DocumentRenderer_LocalUnoconv::TEMP_FOLDER_PATH_SETTINGS_KEY);

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
        } catch (Exception $ex) {
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
    public function getOutputMimeTypes(): array
    {
        return [CRM_Civioffice_MimeType::PDF];
    }

    /**
     * Get a list of document mime types supported by this component
     *
     * @return array
     *   list of mime types as strings
     */
    public function getSupportedMimeTypes(): array
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
     */
    public function render(
        $document_with_placeholders,
        array $entity_ids,
        CRM_Civioffice_DocumentStore_LocalTemp $temp_store,
        string $target_mime_type,
        $entity_type = 'contact'
    ): array {
        $tokenreplaced_documents = [];
        $temp_store_folder_path = $temp_store->getBaseFolder();

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
            $transitional_docx_document = new CRM_Civioffice_DocumentStore_LocalTemp(CRM_Civioffice_MimeType::DOCX, $temp_store_folder_path);
            $transitional_docx_document = $transitional_docx_document->getLocalCopyOfDocument($document_with_placeholders, $new_file_name);

            // open xml file (like .docx) as a zip file, as in fact it is one
            $zip->open($transitional_docx_document->getAbsolutePath());

            /*
             * Possible optimisation opportunities to save many iterations
             * todo: filter binary files like jpgs?
             */

            $numberOfFiles = $zip->numFiles;
            if (empty($numberOfFiles)) throw new Exception("Unoconv: Docx (zip) file seems to be broken or path is wrong");

            for ($i = 0; $i < $numberOfFiles; $i++) {
                // Step 1/4 unpack xml (.docx) file and handle it as a zip file as it is one
                $fileContent = $zip->getFromIndex($i);
                $fileName = $zip->getNameIndex($i);

                // Step 2/4 replace tokens
                $fileContent = $this->wrapTokensInStringWithXmlEscapeCdata($fileContent);
                $fileContent = $this->replaceAllTokens($fileContent, $entity_id, 'contact');

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

        if ($target_mime_type == CRM_Civioffice_MimeType::DOCX) {
            // We can return here and skip conversion as the transition format is equal to the output format
            return $tokenreplaced_documents;
        }

        $convert_command = "cd $temp_store_folder_path && {$this->unoconv_path} -v -f $file_ending_name *.docx";

        exec($convert_command, $exec_output, $exec_return_code);
        if ($exec_return_code != 0) {
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
        exec("cd $temp_store_folder_path && rm *.docx");

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
        return E::ts("Unoconv binary path at: '%1'", [1 => $this->unoconv_path]);
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
}
