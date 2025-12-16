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

use Psr\SimpleCache\CacheInterface;

final class WopDiscoveryServiceCacheDecorator implements WopiDiscoveryServiceInterface {

  private const CACHE_TTL = 24 * 60 * 60;

  private CacheInterface $cache;

  private WopiDiscoveryServiceInterface $wopiDiscoveryService;

  public function __construct(CacheInterface $cache, WopiDiscoveryServiceInterface $wopiDiscoveryService) {
    $this->cache = $cache;
    $this->wopiDiscoveryService = $wopiDiscoveryService;
  }

  /**
   * @inheritDoc
   */
  public function getDiscoveryByEditorId(int $editorId): WopiDiscoveryResponse {
    $discoveryCacheKey = $this->getDiscoveryCacheKey($editorId);
    $discoveryXml = $this->cache->get($discoveryCacheKey);
    if (!is_string($discoveryXml)) {
      $response = $this->wopiDiscoveryService->getDiscoveryByEditorId($editorId);
      $this->cache->set($discoveryCacheKey, $response->toString(), self::CACHE_TTL);
    }
    else {
      $response = new WopiDiscoveryResponse($discoveryXml);
    }

    return $response;
  }

  /**
   * @inheritDoc
   */
  public function getDiscoveryByUrl(string $discoveryUrl): WopiDiscoveryResponse {
    return $this->wopiDiscoveryService->getDiscoveryByUrl($discoveryUrl);
  }

  private function getDiscoveryCacheKey(int $editorId): string {
    return "civioffice_wopi_discovery_$editorId";
  }

}
