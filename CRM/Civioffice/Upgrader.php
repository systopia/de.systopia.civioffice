<?php

use CRM_Civioffice_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Civioffice_Upgrader extends CRM_Extension_Upgrader_Base
{
    /**
     * Run installation tasks.
     */
    public function install()
    {
        // Create/synchronise the Live Snippets option group.
        $customData = new CRM_Civioffice_CustomData(E::LONG_NAME);
        $customData->syncOptionGroup(E::path('resources/live_snippets_option_group.json'));

        // Create a default instance for each renderer type.
        foreach (CRM_Civioffice_Configuration::getDocumentRendererTypes() as $uri => $type) {
            $renderer = new CRM_Civioffice_DocumentRenderer(
                $uri,
                $type['label'],
                $type['class']::defaultConfiguration() + ['type' => $uri]
            );
            $renderer->save();
        }
    }

    /**
     * Example: Run an external SQL script when the module is uninstalled.
     */
    public function uninstall()
    {
        // Remove civioffice_live_snippets option group.
        \Civi\Api4\OptionGroup::delete(false)
            ->addWhere('name', '=', 'civioffice_live_snippets')
            ->execute();
        // TODO: Revert contact settings for each live snippet.
//        Civi::contactSettings()->revert('civioffice.live_snippets.' . $name);

        // Remove settings created by this extension.
        Civi::settings()->revert(CRM_Civioffice_DocumentStore_Local::LOCAL_TEMP_PATH_SETTINGS_KEY);
        Civi::settings()->revert(CRM_Civioffice_DocumentStore_Local::LOCAL_STATIC_PATH_SETTINGS_KEY);
        Civi::settings()->revert(CRM_Civioffice_DocumentStore_Upload::UPLOAD_PRIVATE_ENABLED_SETTINGS_KEY);
        Civi::settings()->revert(CRM_Civioffice_DocumentStore_Upload::UPLOAD_PUBLIC_ENABLED_SETTINGS_KEY);
        // TODO: Revert contact settings.
//        Civi::contactSettings()->revert(CRM_Civioffice_Form_DocumentFromSingleContact::UNOCONV_CREATE_SINGLE_ACTIVIY_ATTACHMENT);
//        Civi::contactSettings()->revert(CRM_Civioffice_Form_DocumentFromSingleContact::UNOCONV_CREATE_SINGLE_ACTIVIY_TYPE);

        // Revert renderer settings.
        foreach (Civi::settings()->get('civioffice_renderers') as $renderer_uri => $renderer_name) {
            Civi::settings()->revert('civioffice_renderer_' . $renderer_uri);
        }
        Civi::settings()->revert('civioffice_renderers');

        // TODO: Clean-up file cache (rendered files), using a cleanup interface.
    }

    /**
     * Support Live Snippets.
     *
     * @return TRUE on success
     * @throws Exception
     */
    public function upgrade_0006(): bool
    {
        // Create/synchronise the Live Snippets option group.
        $this->ctx->log->info('Create/synchronise Live Snippets option group.');
        $customData = new CRM_Civioffice_CustomData(E::LONG_NAME);
        $customData->syncOptionGroup(E::path('resources/live_snippets_option_group.json'));
        return true;
    }

    public function upgrade_0007(): bool
    {
        // Create default renderer instances (one for each RendererType with the same URI) and save them with current
        // settings.
        $renderers = [
            'unoconv-local' => new CRM_Civioffice_DocumentRenderer(
                'unoconv-local',
                E::ts('Local Universal Office Converter (unoconv)'),
                [
                    'type' => 'unoconv-local',
                    'unoconv_binary_path' => Civi::settings()->get('civioffice_unoconv_binary_path'),
                    'unoconv_lock_file_path' => Civi::settings()->get('civioffice_unoconv_lock_file'),
                    'temp_folder_path' => Civi::settings()->get('civioffice_temp_folder_path'),
                    'prepare_docx' => false,
                ]
            ),
            'unoconv-local-phpword' => new CRM_Civioffice_DocumentRenderer(
                'unoconv-local-phpword',
                E::ts('Local Universal Office Converter (unoconv) implementing PhpWord'),
                [
                    'type' => 'unoconv-local-phpword',
                    'unoconv_binary_path' => Civi::settings()->get('civioffice_unoconv_binary_path'),
                    'unoconv_lock_file_path' => Civi::settings()->get('civioffice_unoconv_lock_file'),
                    'temp_folder_path' => Civi::settings()->get('civioffice_temp_folder_path'),
                    'prepare_docx' => false,
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

        return true;
    }
}
