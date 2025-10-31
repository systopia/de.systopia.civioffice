<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2023 SYSTOPIA                            |
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
 * Membership search task: Create CiviOffice documents for selected memberships.
 */
class CRM_Civioffice_Form_Task_CreateCaseDocuments extends CRM_Case_Form_Task {
  use CRM_Civioffice_Form_Task_CreateDocumentsTrait;

  public function preProcess(): void {
    parent::preProcess();
    $this->entityType = 'Case';
  }

}
