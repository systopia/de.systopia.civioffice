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

namespace Civi\Civioffice\Api4\Action\ActivityType;

use Civi\Api4\Activity;
use Civi\Api4\Generic\BasicGetAction;
use CRM_Civioffice_ExtensionUtil as E;

/**
 * @method string|null getEntityType()
 * @method $this setEntityType(string|null $entityType)
 */
final class GetAction extends BasicGetAction {

  /**
   * @var string|null
   */
  protected ?string $entityType = NULL;

  public function __construct() {
    parent::__construct('CiviofficeActivityType', 'get');
  }

  /**
   * @phpstan-return list<array<string, scalar|null>>
   */
  protected function getRecords(): array {
    $activityTypes = Activity::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'label'])
      ->addWhere('name', '=', 'activity_type_id')
      ->addSelect('options')
      ->execute()
      ->single()['options'];

    if (in_array('is_last_used', $this->getSelect(), TRUE)) {
      $lastUsedId = $this->getLastUsedId();
      foreach ($activityTypes as &$activityType) {
        $activityType['is_last_used'] = $lastUsedId === $activityType['id'];
      }
    }

    return $activityTypes;
  }

  private function getLastUsedId(): ?int {
    if (NULL === \CRM_Core_Session::getLoggedInContactID() || NULL === $this->entityType) {
      return NULL;
    }

    $lastId = \Civi::contactSettings()->get(
      'civioffice.create_' . $this->entityType . '.activity_type_id'
    );

    return is_int($lastId) ? $lastId : NULL;
  }

}
