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
        // TODO: Trigger event for fetching tokens implemented elsewhere.
        //       $tokens = [];
        //       $tokens_event = GenericHookEvent::create($tokens);
        //       Civi::dispatcher()->dispatch('civi.civioffice.tokens', $tokens_event);
        //       return $tokens;
        return CRM_Civioffice_LiveSnippets::getTokens();
    }

    public function prefetch(TokenValueEvent $e)
    {
        $token_values = [
            'live_snippets' => $e->getTokenProcessor()->rowContexts[0]['civioffice.live_snippets'],
        ];
        // TODO: Add tokens from external token providers.
        //       $token_values = [];
        //       $token_values_event = new GenericHookEvent($token_values);
        //       $token_values_event = GenericHookEvent::create($token_values);
        //       Civi::dispatcher()->dispatch('civi.civioffice.tokenvalues', $tokens_values_event);

        return $token_values;
    }

    public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = null)
    {
        [$token_type, $token_name] = explode('.', $field);
        $row->tokens($entity, $field, $prefetch[$token_type][$token_name] ?? '');
    }
}
