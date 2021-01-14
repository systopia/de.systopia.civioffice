<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
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
 * (Local)
 */
class CRM_Civioffice_Backend_LocalLibreOfficeConverter
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
    public function __construct($unoconv_path = '/usr/bin/unoconv')
    {
        // todo: detect?
        $this->unoconv_path = $unoconv_path;
    }

    /**
     * Is this backend currently available?
     *
     * @return boolean
     *   is this backend ready for use
     */
    public function isReady()
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
     * Get a human-readable name
     *
     * @return string
     *   name string
     */
    public function getName()
    {
        return E::ts("Local LibreOffice Converter (unoconv)");
    }

    /**
     * Get an internal ID
     *
     * @return string
     *   id string
     */
    public function getID()
    {
        return 'unoconv-local';
    }

    /**
     * Get the URL for the backend's main configuration page
     *
     * @return string
     *   link to the config page
     */
    public function getConfigPage()
    {
        return null;
    }


}
