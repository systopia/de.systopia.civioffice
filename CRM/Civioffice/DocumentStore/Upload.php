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
    const UPLOAD_PUBLIC_ENABLED_SETTINGS_KEY  = 'civioffice_store_upload_public';
    const UPLOAD_PRIVATE_ENABLED_SETTINGS_KEY = 'civioffice_store_upload_private';

    /** @var string specific folder of documents of this instance incl. /common or /contact_ */
    protected $folder_name;

    /** @var string root folder for all upload document stores e.g. /common or /contact_ */
    protected $base_folder;

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

        parent::__construct("upload:{$user_folder}", $common ? E::ts("Shared Uploads") : E::ts("My Uploads"));
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
     * Return the (local) base folder
     *
     * @return string
     *   local base folder
     */
    public function getBaseFolder() : string
    {
        return $this->base_folder;
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
        return [CRM_Civioffice_MimeType::DOCX];
    }


    /**
     * Get the URL to configure this component
     *
     * @return string
     *   URL
     */
    public function getConfigPageURL() : string
    {
        return CRM_Utils_System::url('civicrm/admin/civioffice/settings/uploaddocumentstore', 'reset=1');
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
        $enabled = Civi::settings()->get($this->common ? self::UPLOAD_PUBLIC_ENABLED_SETTINGS_KEY : self::UPLOAD_PRIVATE_ENABLED_SETTINGS_KEY);
        return
            $enabled
            && file_exists($this->folder_name)
            && is_dir($this->folder_name);
    }

    /**
     * Is the other (sibling) document store ready?
     *
     * @return boolean
     *   URL
     */
    public function isSiblingStoreReady() : bool
    {
        $sibling_store = new CRM_Civioffice_DocumentStore_Upload(!$this->common);
        return $sibling_store->isReady();
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
        if (substr($uri, 0, 7) == 'local::') {
            // todo: disallow '..' for security
            $file_name_with_ending = substr(strstr($uri, '/'), strlen('/'));
            $absolute_path_with_file_name = $this->folder_name . DIRECTORY_SEPARATOR . $file_name_with_ending;

            $this->base_folder = $this->folder_name; // better to use only one?
            if (file_exists($absolute_path_with_file_name)) {
                // todo: check for mime type
                $local_path = substr($absolute_path_with_file_name, strlen($this->folder_name) + 1);
                return new CRM_Civioffice_Document_Local($this, CRM_Civioffice_MimeType::DOCX, $local_path, true);
            }
        }
        return null;
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
            return E::ts("Shared Uploaded Documents. There is no folder setup needed as the CiviCRM internal upload folder is being used here. Be aware. If enabled very user has full access to upload documents.");
        } else {
            return E::ts("My Uploaded Documents. Users only have access to files being uploaded by themself. The CiviCRM internal upload folder is being used");
        }
    }

}
