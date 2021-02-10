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
 * CiviOffice abstract backend
 */
abstract class CRM_Civioffice_LocalDocumentStore
{
    /**
     * Get a list of available input mime types
     *
     * @return array
     *   list of available input mime types
     */
    public static function getSupportedMimeTypes() {

        $mimeTypes = [
            'docx',
        ];

        return $mimeTypes;
    }

    /**
     * is read only?
     *
     * @return true|false
     *   is read only?
     */
    public static function isReadOnly()
    {

    }

    /**
     * Is this backend currently available?
     *
     * @return boolean
     *   is this backend ready for use
     */
    public abstract function isReady();

    /**
     * Get a human-readable name
     *
     * @return string
     *   name string
     */
    public abstract function getName();

    /**
     * Get an internal ID
     *
     * @return string
     *   id string
     */
    public abstract function getID();

    /**
     * Get the URL for the backend's main configuration page
     *
     * @return string
     *   link to the config page
     */
    public abstract function getConfigPage();

}
