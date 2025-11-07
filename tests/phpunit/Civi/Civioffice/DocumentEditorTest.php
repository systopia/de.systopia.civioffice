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

namespace Civi\Civioffice;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \Civi\Civioffice\DocumentEditor
 */
final class DocumentEditorTest extends TestCase {

  private DocumentEditor $documentEditor;

  private DocumentEditorTypeInterface&MockObject $typeMock;

  protected function setUp(): void {
    parent::setUp();
    $this->typeMock = $this->createMock(DocumentEditorTypeInterface::class);
    $this->documentEditor = new DocumentEditor(
      123,
      'test',
      [
        'active' => TRUE,
        'fileExtensions' => ['.abc', '.def'],
        'typeConfig' => ['foo' => 'bar'],
      ],
      $this->typeMock
    );
  }

  public function testFileExtensions(): void {
    static::assertSame(['.abc', '.def'], $this->documentEditor->getFileExtensions());
    $this->documentEditor->setFileExtensions(['.ghi']);
    static::assertSame(['.ghi'], $this->documentEditor->getFileExtensions());
  }

  public function testGetId(): void {
    static::assertSame(123, $this->documentEditor->getId());
  }

  public function testGetType(): void {
    static::assertSame($this->typeMock, $this->documentEditor->getType());
  }

  public function testGetTypeConfig(): void {
    static::assertSame(['foo' => 'bar'], $this->documentEditor->getTypeConfig());
    $this->documentEditor->setTypeConfig(['foo' => 'baz']);
    static::assertSame(['foo' => 'baz'], $this->documentEditor->getTypeConfig());
  }

  public function testHandleFile(): void {
    $file = [
      'id' => 2,
      'file_type_id' => NULL,
      'mime_type' => 'text/plain',
      'uri' => 'test.txt',
      'description' => NULL,
      'upload_date' => '2025-11-04 01:02:03',
      'created_id' => NULL,
      'full_path' => '/path/to/test.txt',
    ];

    $response = new Response();
    $this->typeMock->expects(static::once())
      ->method('handleFile')
      ->with(['foo' => 'bar'], $file, 123)
      ->willReturn($response);

    static::assertSame($response, $this->documentEditor->handleFile($file));

  }

  public function testActive(): void {
    static::assertTrue($this->documentEditor->isActive());
    $this->documentEditor->setActive(FALSE);
    static::assertFalse($this->documentEditor->isActive());
  }

  public function testIsFileSupported(): void {
    $file = [
      'id' => 2,
      'file_type_id' => NULL,
      'mime_type' => 'text/plain',
      'uri' => 'test.abc',
      'description' => NULL,
      'upload_date' => '2025-11-04 01:02:03',
      'created_id' => NULL,
      'full_path' => '/path/to/test.abc',
    ];

    $this->typeMock->expects(static::once())
      ->method('isFileSupported')
      ->with(['foo' => 'bar'], $file, 123)
      ->willReturn(TRUE);

    static::assertTrue($this->documentEditor->isFileSupported($file));
  }

  public function testIsFileSupportedNoExtensionMatch(): void {
    $file = [
      'id' => 2,
      'file_type_id' => NULL,
      'mime_type' => 'text/plain',
      'uri' => 'test.txt',
      'description' => NULL,
      'upload_date' => '2025-11-04 01:02:03',
      'created_id' => NULL,
      'full_path' => '/path/to/test.txt',
    ];

    $this->typeMock->expects(static::never())->method('isFileSupported');

    static::assertFalse($this->documentEditor->isFileSupported($file));
  }

  public function testIsFileSupportedWithoutExtension(): void {
    $this->documentEditor->setFileExtensions([]);

    $file = [
      'id' => 2,
      'file_type_id' => NULL,
      'mime_type' => 'text/plain',
      'uri' => 'test.txt',
      'description' => NULL,
      'upload_date' => '2025-11-04 01:02:03',
      'created_id' => NULL,
      'full_path' => '/path/to/test.txt',
    ];

    $this->typeMock->expects(static::once())
      ->method('isFileSupported')
      ->with(['foo' => 'bar'], $file, 123)
      ->willReturn(TRUE);

    static::assertTrue($this->documentEditor->isFileSupported($file));
  }

}
