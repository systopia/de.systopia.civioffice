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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

abstract class AbstractWopiController implements PageControllerInterface {

  /**
   * @inheritDoc
   */
  public function handle(Request $request): Response {
    $allowedMethods = ['GET', 'POST'];
    if (!in_array($request->getMethod(), $allowedMethods, TRUE)) {
      throw new MethodNotAllowedHttpException($allowedMethods);
    }

    $matches = [];
    preg_match(
      '~/wopi/files/(?<fileId>[[:alnum:]]+)(?<contents>/contents)?($|\?)~',
      $request->getRequestUri(),
      $matches
    );
    if (!isset($matches['fileId'])) {
      throw new BadRequestHttpException('File ID is missing');
    }

    $fileId = $matches['fileId'];
    $accessToken = $request->query->get('access_token');
    if (!is_string($accessToken) || $accessToken === '') {
      throw new BadRequestHttpException('Access token is missing');
    }

    if (!isset($matches['contents']) || $matches['contents'] === '') {
      if ('POST' === $request->getMethod()) {
        throw new BadRequestHttpException('PutRelativeFile operation is not allowed');
      }

      return new JsonResponse($this->checkFileInfo($fileId, $accessToken));
    }

    if ($request->isMethod('POST')) {
      $fileInfo = $this->checkFileInfo($fileId, $accessToken);
      if (!$fileInfo['UserCanWrite']) {
        throw new AccessDeniedHttpException();
      }

      $this->putFile($fileId, $accessToken, $request->getContent());

      return new Response();
    }

    return $this->getFile($fileId, $accessToken);
  }

  /**
   * @phpstan-return array{
   *    BaseFileName: string,
   *    Size: int,
   *    UserCanWrite: bool,
   * }
   */
  abstract protected function checkFileInfo(string $wopiFileId, string $wopiAccessToken): array;

  abstract protected function getFile(string $wopiFileId, string $wopiAccessToken): Response;

  abstract protected function putFile(string $wopiFileId, string $wopiAccessToken, string $content): void;

}
