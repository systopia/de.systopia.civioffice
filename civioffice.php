<?php

declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'civioffice.civix.php';
// phpcs:enable

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
function civioffice_civicrm_container(ContainerBuilder $container): void {
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

/**
 * Implements hook_civicrm_searchTasks().
 *
 * @phpstan-param array<string, array{"title": string, "class": string, "result": bool}> $tasks
 */
function civioffice_civicrm_searchTasks(string $objectType, array &$tasks): void {
  if (CRM_Core_Permission::check('access CiviOffice')) {
    switch ($objectType) {
      case 'contact':
        $tasks['civioffice'] = [
          'title' => E::ts('Create Documents (CiviOffice)'),
          'class' => 'CRM_Civioffice_Form_Task_CreateDocuments',
          'result' => FALSE,
        ];
        break;

      case 'contribution':
        $tasks['civioffice'] = [
          'title' => E::ts('Create Documents (CiviOffice)'),
          'class' => 'CRM_Civioffice_Form_Task_CreateContributionDocuments',
          'result' => FALSE,
        ];
        break;

      case 'event':
        $tasks['civioffice'] = [
          'title' => E::ts('Create Documents (CiviOffice)'),
          'class' => 'CRM_Civioffice_Form_Task_CreateParticipantDocuments',
          'result' => FALSE,
        ];
        break;

      case 'membership':
        $tasks['civioffice'] = [
          'title' => E::ts('Create Documents (CiviOffice)'),
          'class' => 'CRM_Civioffice_Form_Task_CreateMembershipDocuments',
          'result' => FALSE,
        ];
        break;

      case 'activity':
        $tasks['civioffice'] = [
          'title' => E::ts('Create Documents (CiviOffice)'),
          'class' => 'CRM_Civioffice_Form_Task_CreateActivityDocuments',
          'result' => FALSE,
        ];
        break;

      case 'case':
        $tasks['civioffice'] = [
          'title' => E::ts('Create Documents (CiviOffice)'),
          'class' => 'CRM_Civioffice_Form_Task_CreateCaseDocuments',
          'result' => FALSE,
        ];
        break;
    }
  }
}

/**
 * Implements hook_civicrm_summaryActions().
 *
 * @phpstan-param array<string, array<string, mixed>> $actions
 */
function civioffice_civicrm_summaryActions(array &$actions, ?int $contactID): void {
  // add "open document with single contact" action
  $actions['open_document_with_single_contact'] = [
    'ref' => 'civioffice-render-single',
    'title' => E::ts('Create CiviOffice document'),
    // to the top!
    'weight' => -110,
    'key' => 'open_document_with_single_contact',
    'class' => 'medium-popup',
    // fixme contact id is passed twice as pid
    'href' => CRM_Utils_System::url('civicrm/civioffice/document_from_single_contact', 'reset=1'),
    'permissions' => ['access CiviOffice'],
  ];
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function civioffice_civicrm_config(\CRM_Core_Config &$config): void {
  _civioffice_composer_autoload();
  _civioffice_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_permission().
 *
 * @phpstan-param array<string, array<string, mixed>> $permissions
 */
function civioffice_civicrm_permission(array &$permissions): void {
  $permissions['access CiviOffice'] = [
    'label' => E::ts('Access CiviOffice'),
    'description' => E::ts('Create documents using CiviOffice.'),
  ];
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 *
 * @phpstan-param array<string, array<string, mixed>> $menu
 * @param-out array<string, array<string, mixed>> $menu
 */
function civioffice_civicrm_navigationMenu(array &$menu): void {
  // @phpstan-ignore paramOut.type
  _civioffice_civix_insert_navigation_menu($menu, 'Administer/Communications', [
    'label' => E::ts('CiviOffice Settings'),
    'name' => 'civioffice_settings',
    'url' => 'civicrm/admin/civioffice/settings',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
    'icon' => 'crm-i fa-file-text',
  ]);
  // @phpstan-ignore paramOut.type
  _civioffice_civix_insert_navigation_menu($menu, '', [
    'label' => E::ts('CiviOffice'),
    'name' => 'civioffice',
    'permission' => 'access CiviOffice',
    'operator' => 'OR',
    'separator' => 0,
    'icon' => 'crm-i fa-file-text',
  ]);
  // @phpstan-ignore paramOut.type
  _civioffice_civix_insert_navigation_menu($menu, 'civioffice', [
    'label' => E::ts('CiviOffice Settings'),
    'name' => 'civioffice_settings',
    'url' => 'civicrm/admin/civioffice/settings',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
    'icon' => 'crm-i fa-cogs',
  ]);
  // @phpstan-ignore paramOut.type
  _civioffice_civix_insert_navigation_menu($menu, 'civioffice', [
    'label' => E::ts('Upload Documents'),
    'name' => 'civioffice_document_upload',
    'url' => 'civicrm/civioffice/document_upload',
    'permission' => 'access CiviOffice',
    'operator' => 'OR',
    'separator' => 0,
    'icon' => 'crm-i fa-upload',
  ]);
  // @phpstan-ignore paramOut.type
  _civioffice_civix_insert_navigation_menu($menu, 'civioffice', [
    'label' => E::ts('Available Tokens'),
    'name' => 'civioffice_tokens',
    'url' => 'civicrm/civioffice/tokens',
    'permission' => 'access CiviOffice',
    'operator' => 'OR',
    'separator' => 0,
    'icon' => 'crm-i fa-code',
  ]);
  _civioffice_civix_navigationMenu($menu);
}
