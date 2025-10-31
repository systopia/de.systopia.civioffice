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

use CRM_Civioffice_ExtensionUtil as E;

/**
 * Document store based on a local folder
 */
class CRM_Civioffice_DocumentStore_Local extends CRM_Civioffice_DocumentStore {

  public const LOCAL_STATIC_PATH_SETTINGS_KEY = 'civioffice_store_local_static_path';

  public const LOCAL_TEMP_PATH_SETTINGS_KEY = 'civioffice_store_local_temp_path';

  /**
   * Local folder this store has access to.
   */
  protected ?string $base_folder;

  /**
   * Local folder this store has access to.
   */
  protected ?string $temp_folder;

  /**
   * Whether this should only be readable.
   */
  protected bool $readonly;

  /**
   * Whether there should be subfolders.
   */
  protected bool $has_subfolders;

  /**
   * @phpstan-param string $uri
   * @phpstan-param string $name
   */
  public function __construct($uri, $name, bool $readonly, bool $has_subfolders) {
    parent::__construct($uri, $name);

    /** @phpstan-var string|null $baseFolder */
    $baseFolder = Civi::settings()->get(self::LOCAL_STATIC_PATH_SETTINGS_KEY);
    // TODO: trim() existing config value in an upgrade step and do not trim() here.
    $this->base_folder = is_string($baseFolder) ? trim($baseFolder) : NULL;

    /** @phpstan-var string|null $tempFolder */
    $tempFolder = Civi::settings()->get(self::LOCAL_TEMP_PATH_SETTINGS_KEY);
    // TODO: trim() existing config value in an upgrade step and do not trim() here.
    $this->temp_folder = is_string($tempFolder) ? trim($tempFolder) : NULL;

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
  public function getDocuments($path = NULL) : array {
    if ($this->has_subfolders) {
      $path = NULL;
    }

    // todo: sanitise path ../..
    $full_path = $this->base_folder;
    if ($path) {
      $full_path = $this->base_folder . DIRECTORY_SEPARATOR . $path;
    }

    $file_list = scandir($full_path);
    $documents = [];
    foreach ($file_list as $file_name) {
      if (preg_match('/^[.].*$/', $file_name)) {
        // we don't want anything that starts with . (including . and ..)
        continue;
      }

      $base_folder = substr($full_path . DIRECTORY_SEPARATOR . $file_name, strlen($this->base_folder) + 1);
      $documents[] = new CRM_Civioffice_Document_Local($this, $base_folder, $this->readonly);
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
  public function getPaths($path = NULL) : array {
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
  public function isReadOnly() : bool {
    return $this->readonly;
  }

  /**
   * Get the URL to configure this component
   *
   * @return string
   *   URL
   */
  public function getConfigPageURL() : string {
    return CRM_Utils_System::url('civicrm/admin/civioffice/settings/localdocumentstore');
  }

  /**
   * Is this component ready, i.e. properly
   *   configured and connected
   *
   * @return boolean
   *   URL
   */
  public function isReady() : bool {
    // todo: active
    return (isset($this->base_folder) && file_exists($this->base_folder) && is_dir($this->base_folder))
            && (isset($this->temp_folder) && file_exists($this->temp_folder) && is_dir($this->temp_folder));
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
  public function getDocumentByURI($uri) {
    if (substr($uri, 0, 7) == 'local::') {
      // this is potentially one of ours:
      $file_name_with_ending = substr($uri, 7);
      // todo: disallow '..' for security
      $absolute_path_with_file_name = $this->base_folder . DIRECTORY_SEPARATOR . $file_name_with_ending;
      if (file_exists($absolute_path_with_file_name)) {
        // todo: check for MIME type
        $local_path = substr($absolute_path_with_file_name, strlen($this->base_folder) + 1);
        return new CRM_Civioffice_Document_Local($this, $local_path, $this->readonly);
      }
    }
    return NULL;
  }

  /**
   * Return the (local) base folder
   *
   * @return string
   *   local base folder
   */
  public function getBaseFolder() : string {
    return $this->base_folder;
  }

  /**
   * Get the (localised) component description
   *
   * @return string
   *   name
   */
  public function getDescription(): string {
    // phpcs:disable Generic.Files.LineLength.TooLong
    return E::ts('A local folder is needed if documents are stored and managed on the server. CiviOffice only uses it for read access. This folder could be a pre existing shared folder of the organisation. A local folder is not being used for uploaded documents.<br> All documents at: <code>%1</code>', [1 => $this->base_folder]);
    // phpcs:enable
  }

  /**
   * Check if the given URI matches this store
   *
   * @param string $uri
   *
   * @return boolean
   */
  public function isStoreURI($uri) {
    return (substr($uri, 0, 7) == 'local::');
  }

}
