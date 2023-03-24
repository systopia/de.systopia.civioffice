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

use CRM_Civioffice_ExtensionUtil as E;
use PhpOffice\PhpWord;

class CRM_Civioffice_DocumentRendererType_LocalUnoconv_PhpWordTemplateProcessor extends PhpWord\TemplateProcessor
{
    /**
     * Replaces CiviCRM tokens with PhpWord macros (converts format from "{token}" to "${macro}").
     *
     * @return array
     *   An array of CiviCRM tokens found in the document.
     */
    public function civiTokensToMacros(): array
    {
        $tokens = [];
        foreach (
            [
                &$this->tempDocumentHeaders,
                &$this->tempDocumentMainPart,
                &$this->tempDocumentFooters,
            ] as &$tempDocPart
        ) {
            // Regex code borrowed from \Civi\Token\TokenProcessor::visitTokens().

            // The regex is a bit complicated, we so break it down into fragments.
            // Consider the example '{foo.bar|whiz:"bang":"bang"}'. Each fragment matches the following:
            $tokenRegex = '([\w]+)\.([\w:\.]+)'; /* MATCHES: 'foo.bar' */
            $filterArgRegex = ':[\w": %\-_()\[\]\+/#@!,\.\?]*'; /* MATCHES: ':"bang":"bang"' */
            // Key rule of filterArgRegex is to prohibit '{}'s because they may parse ambiguously. So you *might* relax
            // it to: $filterArgRegex = ':[^{}\n]*'; /* MATCHES: ':"bang":"bang"' */
            $filterNameRegex = "\w+"; /* MATCHES: 'whiz' */
            $filterRegex = "\|($filterNameRegex(?:$filterArgRegex)?)"; /* MATCHES: '|whiz:"bang":"bang"' */
            $fullRegex = ";\{$tokenRegex(?:$filterRegex)?\};";

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
                    $tokens[$matches[0]] = [
                        'entity' => $matches[1],
                        'field' => $matches[2],
                        'filter' => $matches[3] ?? NULL,
                    ];
                    return '$' . $matches[0];
                },
                $tempDocPart
            );
        }

        return $tokens;
    }

    public function replaceHtmlToken($macro_variable, $rendered_token_message) {
        static $phpWord;
        if (!isset($phpWord)) {
            $phpWord = new PhpWord\PhpWord();
        }
        try {
            // Use a temporary Section element for adding the elements.
            $section = $phpWord->addSection();
            // Note: addHtml() does not accept styles, so added HTML elements do not get applied any existing
            // styles.
            PhpWord\Shared\Html::addHtml($section, $rendered_token_message);
            $elements = $section->getElements();
            $elementCount = count($elements);
            if (
                $elementCount === 1 && $elements[0] instanceof PhpOffice\PhpWord\Element\Text
                || $elementCount === 0
            ) {
                // ... either as plain text (if there is only a single Text element or nothing), ...
                $this->setValue($macro_variable, $rendered_token_message);
            }
            elseif ($elementCount === 1) {
                // ... or as single complex value from HTML
                $this->setComplexValue($macro_variable, $elements[0]);
            }
            else {
                // ... or as HTML: Render all elements and replace the paragraph containing the macro.
                // Note: This will remove the entire paragraph element around the macro.
                // TODO: Save and split surrounding contents and add them to the replaced block.
                //       This would be a logical assumption, since HTML elements will always make for a new
                //       paragraph, moving text before and after the macro into their own paragraphs.
                //       See \PhpOffice\PhpWord\TemplateProcessor::setComplexValue().
                //       It cannot be used with Section objects, because Section is not in the required namespace for
                //       elements supported by this method.
                //       When just putting the section in a \PhpOffice\PhpWord\Writer\Word2007\Element\Container and
                //       using the same code as in setComplexValue() no content appears.
                $xmlWriter = new PhpWord\Shared\XMLWriter();
                foreach ($elements as $element) {
                    $elementName = substr(
                        get_class($element),
                        strrpos(get_class($element), '\\') + 1
                    );
                    $objectClass = 'PhpOffice\\PhpWord\\Writer\\Word2007\\Element\\' . $elementName;

                    /** @var \PhpOffice\PhpWord\Writer\Word2007\Element\AbstractElement $elementWriter */
                    $elementWriter = new $objectClass($xmlWriter, $element, false);
                    $elementWriter->write();
                }

                $this->replaceXmlBlock($macro_variable, $xmlWriter->getData(), 'w:p');
            }
        }
        catch (Exception $exception) {
            throw new Exception(
                E::ts('Error loading/writing PhpWord document: %1', [1 => $exception->getMessage()]),
                $exception->getCode(),
                $exception
            );
        }
    }
}