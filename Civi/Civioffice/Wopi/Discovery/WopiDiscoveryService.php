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

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;

final class WopiDiscoveryService {

  private CacheInterface $cache;

  private ClientInterface $httpClient;

  private RequestFactoryInterface $requestFactory;

  public function __construct(
    CacheInterface $cache,
    ?ClientInterface $httpClient = NULL,
    ?RequestFactoryInterface $requestFactory = NULL,
  ) {
    $this->cache = $cache;
    $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
    $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
  }

  /**
   * @throws \InvalidArgumentException
   * @throws \Psr\Http\Client\ClientExceptionInterface
   */
  public function getDiscovery(string $identifier): WopiDiscoveryResponse {
    $discoveryCacheKey = $this->getDiscoveryCacheKey($identifier);
    $discoveryXml = $this->cache->get($discoveryCacheKey);
    if (NULL === $discoveryXml) {
      $discoveryXml = $this->getDiscoveryXml($this->getDiscoveryUrl($identifier));
      $response = new WopiDiscoveryResponse($discoveryXml);
      $this->cache->set($discoveryCacheKey, $discoveryXml, 24 * 60 * 60);
    }
    else {
      assert(is_string($discoveryXml));
      $response = new WopiDiscoveryResponse($discoveryXml);
    }

    return $response;
  }

  /**
   * @throws \RuntimeException
   * @throws \Psr\Http\Client\ClientExceptionInterface
   */
  public function getDiscoveryByUrl(string $wopiUrl): WopiDiscoveryResponse {
    $identifier = $this->getDiscoveryIdentifier($wopiUrl);
    $urlCacheKey = $this->getUrlCacheKey($identifier);
    $this->cache->set($urlCacheKey, $wopiUrl, 48 * 60 * 60);

    return $this->getDiscovery($identifier);
  }

  public function getDiscoveryIdentifier(string $url): string {
    return hash('crc32c', $url);
  }

  /**
   * @throws \Psr\Http\Client\ClientExceptionInterface
   */
  private function getDiscoveryXml(string $url): string {
    $request = $this->requestFactory->createRequest('GET', $url);
    $response = $this->httpClient->sendRequest($request);

    return $response->getBody()->getContents();
  }

  private function getDiscoveryCacheKey(string $identifier): string {
    return "$identifier:wopi_discovery.xml";
  }

  private function getDiscoveryUrl(string $identifier): string {
    $url = $this->cache->get($this->getUrlCacheKey($identifier));
    if (NULL === $url) {
      throw new \InvalidArgumentException(sprintf('Unknown WOPI URL identifier "%s"', $identifier));
    }

    assert(is_string($url));

    return rtrim($url, '/') . '/hosting/discovery';
  }

  private function getUrlCacheKey(string $identifier): string {
    return "$identifier:wopi_url";
  }

}
