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

namespace Civi\Civioffice\Collabora;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class CoolConvertClientFactory {

  private ClientInterface $httpClient;

  private RequestFactoryInterface $requestFactory;

  private StreamFactoryInterface $streamFactory;

  public function __construct(
    ?ClientInterface $httpClient = NULL,
    ?RequestFactoryInterface $requestFactory = NULL,
    ?StreamFactoryInterface $streamFactory = NULL
  ) {
    $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
    $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
  }

  public function createClient(string $coolUrl): CoolConvertClient {
    return new CoolConvertClient($coolUrl, $this->httpClient, $this->requestFactory, $this->streamFactory);
  }

}
