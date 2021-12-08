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
            'live_snippets' => $e->getTokenProcessor()->rowContexts[0]['civioffice.live_snippets'],
        ];

        return $token_values;
    }

    public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = null)
    {
        [$token_type, $token_name] = explode('.', $field);
        $row->tokens($entity, $field, $prefetch[$token_type][$token_name] ?? '');
    }
}
