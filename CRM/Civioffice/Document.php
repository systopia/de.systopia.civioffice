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
 * CiviOffice abstract document
 */
abstract class CRM_Civioffice_Document
{
    /** @var \CRM_Civioffice_DocumentStore document store */
    protected $document_store;

    /** @var string mime type */
    protected $mime_type;

    /** @var string uri */
    protected $uri;

    /**
     * Get the document store containing this file
     *
     * @return CRM_Civioffice_DocumentStore
     *   the related document store
     */
    public function getDocumentStore() : CRM_Civioffice_DocumentStore
    {
        return $this->document_store;
    }

    /**
     * Get the file's mime type
     *
     * @return string
     *   mime type
     */
    public function getMimeType() : string
    {
        return $this->mime_type;
    }

    /**
     * Get the file's URI
     *
     * @return string
     *   uri
     */
    public function getURI() : string
    {
        return $this->uri;
    }

    /**
     * Get the (binary) content of the file
     *
     * @return string
     *   binary file data
     */
    public abstract function getContent() : string;

    /**
     * Set the (binary) content of the file
     *
     * @param string $data
     *   binary file data
     */
    public abstract function updateFileContent(string $data);


    /**
     * get the file's (local) path
     *
     * @return string
     *   path
     */
    public abstract function getPath() : string;


    /**
     * Can this file be edited?
     *
     * @return bool
     *   is this file editable
     */
    public abstract function isEditable() : bool;
}
