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

/**
 * Participant search task: Create CiviOffice documents for selected participants.
 */
class CRM_Civioffice_Form_Task_CreateParticipantDocuments extends CRM_Event_Form_Task {
  use CRM_Civioffice_Form_Task_CreateDocumentsTrait;

  public function preProcess() {
    parent::preProcess();
    $this->entityType = 'Participant';
    $this->entityIds = $this->_participantIds;
  }

}
