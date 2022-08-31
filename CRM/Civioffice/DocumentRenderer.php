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

use CRM_Civioffice_ExtensionUtil as E;

class CRM_Civioffice_DocumentRenderer extends CRM_Civioffice_OfficeComponent
{
    protected CRM_Civioffice_DocumentRendererType $type;

    protected array $configuration;

    /**
     * @param string $uri
     * @param string $name
     * @param string $type
     * @param array $configuration
     */
    public function __construct(string $uri, string $name, array $configuration)
    {
        parent::__construct($uri, $name);
        $this->configuration = $configuration;
        $this->type = CRM_Civioffice_DocumentRendererType::create($configuration['type']);
    }

    /**
     * @return \CRM_Civioffice_DocumentRendererType
     */
    public function getType(): CRM_Civioffice_DocumentRendererType
    {
        return $this->type;
    }

    /**
     * @param \CRM_Civioffice_DocumentRendererType $type
     */
    public function setType(CRM_Civioffice_DocumentRendererType $type): void
    {
        $this->type = $type;
    }

    /**
     * @inheritDoc
     */
    public function getConfigPageURL(): string
    {
        // TODO: Implement getConfigPageURL() method. This will hold extra config for Live Snippets and DOCX preparation.
    }

    /**
     * @inheritDoc
     */
    public function isReady(): bool
    {
        return $this->type->isReady();
    }

    public function render(
        $document_with_placeholders,
        array $entity_ids,
        CRM_Civioffice_DocumentStore_LocalTemp $temp_store,
        string $target_mime_type,
        string $entity_type = 'contact',
        array $live_snippets = []
    ) {
        return $this->type->render(
            $document_with_placeholders,
            $entity_ids,
            $temp_store,
            $target_mime_type,
            $entity_type,
            $live_snippets,
            $this->configuration
        );
    }

    public static function load($uri): ?CRM_Civioffice_DocumentRenderer
    {
        if ($configuration = Civi::settings()->get('civioffice_renderer_' . $uri)) {
            $renderer = new self(
                $uri,
                $configuration['name'],
                $configuration
            );
        }
        return $renderer ?? null;

    }

    public function save() {
        Civi::settings()->set('civioffice_renderer_' . $this->getURI(), $this->configuration);
        if (!array_key_exists($this->getURI(), $renderer_names = Civi::settings()->get('civioffice_renderers'))) {
            $renderer_names[$this->getURI()] = $this->getName();
            Civi::settings()->set('civioffice_renderers', $renderer_names);
        }
    }

    /**
     * @throws \Exception
     *   When the renderer type does not support a configuration item with the given name.
     */
    public function get($configurationItemName) {
        $this->type->checkConfigurationSupported($configurationItemName);
        return $this->configuration[$configurationItemName];
    }

    /**
     * @throws \Exception
     *   When the renderer type does not support a configuration item with the given name.
     */
    public function set($configurationItemName, $value) {
        $this->type->checkConfigurationSupported($configurationItemName);
        $this->configuration[$configurationItemName] = $value;
    }
}
