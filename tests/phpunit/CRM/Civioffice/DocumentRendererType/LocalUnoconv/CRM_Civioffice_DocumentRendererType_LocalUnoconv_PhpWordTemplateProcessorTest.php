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

namespace CRM\Civioffice\DocumentRendererType\LocalUnoconv;

use Civi\Civioffice\DocumentRendererType\LocalUnoconv\TestablePhpWordTemplateProcessor;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CRM_Civioffice_DocumentRendererType_LocalUnoconv_PhpWordTemplateProcessor
 */
final class CRM_Civioffice_DocumentRendererType_LocalUnoconv_PhpWordTemplateProcessorTest extends TestCase {

  public function testReplaceSimple(): void {
    $mainPart = <<<EOD
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
        <w:t>Foo {place.holder} bar</w:t>
      </w:r>
    </w:p>
  </w:body>
</w:document>
EOD;

    $templateProcessor = new TestablePhpWordTemplateProcessor($mainPart);
    $templateProcessor->civiTokensToMacros();
    $templateProcessor->replaceHtmlToken('place.holder', 'test 123');

    $expectedMainPart = <<<EOD
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
        <w:t>Foo test 123 bar</w:t>
      </w:r>
    </w:p>
  </w:body>
</w:document>
EOD;

    static::assertXmlStringEqualsXmlString($expectedMainPart, $templateProcessor->getMainPart());
  }

  public function testReplaceSpan(): void {
    $mainPart = <<<EOD
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
        <w:t>Foo {place.holder} bar</w:t>
      </w:r>
    </w:p>
  </w:body>
</w:document>
EOD;

    $templateProcessor = new TestablePhpWordTemplateProcessor($mainPart);
    $templateProcessor->civiTokensToMacros();
    $templateProcessor->replaceHtmlToken('place.holder', '<span>test 123</span>');

    $expectedMainPart = <<<EOD
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
        <w:t>Foo test 123 bar</w:t>
      </w:r>
    </w:p>
  </w:body>
</w:document>
EOD;

    static::assertXmlStringEqualsXmlString($expectedMainPart, $templateProcessor->getMainPart());
  }

  /**
   * When a token is replaced with a paragraph, property tags (pPr, rRr) should
   * be copied into each paragraph/text run.
   */
  public function testReplaceParagraphEnclosed(): void {
    $mainPart = <<<EOD
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
        <w:t>Foo {place.holder} bar</w:t>
      </w:r>
    </w:p>
  </w:body>
</w:document>
EOD;

    $templateProcessor = new TestablePhpWordTemplateProcessor($mainPart);
    $templateProcessor->civiTokensToMacros();
    $templateProcessor->replaceHtmlToken('place.holder', '<p>test 123</p>');

    $expectedMainPart = <<<EOD
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
        <w:t>Foo </w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:pStyle w:val="Normal"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:b w:val="true"/>
        </w:rPr>
        <w:t xml:space="preserve">test 123</w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:pStyle w:val="Normal"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:b w:val="true"/>
        </w:rPr>
        <w:t xml:space="preserve"> bar</w:t>
      </w:r>
    </w:p>
  </w:body>
</w:document>
EOD;

    static::assertXmlStringEqualsXmlString($expectedMainPart, $templateProcessor->getMainPart());
  }

  /**
   * Test that the result contains no empty paragraph, if the token is at the
   * beginning of a paragraph.
   *
   * When a token is replaced with a paragraph, property tags (pPr, rRr) should
   * be copied into each paragraph/text run.
   */
  public function testReplaceParagraphStart(): void {
    $mainPart = <<<EOD
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
        <w:t>{place.holder} bar</w:t>
      </w:r>
    </w:p>
  </w:body>
</w:document>
EOD;

    $templateProcessor = new TestablePhpWordTemplateProcessor($mainPart);
    $templateProcessor->civiTokensToMacros();
    $templateProcessor->replaceHtmlToken('place.holder', '<p>test 123</p>');

    $expectedMainPart = <<<EOD
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
        <w:t xml:space="preserve">test 123</w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:pStyle w:val="Normal"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:b w:val="true"/>
        </w:rPr>
        <w:t xml:space="preserve"> bar</w:t>
      </w:r>
    </w:p>
  </w:body>
</w:document>
EOD;

    static::assertXmlStringEqualsXmlString($expectedMainPart, $templateProcessor->getMainPart());
  }

  /**
   * Test that the result contains no empty paragraph, if the token is at the
   * end of a paragraph.
   *
   * When a token is replaced with a paragraph, property tags (pPr, rRr) should
   * be copied into each paragraph/text run.
   */
  public function testReplaceParagraphEnd(): void {
    $mainPart = <<<EOD
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

    $templateProcessor = new TestablePhpWordTemplateProcessor($mainPart);
    $templateProcessor->civiTokensToMacros();
    $templateProcessor->replaceHtmlToken('place.holder', '<p>test 123</p>');

    $expectedMainPart = <<<EOD
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
        <w:t>Foo </w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:pStyle w:val="Normal"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:b w:val="true"/>
        </w:rPr>
        <w:t xml:space="preserve">test 123</w:t>
      </w:r>
    </w:p>
  </w:body>
</w:document>
EOD;

    static::assertXmlStringEqualsXmlString($expectedMainPart, $templateProcessor->getMainPart());
  }

}
