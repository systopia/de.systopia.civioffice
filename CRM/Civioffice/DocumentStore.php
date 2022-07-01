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
     * Retrieves a document's MIME type.
     *
     * @param \CRM_Civioffice_Document $document
     *   The document to determine the MIME type for.
     *
     * @return string | false
     *   The document's MIME type, or FALSE when it could not be determined.
     *
     * @throws \Exception
     *   When the document does not belong to the document store.
     */
    public function getMimeType(CRM_Civioffice_Document $document) {
        if ($document->getDocumentStore()->getURI() !== $this->getURI()) {
            throw new Exception('Document does not belong to DocumentStore, can not retrieve MIME type.');
        }
        // Fallback: Get a local temporary copy and retrieve MIME type using PHP.
        $mime_type_cache = Civi::cache()->get('civioffice_mime_type');
        $mime_type = $mime_type_cache[$document->getURI()] ?? NULL;
        if (!$mime_type) {
            $local = $document->getLocalTempCopy();
            $mime_type = mime_content_type($local);
            $mime_type_cache[$document->getURI()] = $mime_type;
            Civi::cache()->set('civioffice_mime_type', $mime_type_cache);
        }
        return $mime_type;
    }

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
