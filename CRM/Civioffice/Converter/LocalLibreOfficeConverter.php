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
class CRM_Civioffice_Converter_LocalLibreOfficeConverter extends CRM_Civioffice_Converter
{
    const MIN_UNOCONV_VERSION = '6.0'; // todo: determine

    /** @var string path to the unoconv binary */
    protected $unoconv_path = '/usr/bin/unoconv';

    /** @var \CRM_Civioffice_DocumentStore temp store for converted files  */
    protected $temp_store = null;

    /**
     * constructor
     *
     * @param string $unoconv_path
     *   path to the local unoconv file
     */
    public function __construct( $unoconv_path = '/usr/bin/unoconv')
    {
        parent::__construct('unoconv-local', E::ts("Local Universal Office Converter"));
        $this->unoconv_path = $unoconv_path;
        $this->temp_store = new CRM_Civioffice_DocumentStore_LocalTemp('application/pdf');
    }

    /**
     * Is this backend currently available?
     *
     * @return boolean
     *   is this backend ready for use
     */
    public function isReady() : bool
    {
        // todo: test if the binary is there
        try {
            exec("{$this->unoconv_path} --version", $output, $result_code);

            // todo: test version

            if (!empty($result_code)) {
                return false;
            }
        } catch (Exception $ex) {
            return false;
        }
        return true;
    }

    /**
     * Get the output/generated mime types for this converter
     *
     * @return array
     *   list of mime types
     */
    public function getOutputMimeTypes() : array
    {
        return ['application/pdf'];
    }

    /**
     * Get a list of document mime types supported by this component
     *
     * @return array
     *   list of mime types as strings
     */
    public function getSupportedMimeTypes() : array
    {
        return ['application/vnd.openxmlformats-officedocument.wordprocessingm'];
    }

    /**
     * Convert the list of documents to the given mime type
     *
     * @param array $documents
     *   list of CRM_Civioffice_Document objects
     *
     * @param string $target_mime_type
     *   mime type to convert to
     *
     * @return array
     *   list of CRM_Civioffice_Document objects
     */
    public function convert(array $documents, string $target_mime_type) : array
    {
        $conversions = [];
        foreach ($documents as $original_document) {
            /** @var $original_document CRM_Civioffice_Document */
            $converted_document = $this->temp_store->addFile($original_document->getName());

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
             */

            // todo: call converter $original_document->getURI() => $converted_document->getURI()
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
        // TODO:
        return '';
    }

}
