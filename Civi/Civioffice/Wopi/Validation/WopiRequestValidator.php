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

namespace Civi\Civioffice\Wopi\Validation;

use Civi\Civioffice\Wopi\Discovery\WopiDiscoveryServiceInterface;
use Civi\Civioffice\Wopi\UserInfoService;
use Civi\Civioffice\Wopi\WopiAccessTokenService;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class WopiRequestValidator {

  private WopiAccessTokenService $accessTokenService;

  private WopiDiscoveryServiceInterface $discoveryService;

  private WopiProofValidator $proofValidator;

  private UserInfoService $userInfoService;

  public function __construct(
    WopiAccessTokenService $accessTokenService,
    WopiDiscoveryServiceInterface $discoveryService,
    WopiProofValidator $proofValidator,
    UserInfoService $userInfoService
  ) {
    $this->accessTokenService = $accessTokenService;
    $this->discoveryService = $discoveryService;
    $this->proofValidator = $proofValidator;
    $this->userInfoService = $userInfoService;
  }

  /**
   * @return array{int, int, int}
   *   File ID, contact ID, and editor ID.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
   */
  public function decodeAndValidateAccessToken(Request $request, string $wopiFileId): array {
    $accessToken = $request->query->get('access_token');
    if (!is_string($accessToken) || '' === $accessToken) {
      throw new BadRequestHttpException('Access token is missing');
    }

    try {
      [$fileId, $contactId, $editorId] = $this->accessTokenService->decodeToken($accessToken);
    }
    catch (\InvalidArgumentException $e) {
      throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Access token is invalid', $e);
    }

    if ((string) $fileId !== $wopiFileId) {
      throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Access token is invalid');
    }

    $this->assertValidContactId($contactId);
    $this->assertWopiProof($request, $editorId);

    return [$fileId, $contactId, $editorId];
  }

  private function assertValidContactId(int $contactId): void {
    if (!$this->userInfoService->isValidContactId($contactId)) {
      // May only happen if user gets deleted.
      throw new NotFoundHttpException('Invalid contact ID');
    }
  }

  private function assertWopiProof(Request $request, int $editorId): void {
    $proofValidatorInput = ProofValidatorInput::fromRequest($request);
    try {
      $discoveryResponse = $this->discoveryService->getDiscoveryByEditorId($editorId);
    }
    catch (ClientExceptionInterface $e) {
      throw new ServiceUnavailableHttpException(300, 'Cannot reach WOPI discovery service', $e);
    }

    if ($discoveryResponse->hasProofKeys()) {
      if (NULL === $proofValidatorInput || !$this->proofValidator->isValid(
          $proofValidatorInput,
          $discoveryResponse->getProofKeyRsa(),
          $discoveryResponse->getOldProofKeyRsa()
        )) {
        throw new HttpException(Response::HTTP_UNAUTHORIZED, 'WOPI proof is invalid');
      }
    }
  }

}
