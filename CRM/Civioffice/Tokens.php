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
        $token_event = \Civi\Core\Event\GenericHookEvent::create(['tokens' => &$tokens]);
        Civi::dispatcher()->dispatch('civi.civioffice.tokens', $token_event);

        return $tokens;
    }

    public function prefetch(TokenValueEvent $e)
    {
        $token_values = [
            // TODO: Do not access rowContexts directly, use TokenRow! Also make sure we use the "tet/html" format.
            'live_snippets' => $e->getTokenProcessor()->rowContexts[0]['civioffice.live_snippets'],
        ];

        $token_values_event = \Civi\Core\Event\GenericHookEvent::create(['tokens_values' => &$token_values]);
        Civi::dispatcher()->dispatch('civi.civioffice.tokenvalues', $token_values_event);

        return $token_values;
    }

    public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = null)
    {
        [$token_type, $token_name] = explode('.', $field);

        // Set row format for Live Snippets to HTML (default) or an explicitly defined context format.
        $processor_context = $row->tokenProcessor->context['civioffice'] ?? [];
        if ($token_type == 'live_snippets') {
            $row->format($processor_context['format'] ?? 'text/html');
        }

        $row->tokens($entity, $field, $prefetch[$token_type][$token_name] ?? '');
    }
}
