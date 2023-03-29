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
        self::setTitle(E::ts("CiviOffice - Configuration"));

        $office_components = [
            'document_stores'    => CRM_Civioffice_Configuration::getDocumentStores(false),
            'document_renderers' => CRM_Civioffice_Configuration::getDocumentRenderers(false),
            'document_editors'   => CRM_Civioffice_Configuration::getEditors(false),
        ];

        $ui_components = [];
        foreach ($office_components as $element_type => $components) {
            foreach ($components as $instance) {
                /** @var $instance CRM_Civioffice_OfficeComponent */
                $ui_components[$element_type][] = [
                    'id'          => $instance->getURI(),
                    'name'        => $instance->getName(),
                    'description' => $instance->getDescription(),
                    'config_url'  => $instance->getConfigPageURL(),
                    'delete_url'  => $instance->getDeleteURL(),
                    'is_ready'    => $instance->isReady()
                ];
            }
        }

        $this->assign('document_renderer_types', CRM_Civioffice_Configuration::getDocumentRendererTypes());

        foreach (CRM_Civioffice_LiveSnippets::get() as $live_snippet) {
            $live_snippet['current_content'] = Civi::contactSettings()->get('civioffice.live_snippets.' . $live_snippet['name']);
            $ui_components['live_snippets'][$live_snippet['id']] = $live_snippet;
        }

        $this->assign('ui_components', $ui_components);

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


        CRM_Core_Session::setStatus(
            E::ts("Settings Saved"),
            E::ts("The CiviOffice Configuration has been updated"),
            'info'
        );
        parent::postProcess();
    }

}
