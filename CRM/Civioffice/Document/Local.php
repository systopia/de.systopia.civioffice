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
class CRM_Civioffice_Document_Local extends CRM_Civioffice_Document
{
    /** @var string local folder this store has access to */
    protected $local_path;

    /** @var boolean should this be readable */
    protected $readonly;

    public function __construct($document_store, $mime_type, $local_path, $readonly)
    {
        $uri = 'local::' . $local_path;
        parent::__construct($document_store, $mime_type, $uri, basename($local_path));
        $this->local_path = $local_path;
        $this->$readonly = $readonly;
    }

    /**
     * Get the (binary) content of the file
     *
     * @return string
     *   binary file data
     */
    public function getContent() : string
    {
        // todo: exceptions
        return file_get_contents($this->local_path);
    }

    /**
     * Set the (binary) content of the file
     *
     * @param string $data
     *   binary file data
     */
    public function updateFileContent(string $data)
    {
        if ($this->isEditable()) {
            // todo: exceptions
            file_put_contents($this->local_path, $data);
        } else {
            // todo: throw exception?
        }
    }

    /**
     * get the file's (local) path
     *
     * @return string
     *   path
     */
    public function getPath() : string
    {
        return $this->local_path;
    }


    /**
     * Can this file be edited?
     *
     * @return bool
     *   is this file editable
     */
    public function isEditable() : bool
    {
        if ($this->readonly) {
            return false;
        } else {
            return is_writeable($this->local_path);
        }
    }
}
