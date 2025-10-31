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

use Civi\Token\AbstractTokenSubscriber;
use Civi\Token\Event\TokenValueEvent;
use Civi\Token\TokenRow;

class CRM_Civioffice_Tokens extends AbstractTokenSubscriber {

  public function __construct($entity, $tokenNames = []) {
    $tokenNames += self::getTokens();
    parent::__construct($entity, $tokenNames);
  }

  public static function getTokens() {
    return CRM_Civioffice_LiveSnippets::getTokens();
  }

  public function prefetch(TokenValueEvent $e) {
    $token_values = [
      'live_snippets' => $e->getTokenProcessor()->getContextValues('civioffice.live_snippets')[0] ?? [],
    ];

    return $token_values;
  }

  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL) {
    [$token_type, $token_name] = explode('.', $field);

    // Set row format for Live Snippets to HTML (default) or an explicitly defined context format.
    $processor_context = $row->tokenProcessor->context['civioffice'] ?? [];
    if ($token_type == 'live_snippets') {
      $row->format($processor_context['format'] ?? 'text/html');
    }

    $row->tokens($entity, $field, $prefetch[$token_type][$token_name] ?? '');
  }

}
