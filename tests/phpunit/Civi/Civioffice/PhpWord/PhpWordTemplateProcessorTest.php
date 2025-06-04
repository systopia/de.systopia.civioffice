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

namespace Civi\Civioffice\PhpWord;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Civioffice\PhpWord\PhpWordTemplateProcessor
 */
final class PhpWordTemplateProcessorTest extends TestCase {

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
              <w:t xml:space="preserve">Foo </w:t>
            </w:r>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t xml:space="preserve">test 123</w:t>
            </w:r>
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

  public function testReplaceWithSplitToken(): void {
    // There's a run that splits the token, but doesn't have a visible impact.
    // It only provides an rsidR. The token shall be detected nevertheless.
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
              <w:t>Foo {place</w:t>
            </w:r>
            <w:r w:rsidR="FEDCBA98">
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t>.holder} bar</w:t>
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
              <w:t xml:space="preserve">Foo </w:t>
            </w:r>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t xml:space="preserve">test 123</w:t>
            </w:r>
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

  public function testReplaceWithSplitToken2(): void {
    // There's a run that splits the token, but doesn't have a visible impact.
    // It only provides an rsidR. The token shall be detected nevertheless.
    $mainPart = <<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:r w:rsidRPr="00462A6E">
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t>{</w:t>
            </w:r>
            <w:proofErr w:type="spellStart"/>
            <w:proofErr w:type="gramStart"/>
            <w:r w:rsidRPr="00462A6E">
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t>place.hol</w:t>
            </w:r>
            <w:proofErr w:type="gramEnd"/>
            <w:r w:rsidRPr="00462A6E">
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t>der</w:t>
            </w:r>
            <w:proofErr w:type="spellEnd"/>
            <w:r w:rsidRPr="00462A6E">
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t>}</w:t>
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
              <w:t xml:space="preserve">Foo </w:t>
            </w:r>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t xml:space="preserve">test 123</w:t>
            </w:r>
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

  public function testReplaceSpanMultiple(): void {
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
    $templateProcessor->replaceHtmlToken('place.holder', '<span>test</span><span> 123</span>');

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
              <w:t xml:space="preserve">Foo </w:t>
            </w:r>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t xml:space="preserve">test</w:t>
            </w:r>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t xml:space="preserve"> 123</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD;

    static::assertXmlStringEqualsXmlString($expectedMainPart, $templateProcessor->getMainPart());
  }

  public function testReplaceLineBreak(): void {
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
    $templateProcessor->replaceHtmlToken('place.holder', 'test<br/>123');

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
              <w:t xml:space="preserve">Foo </w:t>
            </w:r>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t xml:space="preserve">test</w:t>
            </w:r>
            <w:br/>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t xml:space="preserve">123</w:t>
            </w:r>
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

  public function testReplaceParagraphWithLineBreak(): void {
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
    $templateProcessor->replaceHtmlToken('place.holder', '<p>test<br/>123</p>');

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
              <w:t xml:space="preserve">Foo </w:t>
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
              <w:t xml:space="preserve">test</w:t>
            </w:r>
            <w:br/>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t xml:space="preserve">123</w:t>
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
              <w:t xml:space="preserve">Foo </w:t>
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
              <w:t xml:space="preserve">Foo </w:t>
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

  public function testStrong(): void {
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
                <w:color w:val="FF0000"/>
              </w:rPr>
              <w:t>Foo {place.holder} bar</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD;

    $templateProcessor = new TestablePhpWordTemplateProcessor($mainPart);
    $templateProcessor->civiTokensToMacros();
    $templateProcessor->replaceHtmlToken('place.holder', '<strong>bold</strong>');

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
                <w:color w:val="FF0000"/>
              </w:rPr>
              <w:t xml:space="preserve">Foo </w:t>
            </w:r>
            <w:r>
              <w:rPr>
                <w:b w:val="1"/>
                <w:bCs w:val="1"/>
                <w:color w:val="FF0000"/>
              </w:rPr>
              <w:t xml:space="preserve">bold</w:t>
            </w:r>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
                <w:color w:val="FF0000"/>
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
   * Tests replace with a paragraph where the paragraph that contains the
   * placeholder has a paragraph style that has a text run style.
   */
  public function testReplaceParagraphWithTextRunStyle(): void {
    $mainPart = <<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:pPr>
              <w:pStyle w:val="Normal"/>
              <w:rPr>
                <w:rFonts w:ascii="Liberation Sans" w:hAnsi="Liberation Sans"/>
              </w:rPr>
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
              <w:rPr>
                <w:rFonts w:ascii="Liberation Sans" w:hAnsi="Liberation Sans"/>
              </w:rPr>
            </w:pPr>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t xml:space="preserve">Foo </w:t>
            </w:r>
          </w:p>
          <w:p>
            <w:pPr>
              <w:pStyle w:val="Normal"/>
              <w:rPr>
                <w:rFonts w:ascii="Liberation Sans" w:hAnsi="Liberation Sans"/>
              </w:rPr>
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

  /**
   * Tests replace with a paragraph that has a text run style where the
   * paragraph that contains the placeholder has a paragraph style that has a
   * text run style.
   */
  public function testReplaceParagraphWithRunStyleAndStrong(): void {
    $mainPart = <<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:pPr>
              <w:pStyle w:val="Normal"/>
              <w:rPr>
                <w:rFonts w:ascii="Liberation Sans" w:hAnsi="Liberation Sans"/>
              </w:rPr>
            </w:pPr>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
                <w:color w:val="FF0000"/>
              </w:rPr>
              <w:t>Foo {place.holder}</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD;

    $templateProcessor = new TestablePhpWordTemplateProcessor($mainPart);
    $templateProcessor->civiTokensToMacros();
    $templateProcessor->replaceHtmlToken('place.holder', '<p><strong>test 123</strong></p>');

    $expectedMainPart = <<<EOD
      <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
          <w:p>
            <w:pPr>
              <w:pStyle w:val="Normal"/>
              <w:rPr>
                <w:rFonts w:ascii="Liberation Sans" w:hAnsi="Liberation Sans"/>
              </w:rPr>
            </w:pPr>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
                <w:color w:val="FF0000"/>
              </w:rPr>
              <w:t xml:space="preserve">Foo </w:t>
            </w:r>
          </w:p>
          <w:p>
            <w:pPr>
              <w:pStyle w:val="Normal"/>
              <w:rPr>
                <w:rFonts w:ascii="Liberation Sans" w:hAnsi="Liberation Sans"/>
              </w:rPr>
            </w:pPr>
            <w:r>
              <w:rPr>
                <w:b w:val="1"/>
                <w:bCs w:val="1"/>
                <w:color w:val="FF0000"/>
              </w:rPr>
              <w:t xml:space="preserve">test 123</w:t>
            </w:r>
          </w:p>
        </w:body>
      </w:document>
      EOD;

    static::assertXmlStringEqualsXmlString($expectedMainPart, $templateProcessor->getMainPart());
  }

  public function testReplaceInHeader(): void {
    $header = <<<EOD
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

    $templateProcessor = new TestablePhpWordTemplateProcessor('', [$header]);
    $templateProcessor->civiTokensToMacros();
    $templateProcessor->replaceHtmlToken('place.holder', 'test 123');

    $expectedHeader = <<<EOD
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
              <w:t xml:space="preserve">Foo </w:t>
            </w:r>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t xml:space="preserve">test 123</w:t>
            </w:r>
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

    static::assertXmlStringEqualsXmlString($expectedHeader, $templateProcessor->getHeaders()[0]);
  }

  public function testReplaceInFooter(): void {
    $footer = <<<EOD
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

    $templateProcessor = new TestablePhpWordTemplateProcessor('', [], [$footer]);
    $templateProcessor->civiTokensToMacros();
    $templateProcessor->replaceHtmlToken('place.holder', 'test 123');

    $expectedFooter = <<<EOD
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
              <w:t xml:space="preserve">Foo </w:t>
            </w:r>
            <w:r>
              <w:rPr>
                <w:b w:val="true"/>
              </w:rPr>
              <w:t xml:space="preserve">test 123</w:t>
            </w:r>
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

    static::assertXmlStringEqualsXmlString($expectedFooter, $templateProcessor->getFooters()[0]);
  }

}
