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
abstract class CRM_Civioffice_DocumentStore extends CRM_Civioffice_OfficeComponent
{
    /**
     * Get a list of available documents
     *
     * @param string $path
     *   path, or null for root
     *
     * @return array
     *   list of CRM_Civioffice_Document objects
     */
    public abstract function getDocuments($path = null) : array;

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
     * Get a list of paths under the given paths,
     *   i.e. subdirectories
     *
     * @return boolean
     *   is this document store read only
     */
    public abstract function isReadOnly() : bool;

    /**
     * Get a given document
     *
     * @param string $uri
     *   document URI
     *
     * @return CRM_Civioffice_Document|null
     *   list of CRM_Civioffice_Document objects
     */
    public abstract function getDocumentByURI($uri);

    /**
     * Check if the given URI matches this store
     *
     * @param string $uri
     *
     * @return boolean
     */
    public abstract function isStoreURI($uri);

    /**
     * Generate a zipfile of all documents contained,
     *  and trigger download
     */
    public function downloadZipped()
    {
        // ZIP
        $tmp_file = tmpfile(); // todo: use buffer instead of tmp file
        $zip = new ZipArchive();
        $zip->open($tmp_file, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);

        // add all documents
        foreach ($this->getDocuments() as $document) {
            /** @var \CRM_Civioffice_Document $document */
            $zip->addFromString($document->getName(), $document->getContent());
        }
        $zip->close();

        $data = file_get_contents($tmp_file);
        CRM_Utils_System::download(
            $this->getName(),
            CRM_Civioffice_MimeType::ZIP,
            $data,
            null,
            true
        );
    }
}
