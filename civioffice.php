<?php
declare(strict_types = 1);

require_once 'civioffice.civix.php';

use CRM_Civioffice_ExtensionUtil as E;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;

function _civioffice_composer_autoload(): void {
  if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    $classLoader = require_once __DIR__ . '/vendor/autoload.php';
    if ($classLoader instanceof \Composer\Autoload\ClassLoader) {
      // Re-register class loader to append it. (It's automatically prepended.)
      $classLoader->unregister();
      $classLoader->register();
    }
  }
}

/**
 * Implements hook_civicrm_container().
 */
function civioffice_civicrm_container(ContainerBuilder $container): void
{
    _civioffice_composer_autoload();

    $globResource = new GlobResource(__DIR__ . '/services', '/*.php', FALSE);
    // Container will be rebuilt if a *.php file is added to services
    $container->addResource($globResource);
    foreach ($globResource->getIterator() as $path => $info) {
        // Container will be rebuilt if file changes
        $container->addResource(new FileResource($path));
        require $path;
    }

    if (function_exists('_civioffice_test_civicrm_container')) {
        // Allow to use different services in tests.
        _civioffice_test_civicrm_container($container);
    }
}

function civioffice_civicrm_searchTasks($objectType, &$tasks)
{
    switch ($objectType) {
        case 'contact':
            $tasks['civioffice'] = [
                'title' => E::ts('Create Documents (CiviOffice)'),
                'class' => 'CRM_Civioffice_Form_Task_CreateDocuments',
                'result' => false,
            ];
            break;
        case 'contribution':
            $tasks['civioffice'] = [
                'title' => E::ts('Create Documents (CiviOffice)'),
                'class' => 'CRM_Civioffice_Form_Task_CreateContributionDocuments',
                'result' => false,
            ];
            break;
        case 'event':
            $tasks['civioffice'] = [
                'title' => E::ts('Create Documents (CiviOffice)'),
                'class' => 'CRM_Civioffice_Form_Task_CreateParticipantDocuments',
                'result' => false,
            ];
            break;
        case 'membership':
            $tasks['civioffice'] = [
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
    _civioffice_composer_autoload();
    _civioffice_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_permission().
 */
function civioffice_civicrm_permission(&$permissions) {
    $permissions['access CiviOffice'] = [
        'label' => E::ts('Access CiviOffice'),
        'description' => E::ts('Create documents using CiviOffice.'),
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
        'permission' => 'access CiviOffice',
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
