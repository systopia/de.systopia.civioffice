<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
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
interface DocumentEditorTypeInterface {

  public static function getName(): string;

  public static function getTitle(): string;

  public function buildSettingsForm(\CRM_Civioffice_Form_DocumentEditorSettings $form): void;

  public function getSettingsFormTemplate(): string;

  public function validateSettingsForm(\CRM_Civioffice_Form_DocumentEditorSettings $form): void;

  /**
   * @phpstan-return array<string, mixed> The configuration.
   */
  public function postProcessSettingsForm(\CRM_Civioffice_Form_DocumentEditorSettings $form): array;

  /**
   * @phpstan-return array<string, mixed>
   */
  public function getDefaultConfiguration(): array;

  /**
   * @param array<string, mixed> $configuration
   *
   * @phpstan-param fileT $file
   *   Key 'mime_type' has a non-empty string.
   */
  public function isFileSupported(array $configuration, array $file): bool;

  /**
   * Only called if isFileSupported() returned TRUE.
   *
   * @param array<string, mixed> $configuration
   *
   * @phpstan-param fileT $file
   *   Key 'mime_type' has a non-empty string.
   */
  public function handleFile(array $configuration, array $file): Response;

}
