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

use Civi\Civioffice\Wopi\WopiHeaders;
use Symfony\Component\HttpFoundation\Request;

/**
 * https://github.com/nagi1/laravel-wopi/blob/77629b2a5dbaf759b01826f162c4678cd3c9b874/src/Support/ProofValidatorInput.php
 */
final class ProofValidatorInput {

  public function __construct(
    public string $accessToken,
    public string $timestamp,
    public string $url,
    public string $proof,
    public string $oldProof
  ) {}

  public static function fromRequest(Request $request): ?self {
    $url = $request->getUri();
    $accessToken = $request->query->get('access_token');
    $timestamp = $request->headers->get(WopiHeaders::HEADER_TIMESTAMP);
    $proof = $request->headers->get(WopiHeaders::HEADER_PROOF);
    $oldProof = $request->headers->get(WopiHeaders::HEADER_PROOF_OLD);

    if (!is_string($accessToken) || '' === $accessToken
      || NULL === $proof || '' === $proof
      || NULL === $oldProof || '' === $oldProof
      || NULL === $timestamp || '' === $timestamp
    ) {
      return NULL;
    }

    return new self($accessToken, $timestamp, $url, $proof, $oldProof);
  }

}
