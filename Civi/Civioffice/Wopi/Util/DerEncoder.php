<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
 *
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\Civioffice\Wopi\Util;

/**
 * Minimal ASN.1 DER encoder for RSA public keys.
 */
final class DerEncoder {

  private const TAG_INTEGER = 0x02;

  private const TAG_BIT_STRING = 0x03;

  private const TAG_SEQUENCE = 0x30;

  // OID rsaEncryption (1.2.840.113549.1.1.1) with NULL parameters.
  private const RSA_ALGORITHM_IDENTIFIER = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";

  /**
   * Encode an RSA public key from unsigned big-endian modulus and exponent bytes into PEM.
   */
  public static function encodeRsaPublicKeyPem(string $modulus, string $exponent): string {
    $rsaPublicKey = self::encodeSequence(self::encodeInteger($modulus) . self::encodeInteger($exponent));
    $subjectPublicKeyInfo = self::encodeSequence(
      self::RSA_ALGORITHM_IDENTIFIER . self::encodeBitString($rsaPublicKey)
    );

    return "-----BEGIN PUBLIC KEY-----\n"
      . chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n")
      . "-----END PUBLIC KEY-----\n";
  }

  public static function encodeInteger(string $unsignedBytes): string {
    $bytes = ltrim($unsignedBytes, "\x00");
    if ('' === $bytes) {
      $bytes = "\x00";
    }
    elseif (ord($bytes[0]) >= 0x80) {
      $bytes = "\x00" . $bytes;
    }
    return self::encode(self::TAG_INTEGER, $bytes);
  }

  public static function encodeBitString(string $bytes): string {
    // First byte indicates 0 unused bits.
    return self::encode(self::TAG_BIT_STRING, "\x00" . $bytes);
  }

  public static function encodeSequence(string $content): string {
    return self::encode(self::TAG_SEQUENCE, $content);
  }

  public static function encode(int $tag, string $content): string {
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

}
