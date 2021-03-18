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
class CRM_Civioffice_DocumentStore_LocalTemp extends CRM_Civioffice_DocumentStore_Local
{
    public function __construct($mime_type, $temp_folder_path = null, $skip_path_creation = false) // fixme: mime type used as id?
    {
        // create tmp folder
        if ($skip_path_creation) {
            $temp_folder_path = 'no-path'; //fixme: do not use $skip_path_creation in general or use null here?
        } else {
            if(empty($temp_folder_path)) {
                // create temp folder with random postfix like: var/civioffice/temp/civioffice_202_6050ec8acc7a5

                // fixme: remove last slash
                // todo: use entry from settings
                $user_selectable_path = Civi::settings()->get(CRM_Civioffice_DocumentRenderer_LocalUnoconv::TEMP_FOLDER_PATH_SETTINGS_KEY);
                $current_user_id = CRM_Core_Session::singleton()->getLoggedInContactId();

                $temp_folder_path = $user_selectable_path . DIRECTORY_SEPARATOR . uniqid("civioffice_{$current_user_id}_");
                if (file_exists($temp_folder_path)) {
                    unlink($temp_folder_path);
                    Civi::log()->debug("CiviOffice: Temp folder already exists. Deleting and trying to create a new one");
                }
                mkdir($temp_folder_path);
            }
        }
        parent::__construct("tmp::{$temp_folder_path}", E::ts("Temporary Files"), $mime_type, false, false);
        $this->base_folder = $temp_folder_path;
        Civi::log()->debug("CiviOffice: Created local temp document store at: " . $this->base_folder);
    }

    /**
     * Create an new temporary file
     *
     * @param string $file_name
     * @param null $content
     * @param bool $skip_path_creation
     *
     * @return CRM_Civioffice_Document_LocalTempfile
     *   new temp file
     * @throws \Exception
     */
    public function addFile(string $file_name, $content = null, $skip_path_creation = false): CRM_Civioffice_Document_LocalTempfile
    {
        $file_path_including_filename = $this->base_folder . DIRECTORY_SEPARATOR . $file_name;
        return new CRM_Civioffice_Document_LocalTempfile($this, $this->mime_type, $file_path_including_filename, $skip_path_creation);
    }

    public function packAllFiles()
    {
        // todo zip logic could be moved into here
    }
}
