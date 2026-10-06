<?php
use CRM_Civioffice_ExtensionUtil as E;

return [
  'name' => 'CiviofficeDocumentEditor',
  'table' => 'civicrm_civioffice_document_editor',
  'class' => 'CRM_Civioffice_DAO_CiviofficeDocumentEditor',
  'getInfo' => fn() => [
    'title' => E::ts('CiviofficeDocumentEditor'),
    'title_plural' => E::ts('CiviofficeDocumentEditors'),
    'description' => E::ts('Configurations of CiviOffice Document Editors.'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique CiviofficeDocumentEditor ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => E::ts('Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('The name of the editor.'),
    ],
    'is_active' => [
      'title' => E::ts('Enabled'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => E::ts('Is the editor enabled?'),
    ],
    'file_extensions' => [
      'title' => E::ts('File Extensions'),
      'sql_type' => 'text',
      'input_type' => 'Text',
      'serialize' => \CRM_Core_DAO::SERIALIZE_JSON,
      'required' => TRUE,
      'description' => E::ts('File extensions that are handled by this editor.'),
    ],
    'type' => [
      'title' => E::ts('Type'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'required' => TRUE,
      'pseudoconstant' => [
        'callback' => fn() => \Civi\Civioffice\DocumentEditorTypeContainer::getInstance()->getTitles(),
      ],
      'description' => E::ts('The type of the editor.'),
    ],
    'type_config' => [
      'title' => E::ts('Configuration'),
      'sql_type' => 'text',
      'serialize' => \CRM_Core_DAO::SERIALIZE_JSON,
      'input_type' => 'TextArea',
      'required' => TRUE,
      'description' => E::ts('The configuration for the specified editor type.'),
    ],
  ],
  'getIndices' => fn() => [],
  'getPaths' => fn() => [],
];
