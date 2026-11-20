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

use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Civioffice\Wopi\Util\DerEncoder
 */
final class DerEncoderTest extends TestCase {

  /**
   * @dataProvider provideIntegers
   */
  public function testEncodeInteger(string $input, string $expected): void {
    static::assertSame($expected, DerEncoder::encodeInteger($input));
  }

  /**
   * @return array<string, array{string, string}>
   */
  public static function provideIntegers(): array {
    return [
      'empty becomes zero' => ['', "\x02\x01\x00"],
      'single zero' => ["\x00", "\x02\x01\x00"],
      'redundant leading zeros stripped' => ["\x00\x00\x01", "\x02\x01\x01"],
      'exponent 65537' => ["\x01\x00\x01", "\x02\x03\x01\x00\x01"],
      'msb set gets leading zero' => ["\x80", "\x02\x02\x00\x80"],
      'redundant leading zeros with msb set' => ["\x00\x00\x80\x01", "\x02\x03\x00\x80\x01"],
    ];
  }

  /**
   * @dataProvider provideLengths
   */
  public function testEncodeLength(int $length, string $expectedHeader): void {
    $content = str_repeat('a', $length);
    static::assertSame($expectedHeader . $content, DerEncoder::encode(0x04, $content));
  }

  /**
   * @return array<string, array{int, string}>
   */
  public static function provideLengths(): array {
    return [
      'short length 0' => [0, "\x04\x00"],
      'short length 127' => [127, "\x04\x7f"],
      'long length 128' => [128, "\x04\x81\x80"],
      'long length 256' => [256, "\x04\x82\x01\x00"],
    ];
  }

  public function testEncodeRsaPublicKeyPem(): void {
    $modulus = base64_decode(
      '0HOWUPFFgmSYHbLZZzdWO/HUOr8YNfx5NAl7GUytooHZ7B9QxQKTJpj0NIJ4XEskQW8e4dLzRrPbNOOJ+KpWHttXz8Ho'
      . 'QXkkZV/gYNxaNHJ8/pRXGMZzfVM5vchhx/2C7ULPTrpBsSpmfWQ6ShaVoQzfThFUd0MsBvIN7HVtqzPx9jbSV04wAqyN'
      . 'jcro7F3iu9w7AEsMejHbFlWoN+J05dP5ixryF7+2U5RVmjMt7/dYUdCoiXvCMt2CaVr0XEG6udHU4iDKVKZjmUBc7cT'
      . 'WRzhqEL7lZ1yQfylp38Nd2xxVJ0sSU7OkC1bBDlePcYGaF3JjJgsmp/H5BNnlW9gSxQ==',
      TRUE
    );
    $exponent = base64_decode('AQAB', TRUE);
    static::assertIsString($modulus);
    static::assertIsString($exponent);

    $pem = DerEncoder::encodeRsaPublicKeyPem($modulus, $exponent);
    $key = openssl_pkey_get_public($pem);
    static::assertNotFalse($key);

    $details = openssl_pkey_get_details($key);
    static::assertIsArray($details);
    static::assertSame($modulus, $details['rsa']['n']);
    static::assertSame($exponent, $details['rsa']['e']);
  }

}
