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
class CRM_Civioffice_Form_DocumentRenderer_Settings extends CRM_Core_Form {

    protected CRM_Civioffice_DocumentRenderer $documentRenderer;

    protected CRM_Civioffice_DocumentRendererType $documentRendererType;

    /**
     * @return \CRM_Civioffice_DocumentRenderer
     */
    public function getDocumentRenderer(): ?CRM_Civioffice_DocumentRenderer
    {
        return $this->documentRenderer ?? null;
    }

    /**
     * @return \CRM_Civioffice_DocumentRendererType
     */
    public function getDocumentRendererType(): CRM_Civioffice_DocumentRendererType
    {
        return $this->documentRendererType;
    }

    public function preProcess()
    {
        // Restrict supported actions.
        if (!($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE))) {
            throw new Exception(E::ts('Invalid action.'));
        }

        // Require ID for editing/deleting.
        if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
            if (!$uri = CRM_Utils_Request::retrieve('id', 'Alphanumeric', $this)) {
                throw new Exception(E::ts('Missing Document Renderer ID.'));
            }
            $this->documentRenderer = CRM_Civioffice_DocumentRenderer::load($uri);
            $this->documentRendererType = $this->documentRenderer->getType();
        }

        // Require type for adding.
        if ($this->_action & (CRM_Core_Action::ADD)) {
            if (!$type = CRM_Utils_Request::retrieve('type', 'Alphanumeric', $this)) {
                throw new Exception(E::ts('Missing Document Renderer type.'));
            }
            $this->documentRendererType = CRM_Civioffice_DocumentRendererType::create($type);
        }

        // Make sure to redirect to the CiviOffice settings page.
        CRM_Core_Session::singleton()->replaceUserContext(
            CRM_Utils_System::url(
                'civicrm/admin/civioffice/settings',
                "reset=1"
            )
        );
    }

    public function buildQuickForm()
    {
        if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
            $this->add(
                'text',
                'name',
                E::ts('Name'),
                ['class' => 'huge'],
                true
            );
            if (isset($this->documentRenderer)) {
                $this->setDefaults(
                    [
                        'name' => $this->documentRenderer->getName(),
                    ]
                );
            }

            $this->documentRendererType->buildsettingsForm($this);
            $this->assign('rendererTypeSettingsTemplate', $this->documentRendererType::getSettingsFormTemplate());

            $this->addButtons(
                [
                    [
                        'type' => 'submit',
                        'name' => E::ts('Save'),
                        'isDefault' => true,
                    ],
                ]
            );
        }
        elseif ($this->_action == CRM_Core_Action::DELETE) {
            // No form elements to add, markup is being defined in the template.
        }

        $this->addDefaultButtons(
            $this->_action & CRM_Core_Action::DELETE ? E::ts('Delete') : E::ts('Save'),
            'submit',
            'cancel'
        );

        parent::buildQuickForm();
    }

    public function setDefaults($defaultValues = null, $filter = null)
    {
        // TODO: This sets new (unset) settings in existing renderers' forms to their default value, which might change
        //       settings when this is not intended.
        $defaultValues = array_filter($defaultValues, function($value) { return !is_null($value); });
        $defaultValues += $this->documentRendererType::defaultConfiguration();
        return parent::setDefaults($defaultValues, $filter);
    }

    /**
     * Validate input data
     * This method is executed before postProcess()
     * @return bool
     */
    public function validate(): bool
    {
        parent::validate();

        if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
            $this->documentRendererType->validateSettingsForm($this);
        }

        return (count($this->_errors) == 0);
    }

  public function postProcess() {
      // Create/update/delete option value.
      if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
          $values = $this->exportValues();

          if (!isset($this->documentRenderer)) {
              $this->documentRenderer = new CRM_Civioffice_DocumentRenderer(
                  $this->documentRendererType::getNextUri(),
                  $values['name'],
                  [
                      'type' => $this->documentRendererType->getURI(),
                  ]
              );
          }

          $this->documentRendererType->postProcessSettingsForm($this);

          $this->documentRenderer->save();
      }
      elseif ($this->_action & (CRM_Core_Action::DELETE)) {
          $this->documentRenderer->delete();
      }
  }
}
