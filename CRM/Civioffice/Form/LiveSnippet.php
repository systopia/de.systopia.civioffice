<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
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

class CRM_Civioffice_Form_LiveSnippet extends CRM_Core_Form {
  protected $option_value;

  protected $option_group_id;

  public function preProcess() {
    parent::preProcess();

    // Restrict supported actions.
    if (!($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE))) {
      throw new Exception(E::ts('Invalid action.'));
    }

    $this->option_group_id = civicrm_api3(
        'OptionGroup',
        'getvalue',
        [
          'name' => 'civioffice_live_snippets',
          'return' => 'id',
        ]
    );

    // Require ID for editing/deleting.
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
      if (!$option_value_id = CRM_Utils_Request::retrieve('id', 'Integer', $this)) {
        throw new Exception(E::ts('Missing Live Snippet ID.'));
      }
      try {
        $this->option_value = civicrm_api3(
        'OptionValue',
        'getsingle',
        [
          'id' => $option_value_id,
          'option_group_id' => $this->option_group_id,
        ]
        );
      }
      catch (Exception $exception) {
        throw new Exception(E::ts('Invalid Live Snippet ID.'));
      }
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
      $this->setTitle(
        $this->_action & CRM_Core_Action::ADD
            ? E::ts('Add Live Snippet')
            : E::ts('Edit Live Snippet')
      );
      $this->add(
        'text',
        'label',
        E::ts('Name'),
        NULL,
        TRUE
      );
      $this->add(
        'text',
        'description',
        E::ts('Description')
      );
      $this->add(
        'text',
        'name',
        E::ts('Token'),
        NULL,
        TRUE
      );
    }

    $this->addDefaultButtons(
        $this->_action & CRM_Core_Action::DELETE ? E::ts('Delete') : E::ts('Save'),
        'submit',
        'cancel'
    );
  }

  public function setDefaultValues() {
    return [
      'name' => $this->option_value['name'],
      'label' => $this->option_value['label'],
      'description' => $this->option_value['description'],
    ];
  }

  public function validate() {
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $values = $this->exportValues();

      // Validate unique names.
      $params = [
        'option_group_id' => $this->option_group_id,
        'name' => $values['name'],
      ];
      if ($this->_action & CRM_Core_Action::UPDATE) {
        $params['id'] = ['!=' => $this->option_value['id']];
      }
      $existing = civicrm_api3(
        'OptionValue',
        'getcount',
        $params
      );
      if ($existing) {
        $this->setElementError('name', E::ts('Another Live Snippet with this name already exists.'));
      }

      // Validate name format.
      if (!preg_match('/^[a-z0-9_\,]+$/', $values['name'])) {
        $this->setElementError(
        'name',
        E::ts('The name may only contain lowercase letters, numbers and underscores.')
        );
      }
    }

    return parent::validate();
  }

  public function postProcess() {
    // Create/update/delete option value.
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE)) {
      $values = $this->exportValues();

      // Create/update/delete OptionValue.
      civicrm_api3(
        'OptionValue',
        $this->getApiAction(),
        [
          'id' => $this->option_value['id'] ?? NULL,
          'option_group_id' => $this->option_group_id,
          'label' => $values['label'],
          'name' => $values['name'],
          'description' => $values['description'],
        ]
      );
    }

    parent::postProcess();
  }

}
