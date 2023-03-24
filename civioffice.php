<?php

require_once 'civioffice.civix.php';

// phpcs:disable
use CRM_Civioffice_ExtensionUtil as E;

// phpcs:enable

function civioffice_civicrm_searchTasks($objectType, &$tasks)
{
    switch ($objectType) {
        case 'contact':
            $tasks[] = [
                'title' => E::ts('Create Documents (CiviOffice)'),
                'class' => 'CRM_Civioffice_Form_Task_CreateDocuments',
                'result' => false,
            ];
            break;
        case 'contribution':
            $tasks[] = [
                'title' => E::ts('Create Documents (CiviOffice)'),
                'class' => 'CRM_Civioffice_Form_Task_CreateContributionDocuments',
                'result' => false,
            ];
            break;
        case 'event':
            $tasks[] = [
                'title' => E::ts('Create Documents (CiviOffice)'),
                'class' => 'CRM_Civioffice_Form_Task_CreateParticipantDocuments',
                'result' => false,
            ];
            break;
        case 'membership':
            $tasks[] = [
                'title' => E::ts('Create Documents (CiviOffice)'),
                'class' => 'CRM_Civioffice_Form_Task_CreateMembershipDocuments',
                'result' => false,
            ];
            break;
        case 'activity':
            $tasks['civioffice'] = [
                'title' => E::ts('Create Documents (CiviOffice)'),
                'class' => 'CRM_Civioffice_Form_Task_CreateActivityDocuments',
                'result' => false,
            ];
            break;
        case 'case':
            $tasks['civioffice'] = [
                'title' => E::ts('Create Documents (CiviOffice)'),
                'class' => 'CRM_Civioffice_Form_Task_CreateCaseDocuments',
                'result' => false,
            ];
    }
}

function civioffice_civicrm_summaryActions(&$actions, $contactID)
{
    // add "open document with single contact" action
    $actions['open_document_with_single_contact'] = [
        'ref'         => 'civioffice-render-single',
        'title'       => E::ts('Create CiviOffice document'),
        'weight'      => -110, // to the top!
        'key'         => 'open_document_with_single_contact',
        'class'       => 'medium-popup',
        'href'        => CRM_Utils_System::url('civicrm/civioffice/document_from_single_contact', "reset=1"), // fixme contact id is passed twice as pid
        'permissions' => ['access CiviOffice']
    ];
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function civioffice_civicrm_config(&$config)
{
    _civioffice_civix_civicrm_config($config);

    \Civi::dispatcher()->addSubscriber(new CRM_Civioffice_Tokens('civioffice'));

    if (interface_exists('\Civi\Mailattachment\AttachmentType\AttachmentTypeInterface')) {
        \Civi::dispatcher()->addSubscriber(new CRM_Civioffice_AttachmentProvider());
    }

    Civi::dispatcher()->addSubscriber(new CRM_Civioffice_Configuration());
}

/**
 * Implements hook_civicrm_permission().
 */
function civioffice_civicrm_permission(&$permissions) {
    $permissions['access CiviOffice'] = [
        E::ts('Access CiviOffice'),
        E::ts('Create documents using CiviOffice.'),
    ];
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function civioffice_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
function civioffice_civicrm_navigationMenu(&$menu) {
    _civioffice_civix_insert_navigation_menu($menu, 'Administer/Communications', array(
        'label' => E::ts('CiviOffice Settings'),
        'name' => 'civioffice_settings',
        'url' => 'civicrm/admin/civioffice/settings',
        'permission' => 'administer CiviCRM',
        'operator' => 'OR',
        'separator' => 0,
        'icon' => 'crm-i fa-file-text',
    ));
    _civioffice_civix_insert_navigation_menu($menu, '', array(
        'label' => E::ts('CiviOffice'),
        'name' => 'civioffice',
        'operator' => 'OR',
        'separator' => 0,
        'icon' => 'crm-i fa-file-text',
    ));
    _civioffice_civix_insert_navigation_menu($menu, 'civioffice', array(
        'label' => E::ts('CiviOffice Settings'),
        'name' => 'civioffice_settings',
        'url' => 'civicrm/admin/civioffice/settings',
        'permission' => 'administer CiviCRM',
        'operator' => 'OR',
        'separator' => 0,
        'icon' => 'crm-i fa-cogs',
    ));
    _civioffice_civix_insert_navigation_menu($menu, 'civioffice', array(
        'label' => E::ts('Upload Documents'),
        'name' => 'civioffice_document_upload',
        'url' => 'civicrm/civioffice/document_upload',
        'permission' => 'access CiviOffice',
        'operator' => 'OR',
        'separator' => 0,
        'icon' => 'crm-i fa-upload',
    ));
    _civioffice_civix_insert_navigation_menu($menu, 'civioffice', array(
        'label' => E::ts('Available Tokens'),
        'name' => 'civioffice_tokens',
        'url' => 'civicrm/civioffice/tokens',
        'permission' => 'access CiviOffice',
        'operator' => 'OR',
        'separator' => 0,
        'icon' => 'crm-i fa-code',
    ));
  _civioffice_civix_navigationMenu($menu);
}
