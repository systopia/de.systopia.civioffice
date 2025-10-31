<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2022 SYSTOPIA                            |
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

use Civi\Token\TokenProcessor;

class CRM_Civioffice_Page_Tokens extends CRM_Core_Page {

  public function run() {
    $tokenProcessor = new TokenProcessor(
        Civi::service('dispatcher'),
        [
          'schema' => [
            'contactId',
            'contributionId',
            'participantId',
            'eventId',
            'membershipId',
            'activityId',
            'caseId',
                // TODO: - Implement for token contexts from external token providers.
                //       - Add a note about checkActive() being implemented correctly, i. e. checking the
                //         TokenProcessor's context schema.
          ],
          'controller' => __CLASS__,
          'smarty' => FALSE,
        ]
    );
    $this->assign('tokens', CRM_Utils_Token::formatTokensForDisplay($tokenProcessor->listTokens()));

    return parent::run();
  }

}
