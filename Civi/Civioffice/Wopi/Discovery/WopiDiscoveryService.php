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

namespace Civi\Civioffice\Wopi\Discovery;

use Civi\Civioffice\DocumentEditorManager;
use Civi\Civioffice\Wopi\WopiDocumentEditorTypeInterface;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class WopiDiscoveryService implements WopiDiscoveryServiceInterface {

  private DocumentEditorManager $documentEditorManager;

  private ClientInterface $httpClient;

  private RequestFactoryInterface $requestFactory;

  public function __construct(
    DocumentEditorManager $documentEditorManager,
    ?ClientInterface $httpClient = NULL,
    ?RequestFactoryInterface $requestFactory = NULL,
  ) {
    $this->documentEditorManager = $documentEditorManager;
    $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
    $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
  }

  public function getDiscoveryByEditorId(int $editorId): WopiDiscoveryResponse {
    return $this->getDiscoveryByUrl($this->getDiscoveryUrl($editorId));
  }

  public function getDiscoveryByUrl(string $discoveryUrl): WopiDiscoveryResponse {
    $discoveryXml = $this->getDiscoveryXml($discoveryUrl);

    return new WopiDiscoveryResponse($discoveryXml);
  }

  private function getDiscoveryUrl(int $editorId): string {
    $editor = $this->documentEditorManager->getEditor($editorId);
    if (!$editor->getType() instanceof WopiDocumentEditorTypeInterface) {
      throw new \InvalidArgumentException("Document editor with ID $editorId is not a WOPI document editor");
    }

    return $editor->getType()->getWopiDiscoveryUrl($editor->getTypeConfig());
  }

  /**
   * @throws \Psr\Http\Client\ClientExceptionInterface
   */
  private function getDiscoveryXml(string $url): string {
    $request = $this->requestFactory->createRequest('GET', $url);
    $response = $this->httpClient->sendRequest($request);

    return $response->getBody()->getContents();
  }

}
