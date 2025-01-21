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

use Symfony\Component\HttpFoundation\Response;

/**
 * @phpstan-import-type fileT from \Civi\Civioffice\FileManagerInterface
 */
final class DocumentEditor extends \CRM_Civioffice_OfficeComponent {

  private bool $active;

  /**
   * @var list<string>
   */
  private array $fileExtensions;

  private int $order;

  private DocumentEditorTypeInterface $type;

  /**
   * @phpstan-var array<string, mixed>
   */
  private array $typeConfig;

  /**
   * @phpstan-param array{
   *   active: bool,
   *   order: int,
   *   fileExtensions: list<string>,
   *   typeConfig: array<string, mixed>,
   * } $configuration
   */
  public function __construct(
    int $id,
    string $name,
    array $configuration,
    DocumentEditorTypeInterface $type,
  ) {
    parent::__construct((string) $id, $name);
    $this->active = $configuration['active'];
    $this->order = $configuration['order'];
    $this->fileExtensions = $configuration['fileExtensions'];
    $this->type = $type;
    $this->typeConfig = $configuration['typeConfig'] + $this->type->getDefaultConfiguration();
  }

  public function isActive(): bool {
    return $this->active;
  }

  public function setActive(bool $active): self {
    $this->active = $active;

    return $this;
  }

  /**
   * @return list<string>
   */
  public function getFileExtensions(): array {
    return $this->fileExtensions;
  }

  /**
   * @param list<string> $fileExtensions
   */
  public function setFileExtensions(array $fileExtensions): self {
    $this->fileExtensions = $fileExtensions;

    return $this;
  }

  public function getOrder(): int {
    return $this->order;
  }

  public function setOrder(int $order): static {
    $this->order = $order;

    return $this;
  }

  public function getType(): DocumentEditorTypeInterface {
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
        'civicrm/admin/civioffice/settings/editor',
        'id=' . $this->uri . '&action=update'
    );
  }

  public function getDeleteURL(): string {
    return \CRM_Utils_System::url(
        'civicrm/admin/civioffice/settings/editor',
        'id=' . $this->uri . '&action=delete'
    );
  }

  public function getDescription(): string {
    return $this->type::getTitle();
  }

  /**
   * @phpstan-param fileT $file
   *   Key 'mime_type' has a non-empty string.
   */
  public function isFileSupported(array $file): bool {
    return $this->isFileExtensionSupported($file['uri']) && $this->type->isFileSupported($this->typeConfig, $file);
  }

  /**
   * @inheritDoc
   */
  public function isReady(): bool {
    return $this->isActive();
  }

  /**
   * Only called if isFileSupported() returned TRUE.
   *
   * @phpstan-param fileT $file
   *   Key 'mime_type' has a non-empty string.
   */
  public function handleFile(array $file): Response {
    return $this->type->handleFile($this->typeConfig, $file);
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function getTypeConfig(): array {
    return $this->typeConfig;
  }

  /**
   * @phpstan-param array<string, mixed> $typeConfig
   */
  public function setTypeConfig(array $typeConfig): void {
    $this->typeConfig = $typeConfig;
  }

  private function isFileExtensionSupported(string $uri): bool {
    if ([] === $this->fileExtensions) {
      return TRUE;
    }

    foreach ($this->fileExtensions as $extension) {
      if (str_ends_with($uri, $extension)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
