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
 * CiviOffice abstract Document Store
 */
abstract class CRM_Civioffice_Editor extends CRM_Civioffice_OfficeComponent
{
    /**
     * Get URL of the editor
     *
     * @return string
     *   url path of the editor
     */
    public abstract function getURL() : string;

    /**
     * Get a list of paths under the given paths,
     *   i.e. subdirectories
     *
     * @param string $path
     *   path, or null for root
     *
     * @return array
     *   list of strings representing paths
     */
    public abstract function getPaths($path = null) : array;

    /**
     * Is this document able to be inline edited?
     *
     * @param $document
     *
     * @return boolean
     *   is this document store read only
     */
    public abstract function canInlineEdit($document) : bool;

    /**
     * Is this editor able to be included in other sites e.g. as an inline frame?
     *
     * @return boolean
     */
    public abstract function ableToBeIncludedAsInlineFrame() : bool;
}
