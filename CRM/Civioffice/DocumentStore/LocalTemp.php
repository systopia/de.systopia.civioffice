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
    public function __construct($mime_type, $temp_folder_path = null) // fixme: mime type used as id?
    {
        // create tmp folder
        if (empty($temp_folder_path)) {
            // create temp folder with random postfix like: /tmp/civioffice_yssE0h

            // $temp_folder_path = tempnam(sys_get_temp_dir(), 'civioffice_');

            $user_selectable_path = '/var/civioffice/temp';

            $temp_folder_path = $user_selectable_path . DIRECTORY_SEPARATOR . uniqid('civioffice_');
            if (file_exists($temp_folder_path)) {
                unlink($temp_folder_path);
                Civi::log()->debug("CiviOffice: Temp folder already exists. Deleting and trying to create a new one");
            }
            // fixme creates multiple unused temp folders
            mkdir($temp_folder_path);
        }
        parent::__construct("tmp::{$temp_folder_path}", E::ts("Temporary Files"), $mime_type, false, false);
        $this->base_folder = $temp_folder_path;
        Civi::log()->debug("CiviOffice: Created local temp document store at: " . $this->base_folder);
    }

    /**
     * Create an new temporary file
     *
     * @param string $file_name
     * @param string $content
     *
     * @return CRM_Civioffice_Document_LocalTempfile
     *   new temp file
     */
    public function addFile(string $file_name, $content = null): CRM_Civioffice_Document_LocalTempfile
    {
        $file_path_including_filename = $this->base_folder . DIRECTORY_SEPARATOR . $file_name;
        return new CRM_Civioffice_Document_LocalTempfile($this, $this->mime_type, $file_path_including_filename);
    }

    public function packAllFiles()
    {
        // todo zip logic could be moved into here
    }
}
