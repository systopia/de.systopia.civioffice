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

declare(strict_types = 1);

/**
 * CiviOffice abstract document
 *
 * phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
 */
abstract class CRM_Civioffice_Document {
// phpcs:enable
  protected CRM_Civioffice_DocumentStore $document_store;

  protected string $uri;

  protected string $name;

  protected function __construct(CRM_Civioffice_DocumentStore $document_store, string $uri, string $name) {
    $this->uri = $uri;
    $this->name = $name;
    $this->document_store = $document_store;
  }

  /**
   * Get the document store containing this file
   *
   * @return CRM_Civioffice_DocumentStore
   *   the related document store
   */
  public function getDocumentStore() : CRM_Civioffice_DocumentStore {
    return $this->document_store;
  }

  /**
   * Get the file's MIME type
   *
   * @return string
   *   MIME type
   */
  public function getMimeType() : string {
    return $this->document_store->getMimeType($this);
  }

  /**
   * Get the file's URI
   *
   * @return string
   *   uri
   */
  public function getURI() : string {
    return $this->uri;
  }

  /**
   * Get the file's name
   *
   * @return string
   *   name
   */
  public function getName() : string {
    return $this->name;
  }

  /**
   * Get the (binary) content of the file
   *
   * @return string
   *   binary file data
   */
  abstract public function getContent() : string;

  /**
   * Set the (binary) content of the file
   *
   * @param string $data
   *   binary file data
   */
  abstract public function updateFileContent(string $data): void;

  /**
   * get the file's (local) path
   *
   * @return string
   *   path
   */
  abstract public function getPath() : string;

  /**
   * Can this file be edited?
   *
   * @return bool
   *   is this file editable
   */
  abstract public function isEditable() : bool;

  /**
   * Helper function to offer the given document as a CiviCRM download,
   *  i.e. post the data as file disposition and exit
   */
  public function download(): void {
    $data = $this->getContent();
    CRM_Utils_System::download(
        $this->getName(),
        $this->getMimeType(),
        $data,
        NULL,
        TRUE
    );
  }

  /**
   * Helper function to offer the given document data
   *   as a local tmp file
   *
   * @return string temporary file containing the file
   */
  public function getLocalTempCopy(): string {
    $tmp_file_name = tempnam(sys_get_temp_dir(), '') . '_' . $this->getName();
    file_put_contents($tmp_file_name, $this->getContent());
    return $tmp_file_name;
  }

}
