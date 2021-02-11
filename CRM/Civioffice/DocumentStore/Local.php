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
    /** @var string local folder this store has access to */
    protected $local_path;

    /** @var string mime_type */
    protected $mime_type;

    /** @var boolean should this be readable */
    protected $readonly;

    /** @var boolean should there be subfolders? */
    protected $subfolders;

    public function __construct($id, $name, $local_path, $mime_type, $readonly, $subfolders)
    {
        parent::__construct($id, $name);
        $this->local_path = $local_path;
        $this->mime_type = $mime_type;
        $this->readonly = $readonly;
        $this->subfolders = $subfolders;
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
        if ($this->subfolders) {
            $path = null;
        }

        // todo: santise path ../..
        $full_path = $this->local_path;
        if ($path) {
            $full_path = $this->local_path . DIRECTORY_SEPARATOR . $path;
        }

        $file_list = scandir($full_path);
        $documents = [];
        foreach ($file_list as $file) {
            if (preg_match("/^[.].*$/", $file)) {
                continue; // we don't want anything that starts with . (including . and ..)
            }
            // todo: filter for files (not dirs)
            // todo: check for mime type
            $local_path = $full_path . DIRECTORY_SEPARATOR . $file;
            $documents[] = new CRM_Civioffice_Document_Local($this, $this->mime_type, $local_path, $this->readonly);
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
        $paths = [];

        if ($this->subfolders) {
            $full_path = $this->local_path;
            if ($path) {
                $full_path = $this->local_path . DIRECTORY_SEPARATOR . $path;
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
        // todo:
        return 'tood';
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
        return file_exists($this->local_path) && is_dir($this->local_path);
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
            $path = substr($uri, 7);
            // todo: disallow '..' for security
            $full_path = $this->local_path . DIRECTORY_SEPARATOR . $path;
            if (file_exists($full_path)) {
                // todo: check for mime type
                return new CRM_Civioffice_Document_Local($this, $this->mime_type, $full_path, $this->readonly);
            }
        }
        return null;
    }
}
