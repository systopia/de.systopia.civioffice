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

use Civi\Api4\Activity;
use Civi\Api4\ActivityContact;
use Civi\Core\Event\GenericHookEvent;

final class ActivityCiviOfficeTokenSubscriber extends AbstractCoreEntityCiviOfficeTokenSubscriber {

  public function onCiviOfficeTokenContext(GenericHookEvent $event): void {
    parent::onCiviOfficeTokenContext($event);

    if ($this->getEntityType() === $event->entity_type) {
      $activityContact = ActivityContact::get(FALSE)
        ->addSelect('contact_id')
        ->addWhere('activity_id', '=', $event->entity_id)
        ->addWhere('record_type_id:name', '=', 'Activity Source')
        ->execute()
        // Use the first record, as there might be more than one.
        ->first();
      if (NULL !== $activityContact) {
        $event->context['contactId'] = $activityContact['contact_id'];
      }

      $activity = Activity::get(FALSE)
        ->addSelect('case_id')
        ->addWhere('id', '=', $event->entity_id)
        ->addWhere('case_id', 'IS NOT EMPTY')
        ->execute()
        ->single();
      $event->context['caseId'] = $activity['case_id'];
    }
  }

  protected function getEntityType(): string {
    return 'Activity';
  }

}
