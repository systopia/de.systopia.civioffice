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

    /** @var \CRM_Civioffice_DocumentStore temp store for converted files  */
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
    public function isReady() : bool
    {
        try {
            exec("{$this->unoconv_path} --version", $output, $result_code);

            // todo: test version

            if (!empty($result_code)) {
                return false;
            }

            $output_line_with_version = $output[0];
            // todo: test for $MIN_UNOCONV_VERSION version
            if(strpos($output_line_with_version, 'unoconv') === false) {
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
    public function getOutputMimeTypes() : array
    {
        return [CRM_Civioffice_MimeType::PDF];
    }

    /**
     * Get a list of document mime types supported by this component
     *
     * @return array
     *   list of mime types as strings
     */
    public function getSupportedMimeTypes() : array
    {
        return [CRM_Civioffice_MimeType::DOCX];
    }

    /**
     * Render a document for a list of entities
     *
     * @param CRM_Civioffice_Document $document
     *   the document to be rendered
     *
     * @param array $entity_ids
     *   entity ID, e.g. contact_id
     *
     * @param string $entity_type
     *   entity type, e.g. 'contact'
     *
     * @return array
     *   list of token_name => token value
     */
    public function render($document, $entity_ids, string $target_mime_type, $entity_type ='contact') : array
    {
        $conversions = [];
        // todo: convert as a batch
        foreach ($entity_ids as $entity_id) {
            /** @var $source_document CRM_Civioffice_Document */
            $converted_document = $this->temp_store->addFile("Document-{$entity_id}.pdf");

            // todo: implement

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

            $command = "{$this->unoconv_path} -v -f pdf -o '{$converted_document->getAbsolutePath()}' '{$source_document->getAbsolutePath()}'";
            Civi::log()->debug("CiviOffice: Running command: '{$command}'");
            // 251 = Help or version information printed
            exec($command, $exec_output, $exec_return_code);
            Civi::log()->debug("CiviOffice: Finished command: '{$command}'");

            if($exec_return_code != 0) Civi::log()->debug("CiviOffice: Return code 0 expected but {$exec_return_code} given");

            // todo: call converter $source_document->getURI() => $converted_document->getURI()
            $conversions[] = $converted_document;
        }

        return $conversions;
    }

    /**
     * Get the URL to configure this component
     *
     * @return string
     *   URL
     */
    public function getConfigPageURL() : string
    {
        return CRM_Utils_System::url('civicrm/admin/civioffice/settings/LocalUnoconv');
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
