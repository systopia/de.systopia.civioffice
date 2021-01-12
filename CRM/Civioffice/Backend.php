<?php
/*-------------------------------------------------------+
| SYSTOPIA OnlyOffice Integration                        |
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
 * CiviOffice abstract backend
 */
abstract class CRM_Civioffice_Backend
{
    /**
     * Get a list of available backends
     *
     * @return array
     *   list of CRM_Civioffice_Backend instances
     */
    public static function getBackends() {

        $backends = [
            new CRM_Civioffice_Backend_LocalLibreOfficeConverter(),
        ];

        // todo: call symfony event to collect more backend implementations

        return $backends;
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
