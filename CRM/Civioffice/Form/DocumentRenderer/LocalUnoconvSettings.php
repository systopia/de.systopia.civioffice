<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2021 SYSTOPIA                            |
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
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Civioffice_Form_DocumentRenderer_LocalUnoconvSettings extends CRM_Core_Form {
  public function buildQuickForm() {

      // add form elements
      $this->add(
          'text',
          'unoconv_binary_path',
          E::ts("Path to the <code>unoconv</code> binary"),
          ['class' => 'huge'],
          true
      );

      $this->add(
          'text',
          'unoconv_lock_file',
          E::ts("Path to a lock files"),
          ['class' => 'huge'],
          false
      );

      $this->add(
          'text',
          'temp_folder_path',
          E::ts("path to the working temp folder"),
          ['class' => 'huge'],
          true
      );

      $this->setDefaults(
          [
              'unoconv_binary_path' => Civi::settings()->get(CRM_Civioffice_DocumentRenderer_LocalUnoconv::UNOCONV_BINARY_PATH_SETTINGS_KEY),
              'unoconv_lock_file' => Civi::settings()->get(CRM_Civioffice_DocumentRenderer_LocalUnoconv::UNOCONV_LOCK_PATH_SETTINGS_KEY),
              'temp_folder_path' => Civi::settings()->get(CRM_Civioffice_DocumentRenderer_LocalUnoconv::TEMP_FOLDER_PATH_SETTINGS_KEY)
          ]
      );

      $this->addButtons(
          [
              [
                  'type' => 'submit',
                  'name' => E::ts('Save'),
                  'isDefault' => true,
              ],
          ]
      );

      parent::buildQuickForm();
  }

    /**
     * Validate input data
     * This method is executed before postProcess()
     * @return bool
     */
    public function validate(): bool
    {
        parent::validate();

        $folder_to_check = $this->_submitValues['temp_folder_path'];
        $unoconv_path_to_check = $this->_submitValues['unoconv_binary_path'];
        $lockfile_to_check = $this->_submitValues['unoconv_lock_file'];


        if (empty($folder_to_check)) { // needed?
            $this->_errors['temp_folder_path'] = E::ts("Input is empty");
        }

        if (!is_writable($folder_to_check)) {
            $this->_errors['temp_folder_path'] = E::ts("Unable to write temp folder");
        }

        if (!is_dir($folder_to_check)) {
            $this->_errors['temp_folder_path'] = E::ts("This is not a folder");
        }

        if (!file_exists($unoconv_path_to_check)) {
            $this->_errors['unoconv_binary_path'] = E::ts("File does not exist. Please provide a correct filename");
        }

        if (!empty($lockfile_to_check)) {
            if (!file_exists($lockfile_to_check)) {
                $this->_errors['unoconv_lock_file'] = E::ts("Lock file does not exist. Please create as follows: 'touch {$lockfile_to_check} && chmod 777 {$lockfile_to_check}'");
            }
            if (!is_writable($lockfile_to_check)) {
                $this->_errors['unoconv_lock_file'] = E::ts("Lock file cannot be written. Please run: 'chmod 777 {$lockfile_to_check}'");
            }
        }

        return (count($this->_errors) == 0);
    }

  public function postProcess() {
      $values = $this->exportValues();

      // save to settings
      Civi::settings()->set(CRM_Civioffice_DocumentRenderer_LocalUnoconv::UNOCONV_BINARY_PATH_SETTINGS_KEY, $values['unoconv_binary_path']);
      Civi::settings()->set(CRM_Civioffice_DocumentRenderer_LocalUnoconv::UNOCONV_LOCK_PATH_SETTINGS_KEY, $values['unoconv_lock_file']);

      $values['temp_folder_path'] = rtrim($values['temp_folder_path'], '\/');
      Civi::settings()->set(CRM_Civioffice_DocumentRenderer_LocalUnoconv::TEMP_FOLDER_PATH_SETTINGS_KEY, $values['temp_folder_path']);
  }
}
