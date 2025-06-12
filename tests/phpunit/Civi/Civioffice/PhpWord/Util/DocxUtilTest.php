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
 * @covers \Civi\Civioffice\PhpWord\Util\DocxUtil
 */
final class DocxUtilTest extends TestCase {

  /**
   * @dataProvider provideCombineRunsXml
   */
  public function testCombineRuns(string $xml, string $expected): void {
    static::assertSame($expected, DocxUtil::combineRuns($xml));
    static::assertSame([$expected, $expected], DocxUtil::combineRuns([$xml, $xml]));
  }

  /**
   * @phpstan-return iterable<array{string, string}>
   */
  public function provideCombineRunsXml(): iterable {
    // Run without rPr elements shall be combined.
    yield [<<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:pPr>
              <w:pStyle w:val="Normal"/>
            </w:pPr>
            <w:r>
              <w:t>Foo {place.</w:t>
            </w:r>
            <w:r>
              <w:t>holder</w:t>
            </w:r>
            <w:r>
              <w:t>}</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD,
      <<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:pPr>
              <w:pStyle w:val="Normal"/>
            </w:pPr>
            <w:r>
              <w:t>Foo {place.holder}</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD,
    ];

    $expectedWithStyle = <<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:pPr>
              <w:pStyle w:val="Normal"/>
            </w:pPr>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t>Foo {place.holder}</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD;

    // With rsidDel attribute.
    yield [<<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:pPr>
              <w:pStyle w:val="Normal"/>
            </w:pPr>
            <w:r w:rsidRPr="0123456F">
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t>Foo {place.</w:t>
            </w:r>
            <w:r w:rsidDel="0123456F">
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t>holder}</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD,
      $expectedWithStyle,
    ];

    // With rsidRPr attribute.
    yield [<<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:pPr>
              <w:pStyle w:val="Normal"/>
            </w:pPr>
            <w:r w:rsidRPr="0123456F">
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t>Foo {place.</w:t>
            </w:r>
            <w:r w:rsidRPr="1123456F">
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t>holder}</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD,
      $expectedWithStyle,
    ];

    // With rsidR attribute.
    yield [<<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:pPr>
              <w:pStyle w:val="Normal"/>
            </w:pPr>
            <w:r w:rsidRPr="0123456F">
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t>Foo {place.</w:t>
            </w:r>
            <w:r w:rsidR="0123456F">
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t>holder}</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD,
      $expectedWithStyle,
    ];

    // Run with different rPr element shall not be changed.
    $xml = <<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:pPr>
              <w:pStyle w:val="Normal"/>
            </w:pPr>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t>Foo {place.</w:t>
            </w:r>
            <w:r>
              <w:pPr>
                <w:i/>
              </w:pPr>
              <w:t>holder}</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD;
    yield [$xml, $xml];
  }

}
