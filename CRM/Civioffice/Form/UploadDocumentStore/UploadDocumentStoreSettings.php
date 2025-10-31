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
class CRM_Civioffice_Form_UploadDocumentStore_UploadDocumentStoreSettings extends CRM_Core_Form {

  public function buildQuickForm(): void {
    // add form elements
    $this->add(
        'checkbox',
        CRM_Civioffice_DocumentStore_Upload::UPLOAD_PUBLIC_ENABLED_SETTINGS_KEY,
        E::ts('Shared Document Upload Enabled')
    );

    $this->add(
        'checkbox',
        CRM_Civioffice_DocumentStore_Upload::UPLOAD_PRIVATE_ENABLED_SETTINGS_KEY,
        E::ts('Private Document Upload Enabled')
    );

    $this->setDefaults(
        [
          CRM_Civioffice_DocumentStore_Upload::UPLOAD_PUBLIC_ENABLED_SETTINGS_KEY =>
          Civi::settings()->get(CRM_Civioffice_DocumentStore_Upload::UPLOAD_PUBLIC_ENABLED_SETTINGS_KEY),
          CRM_Civioffice_DocumentStore_Upload::UPLOAD_PRIVATE_ENABLED_SETTINGS_KEY =>
          Civi::settings()->get(CRM_Civioffice_DocumentStore_Upload::UPLOAD_PRIVATE_ENABLED_SETTINGS_KEY),
        ]
    );

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

  public function postProcess(): void {
    $values = $this->exportValues();

    // store
    Civi::settings()->set(
      CRM_Civioffice_DocumentStore_Upload::UPLOAD_PUBLIC_ENABLED_SETTINGS_KEY,
      $values[CRM_Civioffice_DocumentStore_Upload::UPLOAD_PUBLIC_ENABLED_SETTINGS_KEY] ?? FALSE
    );
    Civi::settings()->set(
      CRM_Civioffice_DocumentStore_Upload::UPLOAD_PRIVATE_ENABLED_SETTINGS_KEY,
      $values[CRM_Civioffice_DocumentStore_Upload::UPLOAD_PRIVATE_ENABLED_SETTINGS_KEY] ?? FALSE
    );
  }

}
