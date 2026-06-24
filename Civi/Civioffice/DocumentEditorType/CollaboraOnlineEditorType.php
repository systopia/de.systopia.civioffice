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

namespace Civi\Civioffice\DocumentEditorType;

use Civi\Civioffice\Wopi\Discovery\WopiDiscoveryServiceInterface;
use Civi\Civioffice\Wopi\Util\CiviUrlGenerator;
use Civi\Civioffice\Wopi\WopiAccessTokenService;
use Civi\Civioffice\Wopi\WopiDocumentEditorTypeInterface;
use CRM_Civioffice_ExtensionUtil as E;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @phpstan-import-type fileT from \Civi\Civioffice\FileManagerInterface
 *
 * @see https://sdk.collaboraonline.com/
 */
final class CollaboraOnlineEditorType implements WopiDocumentEditorTypeInterface {

  private const TOKEN_VALIDITY_TIME = 24 * 60 * 60;

  private WopiAccessTokenService $accessTokenService;

  private CiviUrlGenerator $civiUrlGenerator;

  private WopiDiscoveryServiceInterface $discoveryService;

  private \CRM_Core_Smarty $smarty;

  public static function getName(): string {
    return 'cool';
  }

  public static function getTitle(): string {
    return 'Collabora Online';
  }

  public function __construct(
    WopiAccessTokenService $accessTokenService,
    CiviUrlGenerator $civiUrlGenerator,
    WopiDiscoveryServiceInterface $discoveryService,
    ?\CRM_Core_Smarty $smarty = NULL
  ) {
    $this->accessTokenService = $accessTokenService;
    $this->civiUrlGenerator = $civiUrlGenerator;
    $this->discoveryService = $discoveryService;
    $this->smarty = $smarty ?? \CRM_Core_Smarty::singleton();
  }

  public function buildSettingsForm(\CRM_Civioffice_Form_DocumentEditorSettings $form): void {
    $form->add(
      'text',
      'cool_url',
      E::ts('URL to Collabora Online'),
      ['class' => 'huge'],
      TRUE
    );
    $form->add(
      'text',
      'wopi_src_hostname',
      E::ts('WOPISrc Hostname'),
      ['class' => 'huge'],
    );
  }

  public function getSettingsFormTemplate(): string {
    return 'Civi/Civioffice/DocumentEditor/CollaboraOnlineSettings.tpl';
  }

  public function validateSettingsForm(\CRM_Civioffice_Form_DocumentEditorSettings $form, bool $active): void {
    /** @var string $coolUrl */
    $coolUrl = $form->getSubmitValue('cool_url') ?? '';
    if (filter_var($coolUrl, FILTER_VALIDATE_URL) === FALSE
      || (!str_starts_with($coolUrl, 'http://') && !str_starts_with($coolUrl, 'https://'))
    ) {
      $form->setElementError('cool_url', E::ts('Invalid value'));
    }

    try {
      // Only load WOPI discovery if marked as active to allow disabling if COOL server is unreachable.
      if ($active) {
        $this->discoveryService->getDiscoveryByUrl($this->getWopiDiscoveryUrl(['cool_url' => $coolUrl]));
      }
    }
    catch (\InvalidArgumentException | ClientExceptionInterface $e) {
      $form->setElementError('cool_url', E::ts('Could not reach Collabora Online: %1', [1 => $e->getMessage()]));
    }

    $wopiSrcHostname = $form->getSubmitValue('wopi_src_hostname') ?? '';
    if ('' !== $wopiSrcHostname
      && filter_var($wopiSrcHostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === FALSE
    ) {
      $form->setElementError('wopi_src_hostname', E::ts('Invalid value'));
    }
  }

  /**
   * @inheritDoc
   */
  public function postProcessSettingsForm(\CRM_Civioffice_Form_DocumentEditorSettings $form): array {
    $values = $form->exportValues();

    return [
      'cool_url' => $values['cool_url'],
      'wopi_src_hostname' => $values['wopi_src_hostname'] ?? NULL,
    ];
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
  public function getWopiDiscoveryUrl(array $configuration): string {
    assert(is_string($configuration['cool_url']));

    return rtrim($configuration['cool_url'], '/') . '/hosting/discovery';
  }

  /**
   * @inheritDoc
   */
  public function isFileSupported(array $configuration, array $file, int $editorId): bool {
    return NULL !== $this->getWopiAppUrl($file, $editorId);
  }

  /**
   * @inheritDoc
   *
   * @throws \Psr\Http\Client\ClientExceptionInterface
   */
  public function handleFile(array $configuration, array $file, int $editorId): Response {
    $wopiAppUrl = $this->getWopiAppUrl($file, $editorId);
    assert(NULL !== $wopiAppUrl);

    $fileBaseName = basename($file['uri']);
    $wopiSrc = $this->civiUrlGenerator->generateAbsoluteUrl('civicrm/civioffice/collabora/wopi/files/' . $file['id']);
    if ('' !== ($configuration['wopi_src_hostname'] ?? '')) {
      assert(is_string($configuration['wopi_src_hostname']));
      $wopiSrcHostname = parse_url($wopiSrc, PHP_URL_HOST);
      assert(is_string($wopiSrcHostname));
      $wopiSrc = str_replace($wopiSrcHostname, $configuration['wopi_src_hostname'], $wopiSrc);
    }

    $wopiUrl = rtrim($wopiAppUrl, '?') . '?WOPISrc=' . urlencode($wopiSrc);
    $accessTokenTtl = \CRM_Utils_Time::time() + self::TOKEN_VALIDITY_TIME;
    $accessToken = $this->accessTokenService->generateToken(
      $file['id'],
      $editorId,
      $accessTokenTtl
    );
    $accessTokenTtlMs = $accessTokenTtl * 1000;

    $tpl = $this->smarty->createTemplate('Civi/Civioffice/DocumentEditor/CollaboraOnlineEditor.tpl');
    $tpl->assign([
      'fileBaseName' => $fileBaseName,
      'wopiUrl' => $wopiUrl,
      'accessToken' => $accessToken,
      'accessTokenTtl' => $accessTokenTtlMs,
    ]);

    return new Response($tpl->fetch());
  }

  /**
   * @phpstan-param fileT $file
   *
   * @throws \Psr\Http\Client\ClientExceptionInterface
   */
  private function getWopiAppUrl(array $file, int $editorId): ?string {
    $discoveryResponse = $this->discoveryService->getDiscoveryByEditorId($editorId);

    return $discoveryResponse->getActionUrlByMimeType($file['mime_type'], 'edit')
      ?? $discoveryResponse->getActionUrlByMimeType($file['mime_type'], 'view');
  }

}
