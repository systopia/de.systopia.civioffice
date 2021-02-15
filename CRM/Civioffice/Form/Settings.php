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
 * CiviOffice Settings
 */
class CRM_Civioffice_Form_Settings extends CRM_Core_Form
{

    public function buildQuickForm()
    {
        // add backends
        $active_backend_list = []; // plain list of
        $backend_implementations = [];
        /*
        $backends = CRM_Civioffice_Backend::getBackends();
        foreach ($backends as $backend) {
            if ($backend->isReady()) {
                $active_backend_list[$backend->getID()] = $backend->getName();
            }
            $backend_implementations[$backend->getID()] = [
                'id'         => $backend->getID(),
                'name'       => $backend->getName(),
                'is_ready'   => $backend->isReady(),
                'config_url' => $backend->getConfigPage()
            ];
        }
        */
        $this->assign('backends', $backend_implementations);

        // add form elements
        $this->add(
            'select',
            'active_backend',
            E::ts("Active Backend"),
            $active_backend_list,
            true
        );

        // add form elements
        $this->add(
            'select',
            'active_user_backend',
            E::ts("Active Backend (current user)"),
            $active_backend_list,
            true
        );

        // set defaults
        $this->setDefaults([
            'active_backend'      => Civi::settings()->get('civioffice_active_backend'),
            'active_user_backend' => Civi::contactSettings()->get('civioffice_active_backend'),
       ]);


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


    public function postProcess()
    {
        $values = $this->exportValues();

        // store settings
        Civi::settings()->set('civioffice_active_backend', $values['active_backend']);
        Civi::contactSettings()->set('civioffice_active_backend', $values['active_user_backend']);


        CRM_Core_Session::setStatus(
            E::ts("Settings Saved"),
            E::ts("The CiviOffice Configuration has been updated"),
            'info'
        );
        parent::postProcess();
    }

}
