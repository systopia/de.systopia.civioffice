<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
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
 * @covers \Civi\Civioffice\PhpWord\Util\TemplateUtil
 */
final class TemplateUtilTest extends TestCase {

  /**
   * @dataProvider provideCombineRunsXml
   */
  public function testCombineRuns(string $xml, string $expected): void {
    static::assertSame($expected, TemplateUtil::combineRuns($xml));
    static::assertSame([$expected], TemplateUtil::combineRuns([$xml]));
  }

  /**
   * @phpstan-return iterable<array{string, string}>
   */
  public function provideCombineRunsXml(): iterable {
    $expected = <<<EOD
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

    yield [<<<EOD
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
              <w:t>holder}</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD,
      $expected,
    ];

    // With rsidDel attribute.
    yield [<<<EOD
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
            <w:r w:rsidDel="0123456F">
              <w:t>holder}</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD,
      $expected,
    ];

    // With rsidRPr attribute.
    yield [<<<EOD
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
            <w:r w:rsidRPr="0123456F">
              <w:t>holder}</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD,
      $expected,
    ];

    // With rsidR attribute.
    yield [<<<EOD
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
            <w:r w:rsidR="0123456F">
              <w:t>holder}</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD,
      $expected,
    ];

    // Run with rPr element shall not be changed.
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
