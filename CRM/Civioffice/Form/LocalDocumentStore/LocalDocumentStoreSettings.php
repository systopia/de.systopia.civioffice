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

declare(strict_types = 1);

use CRM_Civioffice_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Civioffice_Form_LocalDocumentStore_LocalDocumentStoreSettings extends CRM_Core_Form {

  public function buildQuickForm() {
    // add form elements
    $this->add(
        'text',
        'local_folder',
        E::ts('Local Folder (full path)'),
        ['class' => 'huge'],
        TRUE
    );

    $this->add(
        'text',
        'local_temp_folder',
        E::ts('Local Temporary Folder (full path)'),
        ['class' => 'huge'],
        TRUE
    );

    $this->setDefaults(
        [
          'local_folder' => Civi::settings()->get(CRM_Civioffice_DocumentStore_Local::LOCAL_STATIC_PATH_SETTINGS_KEY),
          'local_temp_folder' => Civi::settings()->get(
            CRM_Civioffice_DocumentStore_Local::LOCAL_TEMP_PATH_SETTINGS_KEY
          ),
        ]
    );
    $this->assign('local_folder_suggestion', Civi::paths()->getPath('[civicrm.files]/civioffice'));
    $this->assign('local_temp_folder_suggestion', sys_get_temp_dir() . '/civioffice');

    $this->addButtons(
        [
            [
              'type' => 'submit',
              'name' => E::ts('Save'),
              'isDefault' => TRUE,
            ],
        ]
    );

    parent::buildQuickForm();
  }

  /**
   * Validate input data
   *
   * @return bool
   */
  public function validate(): bool {
    parent::validate();

    // verify that the folder is 1) there, 2) readable
    $local_folder = trim($this->_submitValues['local_folder']);
    if ('' !== $local_folder) {
      if (!file_exists($local_folder) && !mkdir($local_folder, 0777, TRUE)) {
        $this->_errors['local_folder'] = E::ts('Could not create directory');
      }
      else {
        if (!is_dir($local_folder)) {
          $this->_errors['local_folder'] = E::ts('This is not a folder');
        }
        elseif (!is_readable($local_folder)) {
          $this->_errors['local_folder'] = E::ts('This folder cannot be accessed');
        }
      }
    }

    $local_temp_folder = trim($this->_submitValues['local_temp_folder']);
    if ('' !== $local_temp_folder) {
      if (!file_exists($local_temp_folder) && !mkdir($local_temp_folder, 0777, TRUE)) {
        $this->_errors['local_temp_folder'] = E::ts('Could not create directory');
      }
      else {
        if (!is_dir($local_temp_folder)) {
          $this->_errors['local_temp_folder'] = E::ts('This is not a folder');
        }
        elseif (!is_readable($local_temp_folder)) {
          $this->_errors['local_temp_folder'] = E::ts('This folder cannot be accessed');
        }
      }
    }

    return 0 === count($this->_errors);
  }

  public function postProcess(): void {
    $values = $this->exportValues();

    // store
    Civi::settings()
      ->set(CRM_Civioffice_DocumentStore_Local::LOCAL_STATIC_PATH_SETTINGS_KEY, trim($values['local_folder']))
      ->set(CRM_Civioffice_DocumentStore_Local::LOCAL_TEMP_PATH_SETTINGS_KEY, trim($values['local_temp_folder']));
  }

}
