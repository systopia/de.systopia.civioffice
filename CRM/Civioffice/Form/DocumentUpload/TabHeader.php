<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2022 SYSTOPIA                            |
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

/**
 * Helper class to build navigation links
 */
class CRM_Civioffice_Form_DocumentUpload_TabHeader {

  /**
   * @param \CRM_Civioffice_Form_DocumentUpload $form
   *
   * @return array
   * @throws Exception
   */
  public static function build(&$form) {
    $tabs = $form->get('tabHeader');
    if (!$tabs || empty($_GET['reset'])) {
      $tabs = self::process($form);
      $form->set('tabHeader', $tabs);
    }
    if (method_exists(CRM_Core_Smarty::class, 'setRequiredTabTemplateKeys')) {
      $tabs = \CRM_Core_Smarty::setRequiredTabTemplateKeys($tabs);
    }
    $form->assign('tabHeader', $tabs);
    CRM_Core_Resources::singleton()
      ->addScriptFile(
        'civicrm',
        'templates/CRM/common/TabHeader.js',
        1,
        'html-header'
      )
      ->addSetting([
        'tabSettings' => [
          'active' => self::getCurrentTab($tabs),
        ],
      ]);
    return $tabs;
  }

  /**
   * @param CRM_Civioffice_Form_DocumentUpload $form
   *
   * @return array<string, array<string, mixed>>
   * @throws Exception
   */
  public static function process(&$form): array {
    $default = [
      'link' => NULL,
      'valid' => TRUE,
      'active' => TRUE,
      'current' => FALSE,
      'icon' => FALSE,
    ];
    $tabs = [];
    if ((new CRM_Civioffice_DocumentStore_Upload(FALSE))->isReady()) {
      $tabs['private'] = [
        'title' => E::ts('My Documents'),
        'link' => CRM_Utils_System::url(
          'civicrm/civioffice/document_upload',
          'common=0'
        ),
        'icon' => 'crm-i fa-user',
      ] + $default;
    }
    if ((new CRM_Civioffice_DocumentStore_Upload(TRUE))->isReady()) {
      $tabs['shared'] = [
        'title' => E::ts('Shared Documents'),
        'link' => CRM_Utils_System::url(
        'civicrm/civioffice/document_upload',
        'common=1'
        ),
        'icon' => 'crm-i fa-users',
      ] + $default;
    }

    // Load requested tab.
    $current = CRM_Utils_Request::retrieve(
      'selectedChild',
      'Alphanumeric'
    ) ?? ($form->common ? 'shared' : 'private');
    if (isset($tabs[$current])) {
      $tabs[$current]['current'] = TRUE;
    }

    return $tabs;
  }

  /**
   * @param \CRM_Civioffice_Form_DocumentUpload $form
   */
  public static function reset(&$form) {
    $tabs = self::process($form);
    $form->set('tabHeader', $tabs);
  }

  /**
   * @param $tabs
   *
   * @return int|string
   */
  public static function getCurrentTab($tabs) {
    static $current = FALSE;

    if ($current) {
      return $current;
    }

    if (is_array($tabs)) {
      foreach ($tabs as $subPage => $pageVal) {
        if (CRM_Utils_Array::value('current', $pageVal) === TRUE) {
          $current = $subPage;
          break;
        }
      }
    }

    $current = $current ? $current : 'private';
    return $current;
  }

}
