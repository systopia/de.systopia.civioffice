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

use Civi\Civioffice\Wopi\WopiProofKey;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Civioffice\Wopi\Discovery\WopiDiscoveryResponse
 */
final class WopiDiscoveryResponseTest extends TestCase {

  private WopiDiscoveryResponse $response;

  protected function setUp(): void {
    parent::setUp();
    // @phpstan-ignore argument.type
    $this->response = new WopiDiscoveryResponse(file_get_contents(__DIR__ . '/discovery-with-test-proof-keys.xml'));
  }

  public function testGetActionByExtension(): void {
    static::assertEquals([
      'default' => 'true',
      'ext' => 'odt',
      'name' => 'edit',
      'urlsrc' => 'http://localhost:9980/browser/b6712dc6c4/cool.html?',
      'appName' => 'writer',
      'favIconUrl' => 'http://localhost:9980/browser/b6712dc6c4/images/x-office-document.svg',
    ], $this->response->getActionByExtension('odt', 'edit'));

    static::assertNull($this->response->getActionByExtension('not-existent-extension', 'edit'));
  }

  public function testGetActions(): void {
    static::assertCount(184, $this->response->getActions());
  }

  public function testGetActionsByExtension(): void {
    static::assertEquals([
      [
        'default' => TRUE,
        'ext' => 'ott',
        'name' => 'view',
        'urlsrc' => 'http://localhost:9980/browser/b6712dc6c4/cool.html?',
        'appName' => 'writer',
        'favIconUrl' => 'http://localhost:9980/browser/b6712dc6c4/images/x-office-document.svg',
      ],
    ], $this->response->getActionsByExtension('ott', 'view'));

    static::assertSame([], $this->response->getActionsByExtension('not-existent-extension', 'view'));
  }

  public function testGetActionUrlByExtension(): void {
    static::assertEquals(
      'http://localhost:9980/browser/b6712dc6c4/cool.html?',
      $this->response->getActionUrlByExtension('odt', 'edit')
    );

    static::assertNull($this->response->getActionUrlByExtension('odt', 'view'));
  }

  public function testGetActionsByMimeType(): void {
    static::assertEquals([
      [
        'default' => TRUE,
        'ext' => '',
        'name' => 'edit',
        'urlsrc' => 'http://localhost:9980/browser/b6712dc6c4/cool.html?',
        'appName' => 'application/vnd.oasis.opendocument.text',
        'favIconUrl' => '',
      ],
    ], $this->response->getActionsByMimeType('application/vnd.oasis.opendocument.text', 'edit'));

    static::assertSame([], $this->response->getActionsByMimeType('not-existant-mime-type', 'edit'));
  }

  public function testGetActionByMimeType(): void {
    static::assertEquals(
      [
        'default' => 'true',
        'ext' => '',
        'name' => 'edit',
        'urlsrc' => 'http://localhost:9980/browser/b6712dc6c4/cool.html?',
        'appName' => 'application/vnd.oasis.opendocument.text',
        'favIconUrl' => '',
      ], $this->response->getActionByMimeType('application/vnd.oasis.opendocument.text', 'edit')
    );

    static::assertNull($this->response->getActionByMimeType('not-existant-mime-type', 'edit'));
  }

  public function testGetActionUrlByMimeType(): void {
    static::assertEquals(
      'http://localhost:9980/browser/b6712dc6c4/cool.html?',
      $this->response->getActionUrlByMimeType('application/vnd.oasis.opendocument.text', 'edit')
    );

    static::assertNull($this->response->getActionUrlByMimeType('application/vnd.oasis.opendocument.text', 'view'));
  }

  public function testGetCapabilitiesUrl(): void {
    static::assertSame('http://localhost:9980/hosting/capabilities', $this->response->getCapabilitiesUrl());
  }

  public function testGetOldProofExponent(): void {
    static::assertSame('OldExponent', $this->response->getOldProofExponent());
  }

  public function testGetOldProofModulus(): void {
    static::assertSame('OldModulus', $this->response->getOldProofModulus());
  }

  public function testGetOldProofKey(): void {
    static::assertSame('OldValue', $this->response->getOldProofKey());
  }

  public function testGetOldProofKeyRsa(): void {
    static::assertEquals(
      new WopiProofKey('OldModulus', 'OldExponent'),
      $this->response->getOldProofKeyRsa()
    );
  }

  public function testGetProofExponent(): void {
    static::assertSame('Exponent', $this->response->getProofExponent());
  }

  public function testGetProofModulus(): void {
    static::assertSame('Modulus', $this->response->getProofModulus());
  }

  public function testGetProofKey(): void {
    static::assertSame('Value', $this->response->getProofKey());
  }

  public function testGetProofKeyRsa(): void {
    static::assertEquals(
      new WopiProofKey('Modulus', 'Exponent'),
      $this->response->getProofKeyRsa()
    );
  }

  public function testHasProofKeys(): void {
    static::assertTrue($this->response->hasProofKeys());
  }

}
