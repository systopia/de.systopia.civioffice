<?php
/*
 * Copyright (C) 2024 SYSTOPIA GmbH
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

namespace Civi\Civioffice\PhpWord;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Civioffice\PhpWord\StyleMerger
 */
final class StyleMergerTest extends TestCase {

  public function testMerge(): void {
    $style = <<<EOD
  <w:pPr>
    <w:pStyle w:val="Normal"/>
    <w:keeplines/>
  </w:pPr>
EOD;

    $styleToMerge = <<<EOD
  <w:pPr>
    <w:pStyle w:val="Test"/>
    <w:numPr>
      <w:ilvl w:val="0"/>
      <w:numId w:val="1"/>
    </w:numPr>
  </w:pPr>
EOD;

    $expectedStyle = <<<EOD
  <w:pPr>
    <w:pStyle w:val="Normal"/>
    <w:keeplines/>
    <w:numPr>
      <w:ilvl w:val="0"/>
      <w:numId w:val="1"/>
    </w:numPr>
  </w:pPr>
EOD;

    $styleMerger = new StyleMerger($style);
    $styleMerger->merge($styleToMerge);
    static::assertXmlStringEqualsXmlString($expectedStyle, $styleMerger->getStyleString());
  }

}
