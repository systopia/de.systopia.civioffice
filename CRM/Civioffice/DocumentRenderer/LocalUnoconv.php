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
    const MIN_UNOCONV_VERSION = '6.0'; // todo: determine
    const SETTING_NAME = 'civioffice_unoconv_binary_path';

    /** @var string path to the unoconv binary */
    protected $unoconv_path;

    /** @var CRM_Civioffice_DocumentStore temp store for converted files */
    protected $temp_store = null;

    /**
     * constructor
     *
     */
    public function __construct()
    {
        parent::__construct('unoconv-local', E::ts("Local Universal Office Converter (unoconv)"));
        $this->unoconv_path = Civi::settings()->get(self::SETTING_NAME);
        if (empty($this->unoconv_path)) {
            $this->unoconv_path = '/usr/bin/unoconv'; // default value
        }
        $this->temp_store = new CRM_Civioffice_DocumentStore_LocalTemp(CRM_Civioffice_MimeType::PDF);
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
     *   list of token_name => token value
     */
    public function render($document_with_placeholders, array $entity_ids, CRM_Civioffice_DocumentStore_LocalTemp $temp_store, string $target_mime_type, $entity_type ='contact'
    ) : array
    {
        $tokenreplaced_documents = []; // todo needed?
        $temp_store_folder_path = $temp_store->getBaseFolder();


        /*
         * Token replacement
         * - unpack xml (docx) file
         * - replace tokens
         * - repack it again as xml (docx)
         *
         * example tokens:
         * Hello {contact.display_name} aka {contact.first_name}!
         *
         */
        foreach ($entity_ids as $entity_id) {
            $transitional_xml_based_document = $temp_store->addFile("Document-{$entity_id}.docx");

            $zip = new ZipArchive();

            // copy and rename to target filename. Keeps the xml file name ending e.g. .docx
            copy($document_with_placeholders->getAbsolutePath(), $transitional_xml_based_document->getAbsolutePath());

            // open xml file (like .docx) as a zip file, as in fact it is one
            $zip->open($transitional_xml_based_document->getAbsolutePath());

            $processor = new \Civi\Token\TokenProcessor(
                Civi::service('dispatcher'), [
                    'controller' => __CLASS__,
                    'smarty' => false,
                ]
            );

            $zip_file_list = [];
            /*
             * Possible optimisation opportunities to save many iterations
             * todo: save array positions on initialisation and only touch files where tokens need to be replaced?
             */
            $numberOfFiles = $zip->numFiles;
            for ($i = 0; $i < $numberOfFiles; $i++) {
                $fileContent = $zip->getFromIndex($i);
                $fileName = $zip->getNameIndex($i);

                // add each file content to the token processor
                $processor->addMessage($fileName, $fileContent, 'text/plain');

                $zip_file_list[] = $fileName;
            }

            // fixme: use generic entity instead of 'contact'
            // An array in the form "contextName => contextData" with different token contexts and their needed data (for example, contact IDs).
            $processor->addRow()->context('contactId', $entity_id); // needed?

            $processor->evaluate();

            $rows = $processor->getRows();
            foreach ($rows as $row) { // not needed if there is only one row?
                foreach ($zip_file_list as $fileName) {
                    $fileContent = $row->render($fileName);
                    $zip->addFromString($fileName, $fileContent);
                }
            }

            $zip->close();

            $tokenreplaced_documents[] = $transitional_xml_based_document;
        }

        /*
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

        // todo: Use target_mime_type instead of hardcoded pdf
        // todo: call converter $source_document->getURI() => $converted_document->getURI()
        $command = "cd $temp_store_folder_path && {$this->unoconv_path} -v -f pdf *.docx";
        exec($command, $exec_output, $exec_return_code);
        if($exec_return_code != 0) Civi::log()->debug("CiviOffice: Return code 0 expected but {$exec_return_code} given");
        exec("cd $temp_store_folder_path && rm *.docx");

        return $tokenreplaced_documents; // todo needed?
    }

    /**
     * Get the URL to configure this component
     *
     * @return string
     *   URL
     */
    public function getConfigPageURL() : string
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
}
