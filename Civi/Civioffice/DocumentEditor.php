<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
 *
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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

  private DocumentEditorTypeInterface $type;

  /**
   * @phpstan-var array<string, mixed>
   */
  private array $typeConfig;

  /**
   * @phpstan-param array{
   *   id: int,
   *   name: string,
   *   is_active: bool,
   *   file_extensions: list<string>,
   *   type_config: array<string, mixed>,
   * } $configuration
   */
  public function __construct(array $configuration, DocumentEditorTypeInterface $type) {
    parent::__construct((string) $configuration['id'], $configuration['name']);
    $this->active = $configuration['is_active'];
    $this->fileExtensions = $configuration['file_extensions'];
    $this->type = $type;
    $this->typeConfig = $configuration['type_config'] + $this->type->getDefaultConfiguration();
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

  public function getId(): int {
    return (int) $this->getURI();
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
        'id=' . $this->getId() . '&action=update'
    );
  }

  public function getDeleteURL(): string {
    return \CRM_Utils_System::url(
        'civicrm/admin/civioffice/settings/editor',
        'id=' . $this->getId() . '&action=delete'
    );
  }

  public function getDescription(): string {
    return $this->type::getTitle();
  }

  /**
   * @phpstan-param fileT $file
   */
  public function isFileSupported(array $file): bool {
    return $this->isFileExtensionSupported($file['uri'])
      && $this->type->isFileSupported($this->typeConfig, $file, $this->getId());
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
   */
  public function handleFile(array $file): Response {
    return $this->type->handleFile($this->typeConfig, $file, $this->getId());
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
