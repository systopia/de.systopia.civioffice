<?php
declare(strict_types = 1);

/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: J. Franz (franz@systopia.de)                   |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

/**
 * CiviOffice Document Renderer
 */
abstract class CRM_Civioffice_DocumentRendererType extends CRM_Civioffice_OfficeComponent {

  /**
   * @phpstan-var array<string, mixed>
   */
  protected array $configuration;

  /**
   * @phpstan-param array<string, mixed> $configuration
   */
  public function __construct(?string $uri = NULL, ?string $name = NULL, array $configuration = []) {
    parent::__construct($uri, $name);
    $this->configuration = $configuration;
    foreach (static::supportedConfiguration() as $config_item) {
      if (property_exists($this, $config_item)) {
        $this->{$config_item} = $configuration[$config_item] ?? NULL;
      }
    }
  }

  /**
   * @phpstan-param array<string, mixed> $configuration
   *   The configuration for the Document Renderer Type.
   *
   * @return \CRM_Civioffice_DocumentRendererType
   *   The document renderer type object.
   *
   * @throws \InvalidArgumentException
   *   When the given document renderer type does not exist.
   */
  public static function create(string $type, array $configuration = []): CRM_Civioffice_DocumentRendererType {
    $types = CRM_Civioffice_Configuration::getDocumentRendererTypes();
    if (!isset($types[$type]) || !class_exists($types[$type]['class'])) {
      throw new InvalidArgumentException("Document renderer type $type does not exist.");
    }
    return new $types[$type]['class'](NULL, NULL, $configuration);
  }

  abstract public function buildSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form): void;

  abstract public static function getSettingsFormTemplate(): string;

  abstract public function validateSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form): void;

  abstract public function postProcessSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form): void;

  public static function getNextUri(): string {
    return (new static())->getURI() . '-' . count(CRM_Civioffice_Configuration::getDocumentRenderers());
  }

  /**
   * Get a list of document MIME types supported by this component
   *
   * @phpstan-return list<string>
   *   list of MIME types as strings
   */
  abstract public function getSupportedInputMimeTypes(): array;

  /**
   * Get the output/generated MIME types for this document renderer
   *
   * @phpstan-return list<string>
   *   list of MIME types
   */
  abstract public function getSupportedOutputMimeTypes(): array;

  abstract public function render(string $inputFile, string $outputFile, string $mimeType): void;

  /**
   * @phpstan-return list<string>
   */
  abstract public static function supportedConfiguration(): array;

  /**
   * @phpstan-return array<string, mixed>
   */
  abstract public static function defaultConfiguration(): array;

  public static function supportsConfigurationItem(string $configurationItem): bool {
    return in_array($configurationItem, static::supportedConfiguration(), TRUE);
  }

  /**
   * @throws \RuntimeException
   *   When the renderer type does not support a configuration item with the given name.
   */
  public function checkConfigurationSupported(string $configurationItem): void {
    if (!static::supportsConfigurationItem($configurationItem)) {
      throw new RuntimeException(sprintf(
        'Document renderer type %s does not support configuration item %s',
        $this->getName(),
        $configurationItem
      ));
    }
  }

}
