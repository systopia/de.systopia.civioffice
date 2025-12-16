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

namespace Civi\Civioffice\Wopi\Request;

use Civi\Civioffice\FileManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @phpstan-import-type fileT from FileManagerInterface
 */
abstract class AbstractWopiRequestHandler implements WopiRequestHandlerInterface {

  protected FileManagerInterface $fileManager;

  public function __construct(FileManagerInterface $fileManager) {
    $this->fileManager = $fileManager;
  }

  /**
   * @inheritDoc
   * @throws \CRM_Core_Exception
   */
  public function getFile(int $fileId, int $contactId, Request $request): Response {
    return new BinaryFileResponse($this->getFileEntity($fileId)['full_path']);
  }

  public function putFile(int $fileId, int $contactId, string $content, Request $request): array {
    $fileInfo = $this->checkFileInfo($fileId, $contactId, $request);
    if (!$fileInfo['UserCanWrite']) {
      throw new NotFoundHttpException('File is read only');
    }

    try {
      return $this->doPutFile($fileId, $contactId, $content, $request);
    }
    catch (\Exception $e) {
      if ($e instanceof HttpExceptionInterface) {
        throw $e;
      }

      throw new ServiceUnavailableHttpException(300, $e->getMessage(), $e);
    }
  }

  /**
   * Only called if "UserCanWrite" in the return value of checkFileInfo() is
   * TRUE.
   *
   * @return array<string, mixed>
   *
   * @throws \Exception
   */
  abstract protected function doPutFile(int $fileId, int $contactId, string $content, Request $request): array;

  /**
   * @throws \CRM_Core_Exception
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *
   * @phpstan-return fileT
   */
  protected function getFileEntity(int $fileId): array {
    $fileEntity = $this->fileManager->get($fileId);
    if ($fileEntity === NULL) {
      throw new NotFoundHttpException("File with ID $fileId not found");
    }

    return $fileEntity;
  }

}
