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
    public function __construct($id, $name, $mime_type, $temp_folder = null)
    {
        // create tmp folder
        if (empty($temp_folder)) {
            $temp_folder = tempnam(sys_get_temp_dir(),'civioffice');
            if (file_exists($temp_folder)) {
                unlink($temp_folder);
            }
            mkdir($temp_folder);
        }

        parent::__construct($id, $name, $temp_folder, $mime_type, false, false);
    }

    /**
     * Create an new temporary file
     *
     * @param string $file_name
     * @param string $content
     *
     * @return CRM_Civioffice_Document_LocalTempfile
     *   new temp file
     *
     */
    public function addFile($file_name, $content = null) : CRM_Civioffice_Document_LocalTempfile
    {
        $file_path = $this->local_path . DIRECTORY_SEPARATOR . $file_name;
        return new CRM_Civioffice_Document_LocalTempfile($this, $this->mime_type, $file_path);
    }

    public function zipAllFiles() {
        // todo: bjoern kopiert das
    }
}
