<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
 *
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

use Civi\Civioffice\DocumentEditor;
use Civi\Civioffice\DocumentEditorManager;
use Civi\Civioffice\DocumentEditorTypeContainer;
use Civi\Civioffice\DocumentEditorTypeInterface;
use CRM_Civioffice_ExtensionUtil as E;

final class CRM_Civioffice_Form_DocumentEditorSettings extends \CRM_Core_Form {

  // @phpstan-ignore property.uninitialized
  private DocumentEditor $documentEditor;

  // @phpstan-ignore property.uninitialized
  private DocumentEditorTypeInterface $documentEditorType;

  /**
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {
    // Restrict supported actions.
    if (!$this->isAction(\CRM_Core_Action::UPDATE, \CRM_Core_Action::ADD, \CRM_Core_Action::DELETE)) {
      throw new \RuntimeException(E::ts('Invalid action.'));
    }

    // Require ID for editing/deleting.
    if ($this->isAction(\CRM_Core_Action::UPDATE, \CRM_Core_Action::DELETE)) {
      $id = \CRM_Utils_Request::retrieve('id', 'Integer', $this);
      if (!is_int($id)) {
        throw new \RuntimeException('Document editor ID missing');
      }

      $this->documentEditor = DocumentEditorManager::getInstance()->getEditor($id);
      $this->documentEditorType = $this->documentEditor->getType();
    }

    // Require type for adding.
    if ($this->isAction(\CRM_Core_Action::ADD)) {
      $type = \CRM_Utils_Request::retrieve('type', 'Alphanumeric', $this);
      if (!is_string($type)) {
        throw new \RuntimeException('Document editor type is missing');
      }
      $this->documentEditorType = DocumentEditorTypeContainer::getInstance()->get($type);
    }

    // Make sure to redirect to the CiviOffice settings page.
    \CRM_Core_Session::singleton()->replaceUserContext(
      \CRM_Utils_System::url(
        'civicrm/admin/civioffice/settings',
        'reset=1'
      )
    );
  }

  public function buildQuickForm(): void {
    if ($this->isAction(\CRM_Core_Action::UPDATE, \CRM_Core_Action::ADD)) {
      $this->add(
        'text',
        'name',
        E::ts('Name'),
        ['class' => 'huge'],
        TRUE
      );
      $this->add(
        'checkbox',
        'active',
        E::ts('Enabled'),
      );
      $this->add(
        'text',
        'file_extensions',
        E::ts('File Extensions'),
        ['class' => 'huge'],
        FALSE,
      );
      if (isset($this->documentEditor)) {
        $this->setDefaults(
          [
            'name' => $this->documentEditor->getName(),
            'active' => $this->documentEditor->isActive(),
            'file_extensions' => implode(' ', $this->documentEditor->getFileExtensions()),
          ] + $this->documentEditor->getTypeConfig()
        );
      }
      else {
        $this->setDefaults(['active' => TRUE] + $this->documentEditorType->getDefaultConfiguration());
      }

      $this->documentEditorType->buildSettingsForm($this);
      $this->assign('editorTypeSettingsTemplate', $this->documentEditorType->getSettingsFormTemplate());
    }

    $this->addButtons(
      [
        [
          'type' => 'submit',
          'name' => $this->isAction(\CRM_Core_Action::DELETE) ? E::ts('Delete') : E::ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
  }

  /**
   * Validate input data
   * This method is executed before postProcess()
   * @return bool
   */
  public function validate(): bool {
    if ($this->isAction(\CRM_Core_Action::UPDATE, \CRM_Core_Action::ADD)) {
      $this->documentEditorType->validateSettingsForm($this, (bool) $this->getSubmitValue('active'));
    }

    return parent::validate();
  }

  public function postProcess(): void {
    $editorManager = DocumentEditorManager::getInstance();
    if ($this->isAction(\CRM_Core_Action::UPDATE, \CRM_Core_Action::ADD)) {
      $typeConfig = $this->documentEditorType->postProcessSettingsForm($this);
      $values = $this->exportValues();
      $active = (bool) ($values['active'] ?? FALSE);
      $fileExtensions = explode(' ', $values['file_extensions'] ?? '');
      $fileExtensions = array_values(array_filter($fileExtensions, fn (string $extension) => '' !== $extension));
      $fileExtensions = array_map(fn (string $extension) => '.' . ltrim($extension, '.'), $fileExtensions);

      if (!isset($this->documentEditor)) {
        $editorManager->saveNewEditor(
          $values['name'],
          $active,
          $fileExtensions,
          $this->documentEditorType,
          $typeConfig
        );
      }
      else {
        $this->documentEditor->setName($values['name']);
        $this->documentEditor->setActive($active);
        $this->documentEditor->setFileExtensions($fileExtensions);
        $this->documentEditor->setTypeConfig($typeConfig);
        $editorManager->saveEditor($this->documentEditor);
      }
    }
    elseif ($this->isAction(\CRM_Core_Action::DELETE)) {
      $editorManager->deleteEditor($this->documentEditor);
    }
  }

  private function isAction(int ...$actions): bool {
    return in_array($this->getAction(), $actions, TRUE);
  }

}
