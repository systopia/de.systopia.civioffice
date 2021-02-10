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

    /**
     * constructor
     *
     * @param string $unoconv_path
     *   path to the local unoconv file
     */
    public function __construct( $unoconv_path = '/usr/bin/unoconv')
    {
        parent::__construct('unoconv-local', E::ts("Local Universal Converter"));
        $this->unoconv_path = $unoconv_path;
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
        // todo: implement

        return [];
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
