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
 * Document store based on CivCRM's upload folder
 */
class CRM_Civioffice_DocumentStore_Upload extends CRM_Civioffice_DocumentStore
{
    /** @var string the folder name being used */
    protected $folder_name;

    /** @var boolean is this a set of common/shared documents or the user's private ones */
    protected $common;


    public function __construct($common)
    {
        $this->common = $common;

        // get the upload folder
        $config = CRM_Core_Config::singleton();
        $base_folder = $config->uploadDir . 'civioffice_documents'; // working?
        if (!file_exists($base_folder)) {
            mkdir($base_folder);
        }

        // get the user folder
        $user_folder = $common ? 'common' : 'contact_' . CRM_Core_Session::getLoggedInContactID();
        $this->folder_name = $base_folder . DIRECTORY_SEPARATOR . $user_folder;
        if (!file_exists($this->folder_name)) {
            mkdir($this->folder_name);
        }

        parent::__construct("upload:{$user_folder}", $common ? E::ts("Shared Upload") : E::ts("My Uploads"));
    }

    /**
     * Get the base folder name of this store
     *
     * @return string
     *   folder name
     */
    public function getFolder()
    {
        return $this->folder_name;
    }

    /**
     * Get a list of available documents
     *
     * @param string $path
     *   path, or null for root
     *
     * @return array
     *   list of CRM_Civioffice_Document objects
     */
    public function getDocuments($path = null) : array
    {
        $file_list = scandir($this->folder_name);
        $documents = [];
        foreach ($file_list as $file) {
            if (preg_match("/^[.].*$/", $file)) {
                continue; // we don't want anything that starts with . (including . and ..)
            }
            $file_path = $this->folder_name . DIRECTORY_SEPARATOR . $file;
            $base_folder = basename($this->folder_name) . DIRECTORY_SEPARATOR . $file;
            $documents[] = new CRM_Civioffice_Document_Local($this, mime_content_type($file_path), $base_folder, true);
        }

        return $documents;
    }

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
    public function getPaths($path = null) : array {
        return [];
    }

    /**
     * Get a list of paths under the given paths,
     *   i.e. subdirectories
     *
     * @return boolean
     *   is this document store read only
     */
    public function isReadOnly() : bool
    {
        return true;
    }

    /**
     * Get a list of document mime types supported by this component
     *
     * @return array
     *   list of mime types as strings
     */
    public function getSupportedMimeTypes() : array
    {
        return [CRM_Civioffice_MimeType::RENDERABLE];
    }


    /**
     * Get the URL to configure this component
     *
     * @return string
     *   URL
     */
    public function getConfigPageURL() : string
    {
        return '';
        /* TODO: create config page to do the following
                 - enable private
                 - enable public
                 - possibly: add links to menu
        */

    }

    /**
     * Is this component ready, i.e. properly
     *   configured and connected
     *
     * @return boolean
     *   URL
     */
    public function isReady() : bool
    {
        // todo: active
        return file_exists($this->folder_name) && is_dir($this->folder_name);
    }

    /**
     * Get a given document
     *
     * @param string $uri
     *   document URI
     *
     * @return CRM_Civioffice_Document|null
     *   list of CRM_Civioffice_Document objects
     */
    public function getDocumentByURI($uri)
    {
        // TODO:
//        if (substr($uri, 0, 7) == 'local::') {
//            // this is potentially one of ours:
//            $file_name_with_ending = substr($uri, 7);
//            // todo: disallow '..' for security
//            $absolute_path_with_file_name = $this->base_folder . DIRECTORY_SEPARATOR . $file_name_with_ending;
//            if (file_exists($absolute_path_with_file_name)) {
//                // todo: check for mime type
//                $local_path = substr($absolute_path_with_file_name, strlen($this->base_folder) + 1);
//                return new CRM_Civioffice_Document_Local($this, $this->mime_type, $local_path, $this->readonly);
//            }
//        }
//        return null;
    }

    /**
     * Get the (localised) component description
     *
     * @return string
     *   name
     */
    public function getDescription(): string
    {
        if ($this->common) {
            return E::ts("Shared Uploaded Documents");
        } else {
            return E::ts("My Uploaded Documents");
        }
    }

}
