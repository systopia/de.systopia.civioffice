<?php
/*
 * Copyright (C) 2024 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\Civioffice\EntitySubscriber;

use Civi\Api4\CaseContact;
use Civi\Core\Event\GenericHookEvent;

final class CaseCiviOfficeTokenSubscriber extends AbstractCoreEntityCiviOfficeTokenSubscriber {

  public function onCiviOfficeTokenContext(GenericHookEvent $event): void {
    parent::onCiviOfficeTokenContext($event);

    if ($this->getEntityType() === $event->entity_type) {
      $caseContact = CaseContact::get(FALSE)
        ->addSelect('contact_id')
        ->addWhere('case_id', '=', $event->entity_id)
        ->execute()
        // Use the first record, as there might be more than one.
        ->first();
      if (NULL !== $caseContact) {
        $event->context['contactId'] = $caseContact['contact_id'];
      }
    }
  }

  protected function getEntityType(): string {
    return 'Case';
  }

}
