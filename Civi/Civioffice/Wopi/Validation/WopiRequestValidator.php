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

use Civi\Civioffice\Wopi\Discovery\WopiDiscoveryService;
use Civi\Civioffice\Wopi\UserInfoService;
use Civi\Civioffice\Wopi\WopiAccessTokenService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class WopiRequestValidator {

  private WopiAccessTokenService $accessTokenService;

  private WopiDiscoveryService $discoveryService;

  private WopiProofValidator $proofValidator;

  private UserInfoService $userInfoService;

  public function __construct(
    WopiAccessTokenService $accessTokenService,
    WopiDiscoveryService $discoveryService,
    WopiProofValidator $proofValidator,
    UserInfoService $userInfoService
  ) {
    $this->accessTokenService = $accessTokenService;
    $this->discoveryService = $discoveryService;
    $this->proofValidator = $proofValidator;
    $this->userInfoService = $userInfoService;
  }

  /**
   * @return array{int, int, string}
   *   File ID, contact ID, and discovery identifier.
   */
  public function decodeAndValidateAccessToken(Request $request, string $wopiFileId): array {
    $accessToken = $request->query->get('access_token');
    if (!is_string($accessToken) || $accessToken === '') {
      throw new BadRequestHttpException('Access token is missing');
    }

    try {
      [$fileId, $contactId, $discoveryIdentifier] = $this->accessTokenService->decodeToken($accessToken);
    }
    catch (\InvalidArgumentException $e) {
      throw new AccessDeniedHttpException('Access token is invalid', $e);
    }

    if ((string) $fileId !== $wopiFileId) {
      throw new AccessDeniedHttpException('Access token is invalid');
    }

    $this->assertValidContactId($contactId);
    $this->assertWopiProof($request, $discoveryIdentifier);

    return [$fileId, $contactId, $discoveryIdentifier];
  }

  private function assertValidContactId(int $contactId): void {
    if (!$this->userInfoService->isValidContactId($contactId)) {
      // May only happen if user gets deleted.
      throw new AccessDeniedHttpException('Invalid contact ID');
    }
  }

  private function assertWopiProof(Request $request, string $discoveryIdentifier): void {
    $proofValidatorInput = ProofValidatorInput::fromRequest($request);
    $discoveryResponse = $this->discoveryService->getDiscovery($discoveryIdentifier);
    if ($discoveryResponse->hasProofKeys()) {
      if (NULL === $proofValidatorInput || !$this->proofValidator->isValid(
          $proofValidatorInput,
          $discoveryResponse->getProofKeyRsa(),
          $discoveryResponse->getOldProofKeyRsa()
        )) {
        throw new AccessDeniedHttpException('WOPI proof is invalid');
      }
    }
  }

}
