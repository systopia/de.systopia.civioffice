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

namespace Civi\Civioffice\DocumentRendererType;

use Assert\Assertion;
use Civi\Civioffice\Collabora\CoolConvertClientFactory;
use Civi\Civioffice\DocumentRendererTypeInterface;
use CRM_Civioffice_ExtensionUtil as E;
use CRM_Civioffice_Form_DocumentRenderer_Settings;
use Psr\Http\Client\ClientExceptionInterface;

final class CollaboraOnlineRendererType implements DocumentRendererTypeInterface {

  private CoolConvertClientFactory $coolConvertClientFactory;

  public static function getName(): string {
    return 'cool';
  }

  public static function getTitle(): string {
    return 'Collabora Online';
  }

  public function __construct(CoolConvertClientFactory $coolConvertClientFactory) {
    $this->coolConvertClientFactory = $coolConvertClientFactory;
  }

  public function buildSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form): void {
    $form->add(
      'text',
      'cool_url',
      E::ts('URL to Collabora Online'),
      ['class' => 'huge'],
      TRUE
    );
  }

  public function getSettingsFormTemplate(): string {
    return 'CRM/Civioffice/Form/DocumentRenderer/Settings/CollaboraOnline.tpl';
  }

  public function validateSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form): void {
    $coolUrl = $form->_submitValues['cool_url'];
    if (filter_var($coolUrl, FILTER_VALIDATE_URL) === FALSE
      || (!str_starts_with($coolUrl, 'http://') && !str_starts_with($coolUrl, 'https://'))
    ) {
      $form->_errors['cool_url'] = E::ts('Invalid value');
    }
  }

  public function postProcessSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form): array {
    $values = $form->exportValues();

    return ['cool_url' => $values['cool_url']];
  }

  /**
   * @inheritDoc
   *
   * @see https://www.collaboraonline.com/document-conversion/
   */
  public function getSupportedInputMimeTypes(array $configuration): array {
    return [
      \CRM_Civioffice_MimeType::ODT,
      \CRM_Civioffice_MimeType::DOCX,
      \CRM_Civioffice_MimeType::RTF,
    ];
  }

  /**
   * @inheritDoc
   *
   * @see https://www.collaboraonline.com/document-conversion/
   */
  public function getSupportedOutputMimeTypes(array $configuration): array {
    return [
      \CRM_Civioffice_MimeType::PDF,
      \CRM_Civioffice_MimeType::PNG,
    ];
  }

  public function render(array $configuration, string $inputFile, string $outputFile, string $mimeType): void {
    Assertion::string($configuration['cool_url']);
    $format = \CRM_Civioffice_MimeType::mapMimeTypeToFileExtension($mimeType);

    $convertClient = $this->coolConvertClientFactory->createClient($configuration['cool_url']);
    try {
      file_put_contents($outputFile, $convertClient->convert($inputFile, $format, 'de-DE', 'PDF-1.5'));
    }
    catch (ClientExceptionInterface $e) {
      throw new \CRM_Core_Exception($e->getMessage(), 0, [], $e);
    }
  }

  /**
   * @inheritDoc
   */
  public function getSupportedConfigurationItems(): array {
    return ['cool_url'];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration(): array {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function isReady(array $configuration = []): bool {
    return isset($configuration['cool_url']);
  }

}
