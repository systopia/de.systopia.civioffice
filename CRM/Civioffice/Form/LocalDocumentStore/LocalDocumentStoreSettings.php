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
class CRM_Civioffice_Form_LocalDocumentStore_LocalDocumentStoreSettings extends CRM_Core_Form
{
    public function buildQuickForm()
    {
        // add form elements
        $this->add(
            'text',
            'local_folder',
            E::ts("Local Folder (full path)"),
            ['class' => 'huge'],
            true
        );

        $this->setDefaults(
            [
                'local_folder' => Civi::settings()->get(CRM_Civioffice_DocumentStore_Local::LOCAL_STATIC_PATH_SETTINGS_KEY),
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

        // verify that the folder is 1) there, 2) readable
        if (!empty($this->_submitValues['local_folder'])) {
            $local_folder = $this->_submitValues['local_folder'];
            if (!is_dir($local_folder)) {
                $this->_errors['local_folder'] = E::ts("This is not a folder");
            } else {
                if (!is_readable($local_folder)) {
                    $this->_errors['local_folder'] = E::ts("This folder cannot be accessed");
                }
            }
        }

        return (0 == count($this->_errors));
    }


  
    public function postProcess()
    {
        $values = $this->exportValues();

        // store
        Civi::settings()->set(CRM_Civioffice_DocumentStore_Local::LOCAL_STATIC_PATH_SETTINGS_KEY, $values['local_folder']);
    }

}
