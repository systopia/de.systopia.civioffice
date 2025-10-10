<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
 *
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Lesser General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\Civioffice\PhpWord\Util;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Civioffice\PhpWord\Util\XmlUtil
 */
final class XmlUtilTest extends TestCase {

  public function testBlockElement(): void {
    $expected = <<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:r>
              <w:t>foo</w:t>
            </w:r>
            <w:r>
              <w:t>bar</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD;

    $xml = <<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:ins w:id="0">
              <w:r>
                <w:t>foo</w:t>
              </w:r>
            </w:ins>
            <w:r>
              <w:t>bar</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD;

    static::assertSame($expected, XmlUtil::blockElement($xml, 'w:ins'));
  }

  public function testDropElement(): void {
    $expected = <<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:r>
              <w:t>foo</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD;

    $xml = <<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:pPr>
              <w:i/>
            </w:pPr>
            <w:r>
              <w:t>foo</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD;

    static::assertSame($expected, XmlUtil::dropElement($xml, 'w:pPr'));
  }

  public function testDropSimpleElement(): void {
    $expected = <<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:pPr>
            </w:pPr>
            <w:r>
              <w:t>foo</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD;

    $xml1 = <<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:pPr>
              <w:i/>
            </w:pPr>
            <w:r>
              <w:t>foo</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD;

    static::assertSame($expected, XmlUtil::dropEmptyElement($xml1, 'w:i'));

    $xml2 = <<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:pPr>
              <w:i foo="bar"/>
            </w:pPr>
            <w:r>
              <w:t>foo</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD;

    // Don't know why phpstan reports these errors.
    // @phpstan-ignore staticMethod.impossibleType, argument.unresolvableType
    static::assertSame($expected, XmlUtil::dropEmptyElement($xml2, 'w:i'));
  }

  public function testFlattenXml(): void {
    $xml = <<<EOD
      <w:document>
        <w:body>
          <w:p>
            <w:r>
              <w:t>foo</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD;

    $expected = '<w:document><w:body><w:p><w:r><w:t>foo</w:t></w:r></w:p></w:body></w:document>';
    static::assertSame($expected, XmlUtil::flattenXml($xml));
  }

}
