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
     * @return CRM_Civioffice_Configuration
     *  the current configuration
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
     * @param boolean $only_show_active
     *   return only active objects
     *
     * @return array
     */
    public static function getDocumentStores($only_show_active = true) : array
    {
        // todo: get from config
        // todo: filter for $only_show_active
        return [
            new CRM_Civioffice_DocumentStore_Local('test', "Local Folder", CRM_Civioffice_MimeType::DOCX, false, true)
        ];
    }


    /**
     * Get the list of active document stores
     *
     * @param boolean $only_show_active
     *   return only active objects
     *
     * @return array
     */
    public static function getDocumentRenderers($only_show_active = true) : array
    {
        // todo: get from config
        // todo: filter for $only_show_active
        return [
            new CRM_Civioffice_DocumentRenderer_LocalUnoconv()
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
    public static function getEditors($active_only = true) : array
    {
        // todo: get from config
        // todo: filter for $only_show_active
        return [];
    }

    /**
     * Find/get the document renderer with the given URI
     *
     * @param string $document_renderer_id
     *   document renderer URI
     *
     * @return CRM_Civioffice_DocumentRenderer|null
     */
    public function getDocumentRenderer(string $document_renderer_id)
    {
        $document_renderers = self::getDocumentRenderers(false);
        foreach ($document_renderers as $dr) {
            if ($document_renderer_id == $dr->getID()) {
                return $dr;
            }
        }
        return null; // not found
    }

    /**
     * Get the document with the given URI
     *
     * @param string $document_uri
     *   document URI
     *
     * @return CRM_Civioffice_Document|null
     */
    public function getDocument($document_uri)
    {
        $stores = self::getDocumentStores(false);
        foreach ($stores as $store) {
            // see if this one has the file
            $document = $store->getDocumentByURI($document_uri);
            if ($document) {
                return $document;
            }
        }
        return null; // not found
    }


    /**
     * Get the home folder of the current user (usually webserver)
     *
     * @return string
     */
    public static function getHomeFolder(): string
    {
        // try environment
        if (!empty($_SERVER['HOME'])) {
            return $_SERVER['HOME'];
        }

        // try via shell
        $user_name = get_current_user();
        exec("eval echo \"~{$user_name}\"", $eval_output);
        if (!empty($eval_output[0])) {
            return $eval_output[0];
        }

        // todo: what else to check?
        Civi::log()->warning("CiviOffice: Couldn't determine web user's home folder.");
        return '~';
    }
}
