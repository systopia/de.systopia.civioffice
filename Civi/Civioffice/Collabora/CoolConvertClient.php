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

use Http\Message\MultipartStream\MultipartStreamBuilder;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Client for Collabora Online Conversion API.
 *
 * @see https://sdk.collaboraonline.com/docs/conversion_api.html
 * @see https://www.collaboraonline.com/document-conversion/
 */
final class CoolConvertClient {

  private string $coolUrl;

  private ClientInterface $httpClient;

  private RequestFactoryInterface $requestFactory;

  private StreamFactoryInterface $streamFactory;

  public function __construct(
    string $coolUrl,
    ClientInterface $httpClient,
    RequestFactoryInterface $requestFactory,
    StreamFactoryInterface $streamFactory
  ) {
    $this->coolUrl = rtrim($coolUrl, '/');
    $this->httpClient = $httpClient;
    $this->requestFactory = $requestFactory;
    $this->streamFactory = $streamFactory;
  }

  /**
   * @param string $filename
   *   Path to input file. See supported input formats at
   *   https://www.collaboraonline.com/document-conversion/.
   * @param string $format
   *   See supported output formats at
   *   https://www.collaboraonline.com/document-conversion/.
   * @param string|null $lang
   *   Sets the default format language, useful for date type cells. In the form
   *   xx-XX (e.g. de-DE) or xx (e.g. de).
   * @param string|null $pdfVer
   *   The PDF version if output format is pdf, e.g. PDF/A-2b. See supported
   *   PDF versions at https://sdk.collaboraonline.com/docs/conversion_api.html.
   *
   * @throws \Psr\Http\Client\ClientExceptionInterface
   */
  public function convert(string $filename, string $format, ?string $lang = NULL, ?string $pdfVer = NULL): string {
    $convertUrl = $this->coolUrl . '/cool/convert-to/' . $format;
    if (NULL !== $lang) {
      $convertUrl .= '?lang=' . $lang;
    }
    $request = $this->requestFactory->createRequest('POST', $convertUrl);

    $fileHandle = fopen($filename, 'r');
    if (FALSE === $fileHandle) {
      throw new \RuntimeException(sprintf('Failed to open file "%s"', $filename));
    }

    $builder = new MultipartStreamBuilder($this->streamFactory);
    $builder->addResource('data', $fileHandle, ['filename' => basename($filename)]);
    if (NULL !== $pdfVer) {
      $builder->addResource('PDFVer', $pdfVer);
    }

    $request = $request
      ->withHeader('Content-Type', 'multipart/form-data; boundary="' . $builder->getBoundary() . '"')
      ->withBody($builder->build());

    $response = $this->httpClient->sendRequest($request);

    return $response->getBody()->getContents();
  }

}
