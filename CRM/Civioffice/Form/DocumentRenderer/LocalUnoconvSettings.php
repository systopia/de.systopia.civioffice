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
          E::ts("path to the unoconv binary"),
          [],
          true
      );

      $this->add(
          'text',
          'temp_folder_path',
          E::ts("path to the working temp folder"),
          [],
          true
      );

      $this->setDefaults(
          [
              'unoconv_binary_path' => Civi::settings()->get(CRM_Civioffice_DocumentRenderer_LocalUnoconv::SETTING_NAME),
              'temp_folder_path' => Civi::settings()->get(CRM_Civioffice_DocumentRenderer_LocalUnoconv::TEMP_FOLDER_NAME)
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
     * @return bool
     */
    public function validate(): bool
    {
        parent::validate();
        return true; //fixme debug
        // todo: check if binary is there?

        // verify that the folder is 1) there, 2) readable
        if (!empty($this->_submitValues['unoconv_binary_path'])) {
            $local_folder = $this->_submitValues['local_folder'];
            if (!is_dir($local_folder)) {
                $this->_errors['unoconv_binary_path'] = E::ts("This is not a folder");
            } else {
                if (!is_readable($local_folder)) {
                    $this->_errors['unoconv_binary_path'] = E::ts("This folder cannot be accessed");
                }
            }
        }

        return (0 == count($this->_errors));
    }

  public function postProcess() {
      $values = $this->exportValues();

      // save to settings
      Civi::settings()->set(CRM_Civioffice_DocumentRenderer_LocalUnoconv::SETTING_NAME, $values['unoconv_binary_path']);

      $values['temp_folder_path'] = rtrim($values['temp_folder_path'], '\/');
      Civi::settings()->set(CRM_Civioffice_DocumentRenderer_LocalUnoconv::TEMP_FOLDER_NAME, $values['temp_folder_path']);
  }
}
