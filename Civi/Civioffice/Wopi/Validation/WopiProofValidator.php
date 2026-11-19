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
    $publicKey = $this->createPublicKey($proofKey);
    $oldPublicKey = $this->createPublicKey($oldProofKey);

    $proof = (string) base64_decode($proofValidatorInput->proof, TRUE);
    $oldProof = (string) base64_decode($proofValidatorInput->oldProof, TRUE);

    // The X-WOPI-Proof value using the current public key.
    return openssl_verify($expectedProof, $proof, $publicKey, OPENSSL_ALGO_SHA256) === 1
      // The X-WOPI-ProofOld value using the current public key.
      || openssl_verify($expectedProof, $oldProof, $publicKey, OPENSSL_ALGO_SHA256) === 1
      // The X-WOPI-Proof value using the old public key.
      || openssl_verify($expectedProof, $proof, $oldPublicKey, OPENSSL_ALGO_SHA256) === 1;
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
   * Construct an OpenSSL RSA public key from modulus and exponent.
   *
   * @throws \InvalidArgumentException
   */
  private function createPublicKey(WopiProofKey $key): \OpenSSLAsymmetricKey {
    $modulus = base64_decode($key->modulus, TRUE);
    if (FALSE === $modulus || '' === $modulus) {
      throw new \InvalidArgumentException('Invalid modulus in WOPI proof public key');
    }

    $exponent = base64_decode($key->exponent, TRUE);
    if (FALSE === $exponent || '' === $exponent) {
      throw new \InvalidArgumentException('Invalid exponent in WOPI proof public key');
    }

    // Encode SubjectPublicKeyInfo DER from RSA (n, e) and wrap as PEM.
    $rsaPublicKey = self::derEncode(0x30, self::derInteger($modulus) . self::derInteger($exponent));
    // OID rsaEncryption with NULL parameters.
    $algorithmIdentifier = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
    $subjectPublicKeyInfo = self::derEncode(
      0x30,
      $algorithmIdentifier . self::derEncode(0x03, "\x00" . $rsaPublicKey)
    );
    $pem = "-----BEGIN PUBLIC KEY-----\n"
      . chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n")
      . "-----END PUBLIC KEY-----\n";

    $publicKey = openssl_pkey_get_public($pem);
    if (FALSE === $publicKey) {
      throw new \InvalidArgumentException('Invalid WOPI proof public key');
    }

    return $publicKey;
  }

  private static function derInteger(string $unsignedBytes): string {
    $bytes = ltrim($unsignedBytes, "\x00");
    if ('' === $bytes) {
      $bytes = "\x00";
    }
    elseif (ord($bytes[0]) >= 0x80) {
      $bytes = "\x00" . $bytes;
    }
    return self::derEncode(0x02, $bytes);
  }

  private static function derEncode(int $tag, string $content): string {
    $len = strlen($content);
    if ($len < 0x80) {
      $lenEncoded = pack('C', $len);
    }
    else {
      $lenBytes = ltrim(pack('N', $len), "\x00");
      $lenEncoded = pack('C', 0x80 | strlen($lenBytes)) . $lenBytes;
    }
    return pack('C', $tag) . $lenEncoded . $content;
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
