<?php
/*
 * Copyright (C) 2022 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\Civioffice;

use Assert\Assertion;

final class DocumentRenderer extends \CRM_Civioffice_OfficeComponent {

  /**
   * @phpstan-var array<string, mixed>
   */
  private array $configuration;

  private DocumentRendererTypeInterface $type;

  /**
   * @phpstan-param array<string, mixed> $configuration
   */
  public function __construct(string $uri, string $name, array $configuration) {
    parent::__construct($uri, $name);
    $type = $configuration['type'] ?? NULL;
    Assertion::string($type, 'Renderer type missing in configuration');
    $this->type = DocumentRendererTypeContainer::getInstance()->get($type);
    $this->configuration = $configuration + $this->type->getDefaultConfiguration();
  }

  public function getType(): DocumentRendererTypeInterface {
    return $this->type;
  }

  public function getTypeName(): string {
    return $this->type::getName();
  }

  /**
   * @inheritDoc
   */
  public function getConfigPageURL(): string {
    return \CRM_Utils_System::url(
        'civicrm/admin/civioffice/settings/renderer',
        'id=' . $this->uri . '&action=update'
    );
  }

  public function getDeleteURL(): string {
    return \CRM_Utils_System::url(
        'civicrm/admin/civioffice/settings/renderer',
        'id=' . $this->uri . '&action=delete'
    );
  }

  public function getDescription(): string {
    return $this->type::getTitle();
  }

  /**
   * @phpstan-return list<string>
   */
  public function getSupportedInputMimeTypes(): array {
    return $this->type->getSupportedInputMimeTypes($this->configuration);
  }

  /**
   * @phpstan-return list<string>
   */
  public function getSupportedOutputMimeTypes(): array {
    return $this->type->getSupportedOutputMimeTypes($this->configuration);
  }

  /**
   * @inheritDoc
   */
  public function isReady(): bool {
    return $this->type->isReady($this->configuration);
  }

  public function render(string $inputFile, string $outputFile, string $mimeType): void {
    if (!in_array($mimeType, $this->type->getSupportedOutputMimeTypes($this->configuration), TRUE)) {
      throw new \InvalidArgumentException(
        sprintf('Output MIME type "%s" is not supported by renderer type "%s"', $mimeType, $this->type::getTitle())
      );
    }

    $this->type->render($this->configuration, $inputFile, $outputFile, $mimeType);
  }

  /**
   * @throws \InvalidArgumentException
   */
  public static function load(string $uri): self {
    /** @phpstan-var array<string, string> $rendererList */
    $rendererList = \Civi::settings()->get('civioffice_renderers') ?? [];
    /** @phpstan-var array<string, mixed>|null $configuration */
    $configuration = \Civi::settings()->get('civioffice_renderer_' . $uri);
    if (!isset($rendererList[$uri]) || NULL === $configuration) {
      throw new \InvalidArgumentException(sprintf('Could not load renderer configuration with name %s', $uri));
    }

    return new self($uri, $rendererList[$uri], $configuration);
  }

  public function save(): void {
    $configuration = ['type' => $this->type::getName()] + $this->configuration;
    \Civi::settings()->set('civioffice_renderer_' . $this->uri, $configuration);
    /** @phpstan-var array<string, string> $rendererList */
    $rendererList = \Civi::settings()->get('civioffice_renderers') ?? [];
    $rendererList[$this->uri] = $this->name;
    \Civi::settings()->set('civioffice_renderers', $rendererList);
  }

  public function delete(): void {
    \Civi::settings()->revert('civioffice_renderer_' . $this->uri);
    /** @phpstan-var array<string, string> $rendererList */
    $rendererList = \Civi::settings()->get('civioffice_renderers') ?? [];
    unset($rendererList[$this->uri]);
    \Civi::settings()->set('civioffice_renderers', $rendererList);
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * @throws \InvalidArgumentException
   *   When the renderer type does not support a configuration item with the given name.
   */
  public function getConfigItem(string $name): mixed {
    $this->ensureConfigurationItemSupported($name);

    return $this->configuration[$name];
  }

  /**
   * @throws \InvalidArgumentException
   *   When the renderer type does not support a configuration item with the given name.
   */
  public function setConfigItem(string $name, mixed $value): void {
    $this->ensureConfigurationItemSupported($name);
    $this->configuration[$name] = $value;
  }

  /**
   * @phpstan-param array<string, mixed> $configuration
   *
   * @throws \InvalidArgumentException
   *   When the renderer type does not support a configuration item with the given name.
   */
  public function setConfiguration(array $configuration): void {
    $this->configuration = [];
    foreach ($configuration as $name => $value) {
      $this->setConfigItem($name, $value);
    }
  }

  /**
   * @throws \InvalidArgumentException
   *   When the renderer type does not support a configuration item with the given name.
   */
  private function ensureConfigurationItemSupported(string $configurationItem): void {
    if (!in_array($configurationItem, $this->type->getSupportedConfigurationItems(), TRUE)) {
      throw new \InvalidArgumentException(sprintf(
        'Document renderer type %s does not support configuration item %s',
        $this->type::getName(),
        $configurationItem
      ));
    }
  }

}
