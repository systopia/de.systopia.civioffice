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

use Civi\Civioffice\DocumentRenderer;
use Civi\Civioffice\DocumentRendererTypeContainer;
use Civi\Civioffice\DocumentRendererTypeInterface;
use CRM_Civioffice_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Civioffice_Form_DocumentRenderer_Settings extends CRM_Core_Form {

  private DocumentRenderer $documentRenderer;

  private DocumentRendererTypeInterface $documentRendererType;

  public function preProcess() {
    // Restrict supported actions.
    if (!($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE))) {
      throw new Exception(E::ts('Invalid action.'));
    }

    // Require ID for editing/deleting.
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
      if (!$uri = CRM_Utils_Request::retrieve('id', 'Alphanumeric', $this)) {
        throw new Exception(E::ts('Missing document renderer ID.'));
      }
      $this->documentRenderer = DocumentRenderer::load($uri);
      $this->documentRendererType = $this->documentRenderer->getType();
    }

    // Require type for adding.
    if ($this->_action & (CRM_Core_Action::ADD)) {
      if (!$type = CRM_Utils_Request::retrieve('type', 'Alphanumeric', $this)) {
        throw new Exception(E::ts('Missing document renderer type.'));
      }
      $this->documentRendererType = DocumentRendererTypeContainer::getInstance()->get($type);
    }

    // Make sure to redirect to the CiviOffice settings page.
    CRM_Core_Session::singleton()->replaceUserContext(
        CRM_Utils_System::url(
            'civicrm/admin/civioffice/settings',
            'reset=1'
        )
    );
  }

  public function buildQuickForm() {
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $this->add(
        'text',
        'name',
        E::ts('Name'),
        ['class' => 'huge'],
        TRUE
      );
      if (isset($this->documentRenderer)) {
        $this->setDefaults(
        [
          'name' => $this->documentRenderer->getName(),
        ] + $this->documentRenderer->getConfiguration()
        );
      }
      else {
        $this->setDefaults($this->documentRendererType->getDefaultConfiguration());
      }

      $this->documentRendererType->buildsettingsForm($this);
      $this->assign('rendererTypeSettingsTemplate', $this->documentRendererType->getSettingsFormTemplate());

      $this->addButtons(
        [
            [
              'type' => 'submit',
              'name' => E::ts('Save'),
              'isDefault' => TRUE,
            ],
        ]
      );
    }

    $this->addDefaultButtons(
        $this->_action & CRM_Core_Action::DELETE ? E::ts('Delete') : E::ts('Save'),
        'submit',
        'cancel'
    );

    parent::buildQuickForm();
  }

  /**
   * Validate input data
   * This method is executed before postProcess()
   * @return bool
   */
  public function validate(): bool {
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
        $this->documentRenderer = new DocumentRenderer(
        $this->documentRendererType::getName() . '-' . count(CRM_Civioffice_Configuration::getDocumentRenderers()),
          $values['name'],
          [
            'type' => $this->documentRendererType::getName(),
          ]
        );
      }
      else {
        $this->documentRenderer->setName($values['name']);
      }

      $configuration = $this->documentRendererType->postProcessSettingsForm($this);
      $this->documentRenderer->setConfiguration($configuration);
      $this->documentRenderer->save();
    }
    elseif ($this->_action & (CRM_Core_Action::DELETE)) {
      $this->documentRenderer->delete();
    }
  }

}
