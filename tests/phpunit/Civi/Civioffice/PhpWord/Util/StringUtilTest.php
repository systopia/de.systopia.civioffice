<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify it under
 *  the terms of the GNU Affero General Public License as published by the Free
 *  Software Foundation, either version 3 of the License, or (at your option) any
 *  later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\Civioffice\PhpWord\Util;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Civioffice\PhpWord\Util\StringUtil
 */
final class StringUtilTest extends TestCase {

  public static function testFindPos(): void {
    $string = '<w:r><w:t>a</w.t><w:t whitespace="preserve">b</w:t><w:t>c</w.t></w:r>';
    static::assertSame(5, StringUtil::findPos($string, '<w:t'));
    static::assertSame(5, StringUtil::findPos($string, '/<w:t( whitespace="[^"]+")?>/'));
    static::assertSame(17, StringUtil::findPos($string, '/<w:t( whitespace="[^"]+")>/'));
    static::assertSame(51, StringUtil::findPos($string, '<w:t>', 6));
    static::assertSame(51, StringUtil::findPos($string, '/<w:t>/', 6));
    static::assertNull(StringUtil::findPos($string, 'abc'));
    static::assertNull(StringUtil::findPos($string, '/abc/'));
  }

  public static function testIsRegex(): void {
    static::assertTrue(StringUtil::isRegex('/a/'));
    static::assertTrue(StringUtil::isRegex('#a#'));
    static::assertFalse(StringUtil::isRegex('ZaZ'));
    static::assertFalse(StringUtil::isRegex('1a1'));
  }

}
