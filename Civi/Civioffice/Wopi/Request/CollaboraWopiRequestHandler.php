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
use Civi\Civioffice\Wopi\UserInfoService;
use Civi\Civioffice\Wopi\Util\CiviUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * @see https://sdk.collaboraonline.com/
 */
final class CollaboraWopiRequestHandler extends AbstractWopiRequestHandler {

  private CiviUrlGenerator $civiUrlGenerator;

  private UserInfoService $userInfoService;

  public function __construct(
    CiviUrlGenerator $civiUrlGenerator,
    FileManagerInterface $fileManager,
    UserInfoService $userInfoService
  ) {
    parent::__construct($fileManager);
    $this->civiUrlGenerator = $civiUrlGenerator;
    $this->userInfoService = $userInfoService;
  }

  /**
   * @inheritDoc
   * @throws \CRM_Core_Exception
   */
  public function checkFileInfo(int $fileId, int $contactId, Request $request): array {
    $fileEntity = $this->getFileEntity($fileId);
    $size = filesize($fileEntity['full_path']);
    assert(is_int($size));

    return [
      'BaseFileName' => basename($fileEntity['full_path']),
      'Size' => $size,
      'UserCanWrite' => TRUE,
      'LastModifiedTime' => $this->getLastModifiedTime($fileId),
      'SupportsRename' => FALSE,
      'UserId' => $contactId,
      'UserFriendlyName' => $this->userInfoService->getDisplayName($contactId),
      'IsAdminUser' => $this->userInfoService->isAdmin($contactId),
      'PostMessageOrigin' => $this->civiUrlGenerator->generateAbsoluteUrl(''),
    ];
  }

  /**
   * @inheritDoc
   */
  protected function doPutFile(int $fileId, int $contactId, string $content, Request $request): array {
    if ($request->headers->get('X-COOL-WOPI-IsModifiedByUser') === 'false') {
      return ['LastModifiedTime' => $this->getLastModifiedTime($fileId)];
    }

    $wopiTimestamp = $request->headers->get('X-COOL-WOPI-Timestamp');
    if (NULL !== $wopiTimestamp && $this->getLastModifiedTime($fileId) !== $wopiTimestamp) {
      throw new ConflictHttpException(json_encode(['COOLStatusCode' => 1010], JSON_THROW_ON_ERROR));
    }

    $this->fileManager->writeContent($fileId, $content);

    return ['LastModifiedTime' => $this->getLastModifiedTime($fileId)];
  }

  /**
   * @throws \CRM_Core_Exception
   */
  private function getLastModifiedTime(int $fileId): string {
    $fileEntity = $this->getFileEntity($fileId);
    $modificationTime = filemtime($fileEntity['full_path']);
    assert(is_int($modificationTime));

    return date(DATE_ATOM, $modificationTime);
  }

}
