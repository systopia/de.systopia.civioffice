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

use CRM_Civioffice_ExtensionUtil as E;
use PhpOffice\PhpWord;

class CRM_Civioffice_DocumentRendererType_LocalUnoconv_PhpWordTemplateProcessor extends PhpWord\TemplateProcessor {

  /**
   * Replaces CiviCRM tokens with PhpWord macros (converts format from
   * "{token}" to "${macro}").
   *
   * @return array<string, array{entity: string, field: string, filter: string | null}>
   *   An array of CiviCRM tokens found in the document.
   */
  public function civiTokensToMacros(): array {
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
   * @param string $macroVariable
   * @param string $renderedTokenMessage
   *
   * @return void
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
      // setValue() and setElementsValue() replace only the first occurrence of macro variables in the document, so loop
      // until all have been replaced.
      do {
        if ([] === $elements) {
          // Note: If the paragraph had the macro as its only content, it
          // will not be removed (i.e. leave an empty paragraph).
          $this->setValue($macroVariable, '');
        }
        elseif (count($elements) === 1 && $elements[0] instanceof PhpWord\Element\Text) {
          // ... either as plain text (if there is only a single Text
          // element, which can't have style properties), ...
          // Note: $rendered_token_message shouldn't be used directly
          // because it may contain HTML entities.
          $this->setValue($macroVariable, $elements[0]->getText());
        }
        else {
          // ... or as HTML: Render all elements and insert in the text
          // run or paragraph containing the macro.
          $this->setElementsValue($macroVariable, $elements, TRUE);
        }
      } while (in_array($macroVariable, $this->getVariables(), TRUE));
    }
    catch (Exception $exception) {
      throw new CRM_Core_Exception(
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
   *   If TRUE and an element contains no style, it will be inherited from the
   *   paragraph/text run the macro is inside.
   *
   * @throws \PhpOffice\PhpWord\Exception\Exception
   */
  public function setElementsValue(string $search, array $elements, bool $inheritStyle = FALSE): void {
    $search = static::ensureMacroCompleted($search);
    $elementsData = '';
    $hasParagraphs = FALSE;
    foreach ($elements as $element) {
      $elementName = substr(
        get_class($element),
        (int) strrpos(get_class($element), '\\') + 1
      );
      $objectClass = 'PhpOffice\\PhpWord\\Writer\\Word2007\\Element\\' . $elementName;

      // For inline elements, do not create a new paragraph.
      $withParagraph = \PhpOffice\PhpWord\Writer\Word2007\Element\Text::class !== $objectClass;
      $hasParagraphs = $hasParagraphs || $withParagraph;

      $xmlWriter = new PhpWord\Shared\XMLWriter();
      /** @var \PhpOffice\PhpWord\Writer\Word2007\Element\AbstractElement $elementWriter */
      $elementWriter = new $objectClass($xmlWriter, $element, !$withParagraph);
      $elementWriter->write();
      $elementsData .= $xmlWriter->getData();
    }
    $blockType = $hasParagraphs ? 'w:p' : 'w:r';
    $where = $this->findContainingXmlBlockForMacro($search, $blockType);
    if (is_array($where)) {
      /** @phpstan-var array{start: int, end: int} $where */
      $block = $this->getSlice($where['start'], $where['end']);
      $paragraphStyle = '';
      $textRunStyle = '';
      $parts = $hasParagraphs
        ? $this->splitParagraphIntoParagraphs($block, $paragraphStyle, $textRunStyle)
        : $this->splitTextIntoTexts($block, $textRunStyle);
      if ($inheritStyle) {
        $elementsData = str_replace(['<w:pPr/>', '<w:rPr/>'], [$paragraphStyle, $textRunStyle], $elementsData);
      }
      $this->replaceXmlBlock($search, $parts, $blockType);
      $this->replaceXmlBlock($search, $elementsData, $blockType);
    }
  }

  /**
   * Splits a w:p into a list of w:p where each ${macro} is in a separate w:p.
   *
   * @param string $extractedParagraphStyle
   *   Is set to the extracted paragraph style (w:pPr).
   * @param string $extractedTextRunStyle
   *   Is set to the extracted text run style (w:rPr).
   *
   * @throws \PhpOffice\PhpWord\Exception\Exception
   */
  public function splitParagraphIntoParagraphs(
    string $paragraph,
    string &$extractedParagraphStyle = '',
    string &$extractedTextRunStyle = ''
  ): string {
    if (NULL === $paragraph = preg_replace('/>\s+</', '><', $paragraph)) {
      throw new PhpWord\Exception\Exception('Error processing PhpWord document.');
    }

    $matches = [];
    preg_match('#<w:pPr.*</w:pPr>#i', $paragraph, $matches);
    $extractedParagraphStyle = $matches[0] ?? '';

    preg_match('#<w:rPr.*</w:rPr>#i', $paragraph, $matches);
    $extractedTextRunStyle = $matches[0] ?? '';

    $result = str_replace(
      [
        '<w:t>',
        '${',
        '}',
      ],
      [
        '<w:t xml:space="preserve">',
        sprintf(
          '</w:t></w:r></w:p><w:p>%s<w:r><w:t xml:space="preserve">%s${',
          $extractedParagraphStyle,
          $extractedTextRunStyle
        ),
        sprintf(
          '}</w:t></w:r></w:p><w:p>%s<w:r>%s<w:t xml:space="preserve">',
          $extractedParagraphStyle,
          $extractedTextRunStyle
        ),
      ],
      $paragraph
    );

    // Remove empty paragraphs that might have been created before/after the
    // macro.
    $emptyParagraph = sprintf(
      '<w:p>%s<w:r>%s<w:t xml:space="preserve"></w:t></w:r></w:p>',
      $extractedParagraphStyle,
      $extractedTextRunStyle
    );

    return str_replace($emptyParagraph, '', $result);
  }

  /**
   * @inheritDoc
   * Adds output parameter for extracted style.
   *
   * @param string $extractedStyle
   *   Is set to the extracted text run style (w:rPr).
   *
   * @throws \PhpOffice\PhpWord\Exception\Exception
   */
  protected function splitTextIntoTexts($text, string &$extractedStyle = '') {
    if (NULL === $unformattedText = preg_replace('/>\s+</', '><', $text)) {
      throw new PhpWord\Exception\Exception('Error processing PhpWord document.');
    }

    $matches = [];
    preg_match('/<w:rPr.*<\/w:rPr>/i', $unformattedText, $matches);
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
