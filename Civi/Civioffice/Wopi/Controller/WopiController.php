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

namespace Civi\Civioffice\Wopi\Controller;

use Civi\Civioffice\Controller\PageControllerInterface;
use Civi\Civioffice\Wopi\Request\WopiRequestHandlerInterface;
use Civi\Civioffice\Wopi\Validation\WopiRequestValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class WopiController implements PageControllerInterface {

  private WopiRequestHandlerInterface $requestHandler;

  private WopiRequestValidator $requestValidator;

  public function __construct(WopiRequestHandlerInterface $requestHandler, WopiRequestValidator $requestValidator) {
    $this->requestHandler = $requestHandler;
    $this->requestValidator = $requestValidator;
  }

  /**
   * @inheritDoc
   */
  public function handle(Request $request): Response {
    $this->assertHttpMethod($request);
    [$wopiFileId, $hasContentsPath] = $this->parseRequestPath($request);
    [$fileId, $contactId] = $this->requestValidator->decodeAndValidateAccessToken($request, $wopiFileId);

    if (!$hasContentsPath) {
      if ('POST' === $request->getMethod()) {
        throw new BadRequestHttpException('PutRelativeFile operation is not allowed');
      }

      return new JsonResponse($this->requestHandler->checkFileInfo($fileId, $contactId, $request));
    }

    if ($request->isMethod('POST')) {
      $fileInfo = $this->requestHandler->checkFileInfo($fileId, $contactId, $request);
      if (!$fileInfo['UserCanWrite']) {
        throw new AccessDeniedHttpException('File is read only');
      }

      return new JsonResponse($this->requestHandler->putFile($fileId, $contactId, $request->getContent(), $request));
    }

    return $this->requestHandler->getFile($fileId, $contactId, $request);
  }

  private function assertHttpMethod(Request $request): void {
    $allowedMethods = ['GET', 'POST'];
    if (!in_array($request->getMethod(), $allowedMethods, TRUE)) {
      throw new MethodNotAllowedHttpException($allowedMethods);
    }
  }

  /**
   * @return array{string, bool}
   *   WOPI file ID and flag if path contains "/contents".
   */
  private function parseRequestPath(Request $request): array {
    $matches = [];
    preg_match(
      '~/wopi/files/(?<fileId>[[:alnum:]]+)(?<contents>/contents)?($|\?)~',
      $request->getRequestUri(),
      $matches
    );
    if (!isset($matches['fileId'])) {
      throw new BadRequestHttpException('File ID is missing');
    }

    return [$matches['fileId'], ($matches['contents'] ?? '') !== ''];
  }

}
