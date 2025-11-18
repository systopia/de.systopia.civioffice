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

use Civi\Civioffice\FilesystemUtil;
use CRM_Civioffice_ExtensionUtil as E;

/**
 * Document store based on a local folder
 */
class CRM_Civioffice_DocumentStore_LocalTemp extends CRM_Civioffice_DocumentStore_Local {

  public function __construct(?string $temp_folder_path = NULL) {
    // create tmp folder
    if (empty($temp_folder_path)) {
      // create temp folder with random postfix like: var/civioffice/temp/civioffice_202_6050ec8acc7a5

      $user_selectable_path = rtrim(Civi::settings()->get(self::LOCAL_TEMP_PATH_SETTINGS_KEY) ?? '', '\\/');
      $current_user_id = CRM_Core_Session::singleton()->getLoggedInContactID();

      $temp_folder_path = $user_selectable_path . DIRECTORY_SEPARATOR . uniqid("civioffice_{$current_user_id}_");
      if (file_exists($temp_folder_path)) {
        Civi::log()->debug('CiviOffice: Temp folder already exists. Deleting and trying to create a new one');
        FilesystemUtil::removeRecursive($temp_folder_path);
      }
      mkdir($temp_folder_path, 0777, TRUE);
    }
    parent::__construct("tmp::$temp_folder_path", E::ts('Temporary Files'), FALSE, FALSE);
    $this->base_folder = $temp_folder_path;
    Civi::log()->debug('CiviOffice: Created local temp document store at: ' . $this->base_folder);

    register_shutdown_function(function () {
      if (FilesystemUtil::isDirEmpty($this->getBaseFolder())) {
        rmdir($this->getBaseFolder());
      }
    });
  }

  /**
   * Create a new temporary file
   *
   * @param string $file_name
   *
   * @return CRM_Civioffice_Document_LocalTempfile
   *   new temp file
   */
  public function addFile(string $file_name): CRM_Civioffice_Document_LocalTempfile {
    $file_path_including_filename = $this->base_folder . DIRECTORY_SEPARATOR . $file_name;
    return new CRM_Civioffice_Document_LocalTempfile(
        $this,
        $file_path_including_filename
    );
  }

  /**
   * Copy and rename to target filename. Uses .docx as file name ending
   *
   * @throws \RuntimeException
   */
  public function getLocalCopyOfDocument(
    CRM_Civioffice_Document $source_document,
    string $new_file_name
  ): CRM_Civioffice_Document_LocalTempfile {
    $final_document = $this->addFile($new_file_name);

    // fill the new file with the document data
    if ($source_document instanceof CRM_Civioffice_Document_Local) {
      // if this is a local document, we can simply copy on the file system
      $from_path = $source_document->getAbsolutePath();
      $to_path   = $final_document->getAbsolutePath();
      if (!copy($from_path, $to_path)) {
        throw new RuntimeException('Unoconv: getLocalCopyOfDocument(): Failed to copy file');
      }
    }
    else {
      // if this is NOT a local file, we just write the content
      file_put_contents($final_document->getAbsolutePath(), $source_document->getContent());
    }

    return $final_document;
  }

  public function packAllFiles(): void {
    // todo zip logic could be moved into here
  }

  /**
   * Get a local document store by URI
   *
   * @return CRM_Civioffice_DocumentStore_LocalTemp|null
   *   returns the local tmp store matching the uri, or null if it's not a local temp store URI
   */
  public static function getByURI(string $uri): ?CRM_Civioffice_DocumentStore_LocalTemp {
    if (substr($uri, 0, 5) == 'tmp::') {
      $folder = substr($uri, 5);
      if (file_exists($folder)) {
        return new CRM_Civioffice_DocumentStore_LocalTemp($folder);
      }
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function isStoreURI(string $uri): bool {
    return (substr($uri, 0, 5) == 'tmp::');
  }

}
