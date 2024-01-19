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

use Civi\Core\Event\GenericHookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractCoreEntityCiviOfficeTokenSubscriber implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return ['civi.civioffice.tokenContext' => 'onCiviOfficeTokenContext'];
  }

  public function onCiviOfficeTokenContext(GenericHookEvent $event): void {
    if ($this->getEntityType() === $event->entity_type) {
      $event->context[$this->getIdContextKey()] = $event->entity_id;
    }
    elseif ($this->getEntityType() === ucfirst($event->entity_type)) {
      trigger_error(
        sprintf('CiviOffice: Using lower-cased entity types is deprecated. ("%s" was used.)', $event->entity_type),
        \E_USER_DEPRECATED
      );

      $event->entity_type = $this->getEntityType();
      $event->context[$this->getIdContextKey()] = $event->entity_id;
    }
  }

  abstract protected function getEntityType(): string;

  private function getIdContextKey(): string {
    return lcfirst($this->getEntityType()) . 'Id';
  }

}
