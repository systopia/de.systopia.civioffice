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

use Civi\Civioffice\Api4\CiviofficePermissions;
use Civi\Core\Event\GenericHookEvent;
use CRM_Civioffice_ExtensionUtil as E;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractCiviOfficeSearchKitTaskSubscriber implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return ['hook_civicrm_searchKitTasks' => 'onSearchKitTasks'];
  }

  public function onSearchKitTasks(GenericHookEvent $event): void {
    if ($event->checkPermissions && !\CRM_Core_Permission::check(CiviofficePermissions::ACCESS, $event->userId)) {
      return;
    }

    foreach ($this->getEntityTypes() as $entityType) {
      $event->tasks[$entityType]['civiofficeRender'] = [
        'module' => 'civiofficeSearchTasks',
        'title' => E::ts('Create Documents (CiviOffice)'),
        'icon' => 'fa-file-text-o',
        'uiDialog' => ['templateUrl' => '~/civiofficeSearchTasks/civiofficeSearchTaskRender.html'],
      ];
    }
  }

  /**
   * @phpstan-return iterable<string>
   */
  abstract protected function getEntityTypes(): iterable;

}
