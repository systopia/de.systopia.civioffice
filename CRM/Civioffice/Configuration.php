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
 * CiviOffice Configuration
 */
class CRM_Civioffice_Configuration
{
    protected static $singleton = null;

    /**
     * @return \CRM_Civioffice_Configuration
     *  the current configuraiton
     */
    public static function getConfig()
    {
        if (self::$singleton === null) {
            self::$singleton = new CRM_Civioffice_Configuration();
        }
        return self::$singleton;
    }


    /**
     * Get the list of active document stores
     *
     * @param boolean $active_only
     *   return only active objects
     *
     * @return array
     */
    public function getDocumentStores($active_only = true) : array
    {
        // todo: get from config
        return [
            new CRM_Civioffice_DocumentStore_Local('test', "Test", '/tmp/civioffice', 'application/vnd.openxmlformats-officedocument.wordprocessingm', false, true)
        ];
    }


    /**
     * Get the list of active document stores
     *
     * @param boolean $active_only
     *   return only active objects
     *
     * @return array
     */
    public function getConverters($active_only = true) : array
    {
        // todo: get from config
        return [
            new CRM_Civioffice_Converter_LocalUnoconv()
        ];
    }

    /**
     * Get the list of active document stores
     *
     * @param boolean $active_only
     *   return only active objects
     *
     * @return array
     */
    public function getEditors($active_only = true) : array
    {
        // todo: get from config
        return [];
    }

}
