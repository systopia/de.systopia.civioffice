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
     * @deprecated
     *
     * @use self::civiTokensToMacros()
     */
    public function liveSnippetTokensToMacros()
    {
        $this->tempDocumentHeaders = preg_replace(
            '/({civioffice\.live_snippets\..*?})/',
            '\$$1',
            $this->tempDocumentHeaders
        );
        $this->tempDocumentMainPart = preg_replace(
            '/(\{civioffice\.live_snippets\..*?\})/',
            '\$$1',
            $this->tempDocumentMainPart
        );
        $this->tempDocumentFooters = preg_replace(
            '/({civioffice\.live_snippets\..*?})/',
            '\$$1',
            $this->tempDocumentFooters
        );
    }

    /**
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
}
