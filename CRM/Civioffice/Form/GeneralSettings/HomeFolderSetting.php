<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2025 SYSTOPIA                            |
| Author: S. Nowotnik (nowotnik@systopia.de)             |
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
class CRM_Civioffice_Form_GeneralSettings_HomeFolderSetting extends CRM_Core_Form
{
    public function preProcess(): void
    {
        // Restrict supported actions.
        if (0 === ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE))) {
            throw new RuntimeException(E::ts('Action not supported.'));
        }
    }

    public function buildQuickForm(): void
    {
        if ($this->_action == CRM_Core_Action::UPDATE) {

            $this->add(
                'text',
                'home_folder',
                E::ts("Home Folder (full path)"),
                ['class' => 'huge'],
                true // is required
            );

            $this->setDefaults(
                [
                    'home_folder' => Civi::settings()->get('civioffice_general_home_folder'),
                ]
            );
            $this->assign('formtype', 'update');
        }
        elseif ($this->_action == CRM_Core_Action::DELETE) {
            $this->add(
                'text',
                'home_folder',
                E::ts("Home Folder (full path)"),
                ['class' => 'huge', 'disabled' => TRUE],
                true // is required
            );

            $element = $this->getElement('home_folder');
            $element->setValue(CRM_Civioffice_Configuration::getHomeFolder());
            $element->freeze();

            $this->assign('formtype', 'delete');
        }

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
        // no need to check if folder value is empty as field is required
        $folder = $this->_submitValues['home_folder'];
        if (!is_dir($folder)) {
            $this->_errors['home_folder'] = E::ts("This is not a folder: [{$folder}]");
        }
        // else if (!is_readable($folder)) {
        //     $this->_errors['home_folder'] = E::ts("This folder cannot be accessed. There might be insufficient permission.");
        // }

        return (0 === count($this->_errors));
    }

    public function postProcess(): void
    {
        $values = $this->exportValues();
        Civi::settings()->set('civioffice_general_home_folder', $values['home_folder']);
    }

}
