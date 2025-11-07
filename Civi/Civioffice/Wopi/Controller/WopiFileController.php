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
use Civi\Civioffice\Wopi\WopiHeaders;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * The following operations are implemented:
 * - CheckFileInfo
 * - GetFile
 * - PutFile
 *
 * @see https://learn.microsoft.com/en-us/microsoft-365/cloud-storage-partner-program/rest/endpoints#files-endpoint
 */
class WopiFileController implements PageControllerInterface {

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
    [$wopiFileId, $subPath] = $this->parseRequestPath($request);
    [$fileId, $contactId] = $this->requestValidator->decodeAndValidateAccessToken($request, $wopiFileId);

    if ('/contents' === $subPath) {
      if ($request->isMethod('POST')) {
        return new JsonResponse($this->requestHandler->putFile($fileId, $contactId, $request->getContent(), $request));
      }

      return $this->requestHandler->getFile($fileId, $contactId, $request);
    }

    if (NULL === $subPath && $request->isMethod('GET')) {
      return new JsonResponse($this->requestHandler->checkFileInfo($fileId, $contactId, $request));
    }

    if ($request->headers->has(WopiHeaders::HEADER_OVERRIDE)) {
      throw new HttpException(
        Response::HTTP_NOT_IMPLEMENTED,
        $request->headers->get(WopiHeaders::HEADER_OVERRIDE) . ' is not supported'
      );
    }

    if (NULL !== $subPath) {
      throw new HttpException(Response::HTTP_NOT_IMPLEMENTED, "File endpoint $subPath is not supported");
    }

    throw new HttpException(Response::HTTP_NOT_IMPLEMENTED, 'PutRelativeFile is not supported');
  }

  private function assertHttpMethod(Request $request): void {
    $allowedMethods = ['GET', 'POST'];
    if (!in_array($request->getMethod(), $allowedMethods, TRUE)) {
      throw new MethodNotAllowedHttpException($allowedMethods);
    }
  }

  /**
   * @return array{string, string|null}
   *   WOPI file ID, and sub path (e.g. "/contents") or NULL.
   */
  private function parseRequestPath(Request $request): array {
    $matches = [];
    preg_match(
      '~/wopi/files/(?<fileId>[[:alnum:]]+)(?<subPath>/[^?]*)?($|\?)~',
      $request->getRequestUri(),
      $matches,
      PREG_UNMATCHED_AS_NULL
    );
    if (!isset($matches['fileId'])) {
      throw new BadRequestHttpException('File ID is missing');
    }

    return [$matches['fileId'], $matches['subPath'] ?? NULL];
  }

}
