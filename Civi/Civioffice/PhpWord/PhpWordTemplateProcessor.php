<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

declare(strict_types = 1);

namespace Civi\Civioffice\PhpWord;

use Civi\Civioffice\PhpWord\Util\DocxUtil;
use Civi\Civioffice\PhpWord\Util\XmlUtil;
use CRM_Civioffice_ExtensionUtil as E;
use PhpOffice\PhpWord;

class PhpWordTemplateProcessor extends PhpWord\TemplateProcessor {

  /**
   * Replaces CiviCRM tokens with PhpWord macros (converts format from
   * "{token}" to "${macro}").
   *
   * @return array<string, array{entity: string, field: string, filter: string | null}>
   *   An array of CiviCRM tokens found in the document.
   */
  public function civiTokensToMacros(): array {
    $this->tempDocumentHeaders = DocxUtil::combineRuns(DocxUtil::dropAnnotations($this->tempDocumentHeaders));
    $this->tempDocumentMainPart = DocxUtil::combineRuns(DocxUtil::dropAnnotations($this->tempDocumentMainPart));
    $this->tempDocumentFooters = DocxUtil::combineRuns(DocxUtil::dropAnnotations($this->tempDocumentFooters));

    $tokens = [];
    foreach (
      [
        &$this->tempDocumentHeaders,
        &$this->tempDocumentMainPart,
        &$this->tempDocumentFooters,
      ] as &$tempDocPart
    ) {
      // Regex code borrowed from \Civi\Token\TokenProcessor::visitTokens().
      // Adapted to allow '&quot;' instead of '"'.

      // The regex is a bit complicated, we so break it down into fragments.
      // Consider the example '{foo.bar|whiz:"bang":"bang"}'. Each fragment matches the following:
      $tokenRegex = '([\w]+)\.([\w:\.]+)'; /* MATCHES: 'foo.bar' */
      $filterArgRegex = ':([\w": %\-_()\[\]\+/#@!,\.\?]|&quot;)*'; /* MATCHES: ':"bang":"bang"' */
      // Key rule of filterArgRegex is to prohibit '{}'s because they may parse ambiguously. So you *might* relax
      // it to:
      // MATCHES: ':"bang":"bang"' or ':&quot;bang&quot;'
      // $filterArgRegex = ':[^{}\n]*';

      // MATCHES: 'whiz'
      $filterNameRegex = '\w+';
      $filterRegex = "\|($filterNameRegex(?:$filterArgRegex)?)"; /* MATCHES: '|whiz:"bang":"bang"' */
      $fullRegex = "~\{$tokenRegex(?:$filterRegex)?\}~";

      $tempDocPart = preg_replace_callback(
        $fullRegex,
        /*
         * The match contains:
         * - $0: The entire token, possibly including filters, with surrounding "{" and "}"
         * - $1: The token context (first part  of the token)
         * - $2: The token name (second part of the token)
         * - $3: The filter, possibly including filter parameters
         *
         * We just prefix the token with a "$" as macro names can contain anything,
         * @see \PhpOffice\PhpWord\TemplateProcessor::getVariablesForPart()
         */
        function($matches) use (&$tokens) {
          $token = str_replace('&quot;', '"', $matches[0]);
          $tokens[$token] = [
            'entity' => $matches[1],
            'field' => $matches[2],
            'filter' => $matches[3] ?? NULL,
          ];
          return '$' . $token;
        },
        $tempDocPart
      );
    }

    return $tokens;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function replaceHtmlToken(string $macroVariable, string $renderedTokenMessage): void {
    static $phpWord;
    if (!isset($phpWord)) {
      $phpWord = new PhpWord\PhpWord();
    }

    $outputEscapingEnabled = PhpWord\Settings::isOutputEscapingEnabled();
    PhpWord\Settings::setOutputEscapingEnabled(TRUE);
    try {
      // Use a temporary Section element for adding the elements.
      $section = $phpWord->addSection();
      // Note: addHtml() does not accept styles, so added HTML elements do not get applied any existing
      // styles.
      PhpWord\Shared\Html::addHtml($section, $renderedTokenMessage);
      $elements = $section->getElements();
      if ([] === $elements) {
        // Note: If the paragraph had the macro as its only content, it
        // will not be removed (i.e. leave an empty paragraph).
        $this->setValue($macroVariable, '');
      }
      else {
        // ... or as HTML: Render all elements and insert in the text
        // run or paragraph containing the macro.
        $this->setElementsValue($macroVariable, $elements, TRUE);
      }
    }
    // @phpstan-ignore catch.neverThrown
    catch (\Exception $exception) {
      throw new \CRM_Core_Exception(
        E::ts('Error loading/writing PhpWord document: %1', [1 => $exception->getMessage()]),
        $exception->getCode(),
        [],
        $exception
      );
    }
    finally {
      PhpWord\Settings::setOutputEscapingEnabled($outputEscapingEnabled);
    }
  }

  /**
   * Replaces a search string (macro) with a set of rendered elements, splitting
   * surrounding texts, text runs or paragraphs before and after the macro,
   * depending on the types of elements to insert.
   *
   * @param \PhpOffice\PhpWord\Element\AbstractElement[] $elements
   * @param bool $inheritStyle
   *   If TRUE the style will be inherited from the paragraph/text run the macro
   *   is inside. If the element already contains styles, they will be merged.
   */
  public function setElementsValue(string $search, array $elements, bool $inheritStyle = FALSE): void {
    $elementsDataList = [];
    $hasParagraphs = FALSE;
    foreach ($elements as $element) {
      $elementName = substr(
        get_class($element),
        (int) strrpos(get_class($element), '\\') + 1
      );
      $objectClass = 'PhpOffice\\PhpWord\\Writer\\Word2007\\Element\\' . $elementName;

      // For inline elements, do not create a new paragraph.
      $withParagraph = !is_a($objectClass, \PhpOffice\PhpWord\Writer\Word2007\Element\Text::class, TRUE)
        || is_a($objectClass, \PhpOffice\PhpWord\Writer\Word2007\Element\TextRun::class, TRUE);
      $hasParagraphs = $hasParagraphs || $withParagraph;

      $xmlWriter = new PhpWord\Shared\XMLWriter();
      /** @var \PhpOffice\PhpWord\Writer\Word2007\Element\AbstractElement $elementWriter */
      $elementWriter = new $objectClass($xmlWriter, $element, !$withParagraph);
      $elementWriter->write();
      /** @var list<string> $elementsDataList */
      $elementsDataList[] = preg_replace('/>\s+</', '><', $xmlWriter->getData());
    }

    $this->tempDocumentHeaders = array_map(
      fn (string $partXML) => $this->setElementsValueForPart(
        $search,
        $elementsDataList,
        $partXML,
        $hasParagraphs,
        $inheritStyle
      ),
      $this->tempDocumentHeaders
    );
    $this->tempDocumentMainPart = $this->setElementsValueForPart(
      $search,
      $elementsDataList,
      $this->tempDocumentMainPart,
      $hasParagraphs,
      $inheritStyle
    );
    $this->tempDocumentFooters = array_map(
      fn (string $partXML) => $this->setElementsValueForPart(
        $search,
        $elementsDataList,
        $partXML,
        $hasParagraphs,
        $inheritStyle
      ),
      $this->tempDocumentFooters
    );
  }

  /**
   * Replaces a search string (macro) in a document part with a set of rendered
   * elements, splitting surrounding texts, text runs or paragraphs before and
   * after the macro, depending on the types of elements to insert.
   *
   * @param string $search Macro to search for
   * @param list<string> $replaceXml Rendered elements to replace the macro with
   * @param string $partXml The XML for macro replacement
   * @param bool $hasParagraphs TRUE if a replacement element has a paragraph
   * @param bool $inheritStyle
   *   If TRUE the style will be inherited from the paragraph/text run the macro
   *   is inside. If the element already contains styles, they will be merged.
   *
   * @return string The part XML with every macro occurrence replaced.
   */
  protected function setElementsValueForPart(
    string $search,
    array $replaceXml,
    string $partXml,
    bool $hasParagraphs,
    bool $inheritStyle
  ): string {
    $search = static::ensureMacroCompleted($search);
    $blockType = $hasParagraphs ? 'w:p' : 'w:r';
    while ($where = XmlUtil::findContainingXmlBlock($partXml, $search, $blockType)) {
      $block = XmlUtil::getSlice($partXml, $where['start'], $where['end']);
      $paragraphStyle = '';
      $textRunStyle = '';
      $parts = $hasParagraphs ? DocxUtil::splitParagraphIntoParagraphs(
        $block,
        '${',
        '}',
        $paragraphStyle,
        $textRunStyle
      ) : $this->splitTextIntoTexts($block, $textRunStyle);
      if ($inheritStyle) {
        /** @var list<string> $replaceXml */
        $replaceXml = preg_replace_callback_array([
          '#<w:pPr/>#' => fn() => $paragraphStyle,
          '#<w:pPr(?:(?!<w:pPr).)*</w:pPr>#' => fn(array $matches) => StyleMerger::mergeStyles(
            $matches[0], $paragraphStyle
          ),
          // <w:pPr> may contain <w:rPr> itself so we have to match for <w:rPr> inside <w:r>
          '#<w:r><w:rPr/>(?:(?!<w:r>).)*</w:r>#' => fn(array $matches) => str_replace(
            '<w:rPr/>', $textRunStyle, $matches[0]
          ),
          '#<w:r>(<w:rPr(?:(?!<w:rPr).)*</w:rPr>)(?:(?!<w:r>).)*</w:r>#' => fn(array $matches) => preg_replace(
            '#<w:rPr.*</w:rPr>#',
            StyleMerger::mergeStyles($matches[1], $textRunStyle),
            $matches[0]
          ),
        ], $replaceXml);
      }

      $partXml = XmlUtil::replaceXmlBlock($partXml, $search, $parts, $blockType);
      $partXml = XmlUtil::replaceXmlBlock($partXml, $search, implode('', $replaceXml), $blockType);
    }

    return $partXml;
  }

  /**
   * @inheritDoc
   * Adds output parameter for extracted style.
   *
   * @param string $extractedStyle
   *   Is set to the extracted text run style (w:rPr).
   */
  protected function splitTextIntoTexts($text, string &$extractedStyle = ''): string {
    $unformattedText = XmlUtil::flattenXml($text);

    $matches = [];
    preg_match('#<w:rPr(?:(?!<w:rPr).)*</w:rPr>#', $unformattedText, $matches);
    $extractedStyle = $matches[0] ?? '';

    if (!$this->textNeedsSplitting($text)) {
      return $text;
    }

    $result = str_replace(
      ['<w:t>', '${', '}'],
      [
        '<w:t xml:space="preserve">',
        '</w:t></w:r><w:r>' . $extractedStyle . '<w:t xml:space="preserve">${',
        '}</w:t></w:r><w:r>' . $extractedStyle . '<w:t xml:space="preserve">',
      ],
      $unformattedText
    );

    $emptyTextRun = '<w:r>' . $extractedStyle . '<w:t xml:space="preserve"></w:t></w:r>';

    return str_replace($emptyTextRun, '', $result);
  }

}
