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

interface DocumentRendererTypeInterface {

  public static function getName(): string;

  public static function getTitle(): string;

  public function buildSettingsForm(\CRM_Civioffice_Form_DocumentRenderer_Settings $form): void;

  public function getSettingsFormTemplate(): string;

  public function validateSettingsForm(\CRM_Civioffice_Form_DocumentRenderer_Settings $form): void;

  /**
   * @phpstan-return array<string, mixed> The configuration.
   */
  public function postProcessSettingsForm(\CRM_Civioffice_Form_DocumentRenderer_Settings $form): array;

  /**
   * @phpstan-return array<string, mixed>
   */
  public function getDefaultConfiguration(): array;

  /**
   * @phpstan-return list<string>
   *   Possible keys in configuration. Must NOT contain "type".
   */
  public function getSupportedConfigurationItems(): array;

  /**
   * Get a list of document MIME types supported by this component
   *
   * @phpstan-param array<string, mixed> $configuration
   *
   * @phpstan-return list<string>
   *   list of MIME types as strings
   */
  public function getSupportedInputMimeTypes(array $configuration): array;

  /**
   * Get the output/generated MIME types for this document renderer
   *
   * @phpstan-param array<string, mixed> $configuration
   *
   * @phpstan-return list<string>
   *   list of MIME types
   */
  public function getSupportedOutputMimeTypes(array $configuration): array;

  /**
   * @phpstan-param array<string, mixed> $configuration
   *
   * @return bool TRUE, if the configuration is ready to render documents.
   */
  public function isReady(array $configuration): bool;

  /**
   * @phpstan-param array<string, mixed> $configuration
   */
  public function render(array $configuration, string $inputFile, string $outputFile, string $mimeType): void;

}
