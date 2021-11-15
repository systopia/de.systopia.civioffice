<?php

require_once 'civioffice.civix.php';

// phpcs:disable
use CRM_Civioffice_ExtensionUtil as E;

// phpcs:enable

function civioffice_civicrm_searchTasks($objectType, &$tasks)
{
    if ($objectType == 'contact')
    {
        $tasks[] = [
            'title' => E::ts('Create Documents (CiviOffice)'),
            'class' => 'CRM_Civioffice_Form_Task_CreateDocuments',
            'result' => false
        ];
    }
}

function civioffice_civicrm_summaryActions(&$actions, $contactID)
{
    // add "open document with single contact" action
    if (CRM_Core_Permission::check('administer CiviCRM')) // todo correct?
    {
        $actions['open_document_with_single_contact'] = [
            'ref'         => 'civioffice-render-single',
            'title'       => E::ts('Create CiviOffice document'),
            'weight'      => -110, // to the top!
            'key'         => 'open_document_with_single_contact',
            'class'       => 'medium-popup',
            'href'        => CRM_Utils_System::url('civicrm/civioffice/document_from_single_contact', "reset=1"), // fixme contact id is passed twice as pid
            'permissions' => ['view all contacts']
        ];
    }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function civioffice_civicrm_config(&$config)
{
    _civioffice_civix_civicrm_config($config);

    \Civi::dispatcher()->addSubscriber(new CRM_Civioffice_AttachmentProvider());
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function civioffice_civicrm_xmlMenu(&$files)
{
    _civioffice_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function civioffice_civicrm_install()
{
    _civioffice_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function civioffice_civicrm_postInstall()
{
    _civioffice_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function civioffice_civicrm_uninstall()
{
    _civioffice_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function civioffice_civicrm_enable()
{
    _civioffice_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function civioffice_civicrm_disable()
{
    _civioffice_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function civioffice_civicrm_upgrade($op, CRM_Queue_Queue $queue = null)
{
    return _civioffice_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function civioffice_civicrm_managed(&$entities)
{
    _civioffice_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function civioffice_civicrm_caseTypes(&$caseTypes)
{
    _civioffice_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function civioffice_civicrm_angularModules(&$angularModules)
{
    _civioffice_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function civioffice_civicrm_alterSettingsFolders(&$metaDataFolders = null)
{
    _civioffice_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function civioffice_civicrm_entityTypes(&$entityTypes)
{
    _civioffice_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function civioffice_civicrm_themes(&$themes)
{
    _civioffice_civix_civicrm_themes($themes);
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
//function civioffice_civicrm_navigationMenu(&$menu) {
//  _civioffice_civix_insert_navigation_menu($menu, 'Mailings', array(
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ));
//  _civioffice_civix_navigationMenu($menu);
//}
