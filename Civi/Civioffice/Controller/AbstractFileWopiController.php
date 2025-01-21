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

namespace Civi\Civioffice\Controller;

use Civi\Api4\File;
use Civi\RemoteTools\Api4\Api4;
use Civi\RemoteTools\Api4\Api4Interface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractFileWopiController extends AbstractWopiController {

  protected Api4Interface $api4;

  protected \CRM_Core_Config $config;

  public function __construct(?Api4Interface $api4 = NULL, ?\CRM_Core_Config $config = NULL) {
    $this->api4 = $api4 ?? Api4::getInstance();
    $this->config = $config ?? \CRM_Core_Config::singleton();
  }

  /**
   * Validates the token and returns the File entity ID.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  abstract protected function getFileId(string $wopiFileId, string $wopiAccessToken): int;

  protected function getFile(string $wopiFileId, string $wopiAccessToken): Response {
    return new BinaryFileResponse($this->getFilename($this->getFileId($wopiFileId, $wopiAccessToken)));
  }

  protected function putFile(string $wopiFileId, string $wopiAccessToken, string $content): void {
    file_put_contents($this->getFilename($this->getFileId($wopiFileId, $wopiAccessToken)), $content);
    $this->api4->updateEntity(File::getEntityName(), (int) $wopiFileId, [
      'upload_date' => date('Y-m-d H:i:s'),
    ]);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  protected function getFilename(int $fileId): string {
    /** @phpstan-var array{id: int, uri: string}|null $file */
    $file = $this->api4->getEntity(File::getEntityName(), $fileId);
    if ($file === NULL) {
      throw new NotFoundHttpException("File with ID $fileId not found");
    }

    return $this->getFullPath($file['uri']);
  }

  private function getFullPath(string $fileUri): string {
    $path = $this->config->customFileUploadDir . '/' . $fileUri;
    if (!file_exists($path)) {
      throw new NotFoundHttpException("No file at $path found");
    }

    return $path;
  }

}
