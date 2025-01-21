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

namespace Civi\Civioffice\EventSubscriber;

use Civi\Api4\File;
use Civi\Civioffice\Controller\ColaboraFileWopiController;
use Civi\Core\Event\GenericHookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

final class ColaboraFilePageSubscriber implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return ['hook_civicrm_pageRun' => 'onPageRun'];
  }

  /**
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \CRM_Core_Exception
   */
  public function onPageRun(GenericHookEvent $event): void {
    if (!$event->page instanceof \CRM_Core_Page_File) {
      return;
    }

    /** @var string $colaboraUrl */
    $colaboraUrl = \Civi::settings()->get('colabora_url') ?? '';
    if ('' === $colaboraUrl) {
      return;
    }

    /** @var int|null $fileId */
    $fileId = \CRM_Utils_Request::retrieve('id', 'Positive');
    if (NULL === $fileId) {
      return;
    }

    /** @phpstan-var array{id: int, uri: string|null, mime_type: string|null}|null $file */
    $file = File::get(FALSE)->addWhere('id', '=', $fileId)->execute()->first();
    if (NULL === $file) {
      return;
    }

    $mimeType = $file['mime_type'] ?? '';
    if ('' == $mimeType || NULL === $file['uri']) {
      return;
    }

    $wopiAppUrl = $this->discoverWopiAppUrl($colaboraUrl, $mimeType);
    if (NULL === $wopiAppUrl) {
      return;
    }

    $fileBaseName = basename($file['uri']);
    $wopiSrc = \CRM_Utils_System::url('civicrm/civioffice/colabora/wopi/files/' . $fileId, '', TRUE, NULL, FALSE);
    // @todo: Remove workaround for Docker.
    $wopiSrc = str_replace('localhost', '172.17.0.1', $wopiSrc);
    $wopiUrl = rtrim($wopiAppUrl, '?') . '?WOPISrc=' . urlencode($wopiSrc);
    $accessToken = ColaboraFileWopiController::generateHash($fileId);

    $html = <<<HTML
<html>
<head>
<title>$fileBaseName - CiviCRM</title>
</head>
<body style="margin: 0; overflow: hidden">

<iframe id="coolframe" name="coolframe" title="Colabora Online"
  allowfullscreen allow="clipboard-read *; clipboard-write *"
  style="width:100%; height:100%; position:absolute; border: none;"></iframe>

<div style="display: none">
  <form id="coolform" name="coolform" target="coolframe" action="$wopiUrl" method="post">
    <input name="access_token" value="$accessToken" type="hidden">
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

    $response = new Response($html);
    $response->send();
    \CRM_Utils_System::civiExit();
  }

  private function discoverWopiAppUrl(string $wopiClientUrl, string $mimeType): ?string {
    $cache = \Civi::cache('long');

    $content = $cache->get('colabora_wopi_discovery.xml');
    if (NULL === $content) {
      $discoveryUrl = $wopiClientUrl . '/hosting/discovery';
      $httpClient = \CRM_Utils_HttpClient::singleton();
      [$status, $content] = $httpClient->get($discoveryUrl);
      if ($status !== \CRM_Utils_HttpClient::STATUS_OK) {
        return NULL;
      }

      $cache->set('colabora_wopi_discovery.xml', $content);
    }

    $wopiDiscovery = new \SimpleXMLElement($content);
    $editClientUrl = NULL;
    $viewClientUrl = NULL;
    foreach ($wopiDiscovery->{'net-zone'} as $netZone) {
      foreach ($netZone->app as $app) {
        if ((string) $app['name'] === $mimeType) {
          foreach ($app->action as $action) {
            if ((string) $action['name'] === 'edit') {
              if ((string) $action['default'] === 'true') {
                return (string) $action['urlsrc'];
              }

              $editClientUrl = (string) $action['urlsrc'];
            }
            elseif ((string) $action['name'] === 'view') {
              if (NULL === $viewClientUrl || (string) $action['default'] === 'true') {
                $viewClientUrl = (string) $action['urlsrc'];
              }
            }
          }

          if (NULL !== $editClientUrl || NULL !== $viewClientUrl) {
            return $editClientUrl ?? $viewClientUrl;
          }
        }
      }
    }

    return NULL;
  }

}
