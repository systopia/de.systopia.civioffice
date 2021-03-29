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
 * Document store based on a local folder
 */
class CRM_Civioffice_DocumentStore_Local extends CRM_Civioffice_DocumentStore
{
    const LOCAL_STATIC_PATH = 'civioffice_store_local_static_path';

    /** @var string local folder this store has access to */
    protected $base_folder;

    /** @var string mime_type */
    protected $mime_type;

    /** @var boolean should this be readable */
    protected $readonly;

    /** @var boolean should there be subfolders? */
    protected $has_subfolders;

    public function __construct($id, $name, $mime_type, $readonly, $has_subfolders)
    {
        parent::__construct($id, $name);
        $this->base_folder = Civi::settings()->get(self::LOCAL_STATIC_PATH);
        $this->mime_type = $mime_type;
        $this->readonly = $readonly;
        $this->has_subfolders = $has_subfolders;
    }

    /**
     * Get a list of available documents
     *
     * @param string $path
     *   path, or null for root
     *
     * @return array
     *   list of CRM_Civioffice_Document objects
     * @throws \Exception
     */
    public function getDocuments($path = null) : array
    {
        if ($this->has_subfolders) {
            $path = null;
        }

        // todo: sanitise path ../..
        $full_path = $this->base_folder;
        if ($path) {
            $full_path = $this->base_folder . DIRECTORY_SEPARATOR . $path;
        }

        $file_list = scandir($full_path);
        $documents = [];
        foreach ($file_list as $file_name) {
            if (preg_match("/^[.].*$/", $file_name)) {
                continue; // we don't want anything that starts with . (including . and ..)
            }
            if (!$this->hasSpecificFileNameExtension($file_name, CRM_Civioffice_MimeType::DOCX)) continue;

            $base_folder = substr($full_path . DIRECTORY_SEPARATOR . $file_name, strlen($this->base_folder) + 1);
            $documents[] = new CRM_Civioffice_Document_Local($this, $this->mime_type, $base_folder, $this->readonly);
        }

        return $documents;
    }

    /**
     * Checks if the file ending/extension matches with the given fully qualified mime type
     *
     * @param $file_name
     * @param $mime_type
     *
     * @return bool Returns true if given mime type is equal to ending/extension
     * @throws \Exception
     */
    private function hasSpecificFileNameExtension($file_name, $mime_type): bool
    {
        $ending = CRM_Civioffice_MimeType::mapMimeTypeToFileExtension($mime_type);
        $length_from_right = -1 * abs(strlen($ending)); // negative as we want to check the file extension

        return substr($file_name, $length_from_right) == $ending;
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
        $paths = [];

        if ($this->has_subfolders) {
            $full_path = $this->base_folder;
            if ($path) {
                $full_path = $this->base_folder . DIRECTORY_SEPARATOR . $path;
            }

            $file_list = scandir($full_path);
            foreach ($file_list as $file) {
                if (is_dir($file)) {
                    // todo: filter for . / ..
                    $paths[] = $full_path . DIRECTORY_SEPARATOR . $file;
                }
            }
        }

        return $paths;

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
        return $this->readonly;
    }

    /**
     * Get a list of document mime types supported by this component
     *
     * @return array
     *   list of mime types as strings
     */
    public function getSupportedMimeTypes() : array
    {
        return [$this->mime_type];
    }


    /**
     * Get the URL to configure this component
     *
     * @return string
     *   URL
     */
    public function getConfigPageURL() : string
    {
        return CRM_Utils_System::url('civicrm/admin/civioffice/settings/localdocumentstore');
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
        return file_exists($this->base_folder) && is_dir($this->base_folder);
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
        if (substr($uri, 0, 7) == 'local::') {
            // this is potentially one of ours:
            $file_name_with_ending = substr($uri, 7);
            // todo: disallow '..' for security
            $absolute_path_with_file_name = $this->base_folder . DIRECTORY_SEPARATOR . $file_name_with_ending;
            if (file_exists($absolute_path_with_file_name)) {
                // todo: check for mime type
                $local_path = substr($absolute_path_with_file_name, strlen($this->base_folder) + 1);
                return new CRM_Civioffice_Document_Local($this, $this->mime_type, $local_path, $this->readonly);
            }
        }
        return null;
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
     * Get the (localised) component description
     *
     * @return string
     *   name
     */
    public function getDescription(): string
    {
        return E::ts("All documents at: '%1'", [1 => $this->base_folder]);
    }

}
