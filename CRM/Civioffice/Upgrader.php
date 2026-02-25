<?php

declare(strict_types = 1);

use Civi\Civioffice\DocumentRenderer;
use CRM_Civioffice_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Civioffice_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Run installation tasks.
   */
  public function install(): void {
    Civi::settings()->set(
        CRM_Civioffice_DocumentStore_Local::LOCAL_TEMP_PATH_SETTINGS_KEY,
        sys_get_temp_dir() . '/civioffice'
    );

    // Create/synchronise the Live Snippets option group.
    $customData = new CRM_Civioffice_CustomData(E::LONG_NAME);
    $customData->syncOptionGroup(E::path('resources/live_snippets_option_group.json'));
  }

  public function uninstall(): void {
    // Remove settings created by this extension.
    Civi::settings()->revert(CRM_Civioffice_DocumentStore_Local::LOCAL_TEMP_PATH_SETTINGS_KEY);
    Civi::settings()->revert(CRM_Civioffice_DocumentStore_Local::LOCAL_STATIC_PATH_SETTINGS_KEY);
    Civi::settings()->revert(CRM_Civioffice_DocumentStore_Upload::UPLOAD_PRIVATE_ENABLED_SETTINGS_KEY);
    Civi::settings()->revert(CRM_Civioffice_DocumentStore_Upload::UPLOAD_PUBLIC_ENABLED_SETTINGS_KEY);

    // Revert contact settings for each live snippet.
    $liveSnippetNames = \Civi\Api4\OptionValue::get(FALSE)
      ->addSelect('name')
      ->addWhere('option_group_id.name', '=', 'civioffice_live_snippets')
      ->execute()
      ->column('name');

    // Revert/delete contact settings.
    $contactSettingsLike = implode(
      ' OR ',
      [
        CRM_Civioffice_Form_DocumentFromSingleContact::UNOCONV_CREATE_SINGLE_ACTIVIY_ATTACHMENT,
        CRM_Civioffice_Form_DocumentFromSingleContact::UNOCONV_CREATE_SINGLE_ACTIVIY_TYPE,
        'civioffice_create_%_activity_type',
        'civioffice.create_%.activity_type_id',
      ]
      + array_map(fn($liveSnippetName) => "civioffice.live_snippets.{$liveSnippetName}", $liveSnippetNames)
    );
    // Civi::contactSettings()->revert() does not support reverting for all contacts.
    CRM_Core_DAO::executeQuery(
      <<<SQL
        DELETE FROM `civicrm_setting` WHERE `name` LIKE '{$contactSettingsLike}';
        SQL
    );

    // Remove "civioffice_live_snippets" option group.
    \Civi\Api4\OptionGroup::delete(FALSE)
      ->addWhere('name', '=', 'civioffice_live_snippets')
      ->execute();

    // Revert renderer settings.
    foreach ((array) Civi::settings()->get('civioffice_renderers') as $renderer_uri => $renderer_name) {
      Civi::settings()->revert('civioffice_renderer_' . $renderer_uri);
    }
    Civi::settings()->revert('civioffice_renderers');
    // TODO: Clean-up file cache (temporary rendered files), using a cleanup interface.
  }

  /**
   * Support Live Snippets.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0006(): bool {
    // Create/synchronise the Live Snippets option group.
    $this->ctx->log->info('Create/synchronise Live Snippets option group.');
    $customData = new CRM_Civioffice_CustomData(E::LONG_NAME);
    $customData->syncOptionGroup(E::path('resources/live_snippets_option_group.json'));
    return TRUE;
  }

  public function upgrade_0007(): bool {
    // Create default renderer instances (one for each RendererType with the same URI) and save them with current
    // settings.
    $renderers = [
      'unoconv-local' => new DocumentRenderer(
            'unoconv-local',
            E::ts('Local Universal Office Converter (unoconv)'),
            [
              'type' => 'unoconv-local',
              'unoconv_binary_path' => Civi::settings()->get('civioffice_unoconv_binary_path'),
              'unoconv_lock_file_path' => Civi::settings()->get('civioffice_unoconv_lock_file'),
            ]
      ),
      'unoconv-local-phpword' => new DocumentRenderer(
            'unoconv-local-phpword',
            E::ts('Local Universal Office Converter (unoconv) implementing PhpWord'),
            [
              'type' => 'unoconv-local',
              'unoconv_binary_path' => Civi::settings()->get('civioffice_unoconv_binary_path'),
              'unoconv_lock_file_path' => Civi::settings()->get('civioffice_unoconv_lock_file'),
            ]
      ),
    ];
    foreach ($renderers as $renderer) {
      $renderer->save();
    }

    // Copy temporary directory configuration from renderer to store, as this configuration option moved there.
    Civi::settings()->set(
        CRM_Civioffice_DocumentStore_Local::LOCAL_TEMP_PATH_SETTINGS_KEY,
        Civi::settings()->get('civioffice_temp_folder_path')
    );

    // Remove (revert) legacy settings.
    Civi::settings()->revert('civioffice_unoconv_binary_path');
    Civi::settings()->revert('civioffice_unoconv_lock_file');
    Civi::settings()->revert('civioffice_temp_folder_path');

    return TRUE;
  }

  public function upgrade_0008(): bool {
    self::fixSettingsValueSerialization();
    // Check for instances of the "unoconv-local-phpword" renderer type.
    foreach (Civi::settings()->get('civioffice_renderers') ?? [] as $renderer_uri => $renderer_name) {
      $configuration = Civi::settings()->get('civioffice_renderer_' . $renderer_uri);
      if ($configuration['type'] == 'unoconv-local-phpword') {
        $this->ctx->log->info(
          'Migrate "unoconv-local-phpword" renderer instance '
          . $renderer_name . ' to unified "unoconv-local" with PHPWord usage.'
        );
        $configuration['type'] = 'unoconv-local';
        Civi::settings()->set('civioffice_renderer_' . $renderer_uri, $configuration);
      }
    }
    return TRUE;
  }

  public function upgrade_0009(): bool {
    self::fixSettingsValueSerialization();
    // Drop "temp_folder_path" from unoconv renderer settings.
    foreach (Civi::settings()->get('civioffice_renderers') ?? [] as $renderer_uri => $renderer_name) {
      $configuration = Civi::settings()->get('civioffice_renderer_' . $renderer_uri);
      if (($configuration['type'] ?? NULL) === 'unoconv-local') {
        $this->ctx->log->info('Drop "temp_folder_path" from settings of renderer ' . $renderer_name . '.');
        unset($configuration['temp_folder_path']);
        Civi::settings()->set('civioffice_renderer_' . $renderer_uri, $configuration);
      }
    }

    return TRUE;
  }

  public function upgrade_0010(): bool {
    self::fixSettingsValueSerialization();
    return TRUE;
  }

  public function upgrade_0011(): bool {
    E::schema()->createEntityTable('schema/CiviofficeDocumentEditor.entityType.php');

    return TRUE;
  }

  /**
   * Convert JSON-formatted setting "civioffice_renderers" to PHP-serialized format.
   * The setting was wrongly defined as JSON-formatted in settings metadata while unerialization with a format other
   * than PHP-serialized only works with the API. Civi::settings() always expects and generates PHP-serialized values.
   */
  private static function fixSettingsValueSerialization(): void {
    $dao = \CRM_Core_DAO::executeQuery(
      \CRM_Utils_SQL_Select::from('civicrm_setting')
        ->select('value')
        ->where('name="civioffice_renderers"')
        ->toSQL()
    );
    while ($dao->fetch()) {
      $legacyValue = \CRM_Core_DAO::unSerializeField($dao->value, \CRM_Core_DAO::SERIALIZE_JSON);
      if (is_array($legacyValue)) {
        Civi::settings()->set('civioffice_renderers', $legacyValue);
      }
    }
  }

}
