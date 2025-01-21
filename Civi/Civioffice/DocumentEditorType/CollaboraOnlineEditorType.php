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

use Civi\Civioffice\DocumentEditorTypeInterface;
use Civi\Civioffice\Wopi\Discovery\WopiDiscoveryService;
use Civi\Civioffice\Wopi\Util\CiviUrlGenerator;
use Civi\Civioffice\Wopi\WopiAccessTokenService;
use CRM_Civioffice_ExtensionUtil as E;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @phpstan-import-type fileT from \Civi\Civioffice\FileManagerInterface
 */
final class CollaboraOnlineEditorType implements DocumentEditorTypeInterface {

  private const TOKEN_VALIDITY_TIME = 24 * 60 * 60;

  private WopiAccessTokenService $accessTokenService;

  private CiviUrlGenerator $civiUrlGenerator;

  private WopiDiscoveryService $discoveryService;

  public static function getName(): string {
    return 'cool';
  }

  public static function getTitle(): string {
    return 'Collabora Online';
  }

  public function __construct(
    WopiAccessTokenService $accessTokenService,
    CiviUrlGenerator $civiUrlGenerator,
    WopiDiscoveryService $discoveryService
  ) {
    $this->accessTokenService = $accessTokenService;
    $this->civiUrlGenerator = $civiUrlGenerator;
    $this->discoveryService = $discoveryService;
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
    return 'Civi/Civioffice/Form/DocumentEditor/CollaboraOnline.tpl';
  }

  public function validateSettingsForm(\CRM_Civioffice_Form_DocumentEditorSettings $form): void {
    /** @var string $coolUrl */
    $coolUrl = $form->getSubmitValue('cool_url') ?? '';
    if (filter_var($coolUrl, FILTER_VALIDATE_URL) === FALSE
      || (!str_starts_with($coolUrl, 'http://') && !str_starts_with($coolUrl, 'https://'))
    ) {
      $form->setElementError('cool_url', E::ts('Invalid value'));
    }

    try {
      $this->discoveryService->getDiscoveryByUrl($coolUrl);
    }
    catch (\InvalidArgumentException | ClientExceptionInterface $e) {
      $form->setElementError('cool_url', E::ts('Could not reach Collabora Online: %1', [1 => $e->getMessage()]));
    }

    $wopiSrcHostname = $form->getSubmitValue('wopi_src_hostname') ?? '';
    if ('' !== $wopiSrcHostname) {
      if (filter_var($wopiSrcHostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === FALSE) {
        $form->setElementError('wopi_src_hostname', E::ts('Invalid value'));
      }
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
  public function isFileSupported(array $configuration, array $file): bool {
    assert(is_string($configuration['cool_url']));

    return NULL !== $this->getWopiAppUrl($configuration, $file);
  }

  /**
   * @inheritDoc
   */
  public function handleFile(array $configuration, array $file): Response {
    assert(is_string($configuration['cool_url']));
    $wopiAppUrl = $this->getWopiAppUrl($configuration, $file);
    assert(is_string($wopiAppUrl));

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
      $this->discoveryService->getDiscoveryIdentifier($configuration['cool_url']),
      $accessTokenTtl
    );

    $html = <<<HTML
<html>
<head>
<title>$fileBaseName - CiviCRM</title>
</head>
<body style="margin: 0; overflow: hidden">

<iframe id="coolframe" name="coolframe" title="Collabora Online"
  allowfullscreen allow="clipboard-read *; clipboard-write *"
  style="width:100%; height:100%; position:absolute; border: none;"></iframe>

<div style="display: none">
  <form id="coolform" name="coolform" target="coolframe" action="$wopiUrl" method="post">
    <input name="access_token" value="$accessToken" type="hidden">
    <input name="access_token_ttl" value="$accessTokenTtl" type="hidden">
  </form>
</div>

<script>
  const frame = document.getElementById("coolframe");
  const form = document.getElementById("coolform");
  frame.focus();
  form.submit();
</script>
</body>
</html>
HTML;

    return new Response($html);
  }

  /**
   * @param array{cool_url: string} $configuration
   * @phpstan-param fileT $file
   */
  private function getWopiAppUrl(array $configuration, array $file): ?string {
    $discoveryResponse = $this->discoveryService->getDiscoveryByUrl($configuration['cool_url']);

    assert(is_string($file['mime_type']));
    return $discoveryResponse->getActionUrlByMimeType($file['mime_type'], 'edit')
      ?? $discoveryResponse->getActionUrlByMimeType($file['mime_type'], 'view');
  }

}
