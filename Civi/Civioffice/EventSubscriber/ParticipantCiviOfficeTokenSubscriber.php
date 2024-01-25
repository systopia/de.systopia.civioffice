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

namespace Civi\Civioffice\EventSubscriber;

use Civi\Api4\Participant;
use Civi\Core\Event\GenericHookEvent;

final class ParticipantCiviOfficeTokenSubscriber extends AbstractCoreEntityCiviOfficeTokenSubscriber {

  public function onCiviOfficeTokenContext(GenericHookEvent $event): void {
    parent::onCiviOfficeTokenContext($event);

    if ($this->getEntityType() === $event->entity_type) {
      $participant = Participant::get(FALSE)
        ->addWhere('id', '=', $event->entity_id)
        ->execute()
        ->single();
      $event->context['contactId'] = $participant['contact_id'];
      $event->context['eventId'] = $participant['event_id'];

      try {
        /** @var array{contribution_id: int|string} $participant_payment */
        $participant_payment = civicrm_api3(
          'ParticipantPayment',
          'getsingle',
          ['participant_id' => $participant['id']]
        );
        $event->context['contributionId'] = (int) $participant_payment['contribution_id'];
      }
      catch (\CRM_Core_Exception $exception) {
        // No participant payment, nothing to do.
        // @ignoreException
      }
    }
  }

  protected function getEntityType(): string {
    return 'Participant';
  }

}
