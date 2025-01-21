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

namespace Civi\Civioffice\Wopi;

use Civi\Crypto\CryptoJwt;
use Civi\Crypto\Exception\CryptoException;

final class WopiAccessTokenService {

  private CryptoJwt $cryptoJwt;

  private UserInfoService $userInfoService;

  public function __construct(CryptoJwt $cryptoJwt, UserInfoService $userInfoService) {
    $this->cryptoJwt = $cryptoJwt;
    $this->userInfoService = $userInfoService;
  }

  public function generateToken(int $fileId, string $discoveryIdentifier, int $expirationTime): string {
    return $this->cryptoJwt->encode([
      'exp' => $expirationTime,
      'sub' => $this->userInfoService->getContactId(),
      'fid' => $fileId,
      'dsc' => $discoveryIdentifier,
    ]);
  }

  /**
   * @return array{int, int, string}
   *   File ID, contact ID, and discovery identifier.
   *
   * @throws \InvalidArgumentException
   */
  public function decodeToken(string $token): array {
    try {
      $claims = $this->cryptoJwt->decode($token);
    }
    catch (CryptoException $e) {
      throw new \InvalidArgumentException('Invalid token: ' . $e->getMessage(), $e->getCode(), $e);
    }

    if (!is_int($claims['sub'] ?? NULL) || !is_int($claims['fid'] ?? NULL) || !is_string($claims['dsc'] ?? NULL)) {
      throw new \InvalidArgumentException('Invalid token');
    }

    return [$claims['fid'], $claims['sub'], $claims['dsc']];
  }

}
