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

use Civi\Civioffice\Wopi\Util\DotNetTimeConverter;
use Civi\Civioffice\Wopi\WopiProofKey;
use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;

/**
 * Validates a WOPI proof as described in the specification.
 *
 * @see https://learn.microsoft.com/en-us/microsoft-365/cloud-storage-partner-program/online/scenarios/proofkeys
 */
final class WopiProofValidator {

  /**
   * @throws \InvalidArgumentException
   *   If current or old proof key is invalid.
   */
  public function isValid(
    ProofValidatorInput $proofValidatorInput,
    WopiProofKey $proofKey,
    WopiProofKey $oldProofKey
  ): bool {
    if (!$this->verifyTimestamp($proofValidatorInput)) {
      return FALSE;
    }

    $expectedProof = $this->getExpectedProof($proofValidatorInput);
    $rsa = $this->createRsa($proofKey);
    $oldRsa = $this->createRsa($oldProofKey);

    $proof = (string) base64_decode($proofValidatorInput->proof, TRUE);
    $oldProof = (string) base64_decode($proofValidatorInput->oldProof, TRUE);

    // The X-WOPI-Proof value using the current public key.
    return $rsa->verify($expectedProof, $proof)
      // The X-WOPI-ProofOld value using the current public key.
      || $rsa->verify($expectedProof, $oldProof)
      // The X-WOPI-Proof value using the old public key.
      || $oldRsa->verify($expectedProof, $proof);
  }

  private function getExpectedProof(ProofValidatorInput $proofValidatorInput): string {
    $accessToken = $proofValidatorInput->accessToken;
    $url = strtoupper($proofValidatorInput->url);
    // Timestamp as unsigned long long (64 bit, big endian byte order)
    $timestamp = pack('J', $proofValidatorInput->timestamp);

    return sprintf(
      '%s%s%s%s%s%s',

      // Length of access token as unsigned long (32 bit, big endian byte order)
      pack('N', strlen($accessToken)),
      $accessToken,

      // Length of the full request URL as unsigned long (32 bit, big endian byte order)
      pack('N', strlen($url)),
      $url,

      // Length of the WOPI timestamp as unsigned long (32 bit, big endian byte order)
      pack('N', strlen($timestamp)),
      $timestamp
    );
  }

  /**
   * Construct the RSA public key from modulus and exponent.
   *
   * @throws \InvalidArgumentException
   */
  private function createRsa(WopiProofKey $key): RSA {
    $modulus = base64_decode($key->modulus, TRUE);
    if (FALSE === $modulus) {
      throw new \InvalidArgumentException('Invalid modulus in WOPI proof public key');
    }

    $exponent = base64_decode($key->exponent, TRUE);
    if (FALSE === $exponent) {
      throw new \InvalidArgumentException('Invalid exponent in WOPI proof public key');
    }

    $rsa = new RSA();
    if (!$rsa->loadKey([
      'e' => new BigInteger($exponent, 256),
      'n' => new BigInteger($modulus, 256),
    ])) {
      throw new \InvalidArgumentException('Invalid WOPI proof public key');
    }

    $rsa->setSignatureMode(RSA::SIGNATURE_PKCS1);
    $rsa->setHash('sha256');

    return $rsa;
  }

  /**
   * Verify WOPI timestamp and make sure that it was sent within the last 20
   * minutes as demanded in the specification.
   */
  private function verifyTimestamp(ProofValidatorInput $proofValidatorInput): bool {
    $timestamp = $proofValidatorInput->timestamp;
    if (!is_numeric($timestamp)) {
      return FALSE;
    }

    // WOPI timestamps are the number of 100 nanosecond units passed since 1/1/0001.
    $date = DotNetTimeConverter::toDateTime((int) $timestamp);

    $timestampDiff = abs(\CRM_Utils_Time::time() - $date->getTimestamp());
    if ($timestampDiff > 20 * 60) {
      return FALSE;
    }

    return TRUE;
  }

}
