<?php

use CRM_Civioffice_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Civioffice_Upgrader extends CRM_Civioffice_Upgrader_Base
{
    /**
     * Run installation tasks.
     */
    public function install()
    {
        // Create/synchronise the Live Snippets option group.
        $customData = new CRM_Civioffice_CustomData(E::LONG_NAME);
        $customData->syncOptionGroup(E::path('resources/live_snippets_option_group.json'));
    }

    /**
     * Example: Run an external SQL script when the module is uninstalled.
     */
    public function uninstall()
    {
        // TODO: Remove civioffice_live_snippets option group.
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
}
