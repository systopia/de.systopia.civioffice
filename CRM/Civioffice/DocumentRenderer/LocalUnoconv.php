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
        $this->temp_store = new CRM_Civioffice_DocumentStore_LocalTemp(CRM_Civioffice_MimeType::PDF, null, true); //needs to be true!
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
    public function render(
        $document_with_placeholders,
        array $entity_ids,
        CRM_Civioffice_DocumentStore_LocalTemp $temp_store,
        string $target_mime_type,
        $entity_type = 'contact'
    ): array {
        $tokenreplaced_documents = [];
        $temp_store_folder_path = $temp_store->getBaseFolder();
        // shadow: This store represents docx file names and is only used for being returned as conversion happens entirely without this store in unoconv
        $shadow_temp_result_store = new CRM_Civioffice_DocumentStore_LocalTemp('pdf', $temp_store_folder_path, true);

        /*
         * Token replacement
         *
         * example tokens:
         * Hello {contact.display_name} aka {contact.first_name}!
         *
         */
        foreach ($entity_ids as $entity_id) {
            // todo save name identifier at a central place
            $transitional_xml_based_document = $temp_store->addFile("Document-{$entity_id}.docx");
            $shadow_pdf = $shadow_temp_result_store->addFile("Document-{$entity_id}.pdf", null, true); //needs to be true!

            $zip = new ZipArchive();

            // copy and rename to target filename. Keeps the xml file name ending e.g. .docx
            copy($document_with_placeholders->getAbsolutePath(), $transitional_xml_based_document->getAbsolutePath());

            // open xml file (like .docx) as a zip file, as in fact it is one
            $zip->open($transitional_xml_based_document->getAbsolutePath());


            /*
             * Possible optimisation opportunities to save many iterations
             * todo: filter binary files like jpgs?
             */

            $document_renderer_unoconv = new CRM_Civioffice_DocumentRenderer_LocalUnoconv();

            $numberOfFiles = $zip->numFiles;
            for ($i = 0; $i < $numberOfFiles; $i++) {
                // Step 1/4 unpack xml (.docx) file and handle it as a zip file as it is one
                $fileContent = $zip->getFromIndex($i);
                $fileName = $zip->getNameIndex($i);

                // Step 2/4 replace tokens
                $fileContent = $document_renderer_unoconv->replaceAllTokens($fileContent, $entity_id, 'contact');

                // Step 3/4 repack it again as xml (docx)
                $zip->addFromString($fileName, $fileContent);
            }

            $zip->close();

            $tokenreplaced_documents[] = $shadow_pdf;
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

        // todo: add a file name to mime type mapping in CRM_Civioffice_MimeType 1/2
        // todo: return here if target mime type is docx
        if ($target_mime_type == CRM_Civioffice_MimeType::PDF) {
            $mime_type_ending_name = 'pdf';
        } else {
            throw new Exception('Mime types other than pdf yet need to be implemented and tested');
        }

        $command_cd = "cd $temp_store_folder_path && {$this->unoconv_path} -v -f $mime_type_ending_name *.docx";

        // $command = "{$this->unoconv_path} -v -f pdf tmp_civioffice_123.docx tmp/civioffice_124.docx";

        exec($command_cd, $exec_output, $exec_return_code);
        if ($exec_return_code != 0) {
            Civi::log()->debug("CiviOffice: Exception: Return code 0 expected but {$exec_return_code} given");
            throw new Exception('Unoconv: Return code 0 expected');
        }
        // fixme: This only works when apache2 protected temp is disabled
        exec("cd $temp_store_folder_path && rm *.docx");

        return $tokenreplaced_documents;
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
}
