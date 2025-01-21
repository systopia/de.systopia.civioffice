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

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ColaboraFileWopiController extends AbstractFileWopiController {

  public static function generateHash(int $fileId): string {
    $colaboraHashSecret = \Civi::settings()->get('colabora_hash_secret');

    return hash('sha256', "$fileId:$colaboraHashSecret");
  }

  /**
   * @inheritDoc
   */
  protected function checkFileInfo(string $wopiFileId, string $wopiAccessToken): array {
    $filename = $this->getFilename($this->getFileId($wopiFileId, $wopiAccessToken));
    $size = filesize($this->getFilename($this->getFileId($wopiFileId, $wopiAccessToken)));
    assert(is_int($size));

    return [
      'BaseFileName' => basename($filename),
      'Size' => $size,
      'UserCanWrite' => TRUE,
    ];
  }

  /**
   * @inheritDoc
   */
  protected function getFileId(string $wopiFileId, string $wopiAccessToken): int {
    $this->assertToken($wopiFileId, $wopiAccessToken);

    return (int) $wopiFileId;
  }

  private function assertToken(string $wopiFileId, string $wopiAccessToken): void {
    if (preg_match('/^[1-9][0-9]*$/', $wopiFileId) !== 1) {
      throw new NotFoundHttpException('Invalid file ID');
    }

    if (self::generateHash((int) $wopiFileId) !== $wopiAccessToken) {
      throw new AccessDeniedHttpException('Invalid access token');
    }
  }

}
