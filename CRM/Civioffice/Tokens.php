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
use Civi\Token\AbstractTokenSubscriber;
use Civi\Token\TokenRow;
use Civi\Token\Event\TokenValueEvent;

class CRM_Civioffice_Tokens extends AbstractTokenSubscriber
{
    public function __construct($entity, $tokenNames = [])
    {
        $tokenNames += self::getTokens();
        parent::__construct($entity, $tokenNames);
    }

    public static function getTokens() {
        return CRM_Civioffice_LiveSnippets::getTokens();
    }

    public function prefetch(TokenValueEvent $e)
    {
        $token_values = [
            'live_snippets' => CRM_Civioffice_LiveSnippets::getValues(),
        ];

        // TODO: Convert to context mime type.
        $context = $e->getTokenProcessor()->getContextValues('civioffice', 'mime_type');
        foreach ($token_values as $category => $tokens) {
            foreach ($tokens as $token) {
                switch (reset($context)) {
                    case CRM_Civioffice_MimeType::DOCX:
                        /**
                         * TODO: Convert HTML to OOXML using PhpWord library
                         * @url https://github.com/PHPOffice/PHPWord
                         * @url https://code-boxx.com/convert-html-to-docx-using-php/
                         */
                        // PHPWord is a CiviCRM Core dependency.
                        $pw = new \PhpOffice\PhpWord\PhpWord();
                        $section = $pw->addSection();
                        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $token, false, false);
                        $pw->save("HTML.docx", "Word2007");
                        break;
                }
            }
        }

        return $token_values;
    }

    public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = null)
    {
        [$token_type, $token_name] = explode('.', $field);
        $row->tokens($entity, $field, $prefetch[$token_type][$token_name] ?? '');
    }
}
