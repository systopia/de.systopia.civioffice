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

use Civi\Api4\Generic\BasicGetFieldsAction;
use CRM_Civioffice_ExtensionUtil as E;

final class GetFieldsAction extends BasicGetFieldsAction {

  public function __construct() {
    parent::__construct('CiviofficeActivityType', 'getFields');
  }

  /**
   * @phpstan-return list<array<string, array<string, scalar>|array<scalar>|scalar|null>>
   */
  protected function getRecords(): array {
    return [
      [
        'name' => 'id',
        'title' => E::ts('ID'),
        'type' => 'Field',
        'nullable' => TRUE,
        'data_type' => 'Integer',
        'readonly' => TRUE,
      ],
      [
        'name' => 'name',
        'title' => E::ts('Name'),
        'type' => 'Field',
        'nullable' => FALSE,
        'data_type' => 'String',
        'readonly' => TRUE,
      ],
      [
        'name' => 'label',
        'title' => E::ts('Label'),
        'type' => 'Field',
        'nullable' => FALSE,
        'data_type' => 'String',
        'readonly' => TRUE,
      ],
      [
        'name' => 'is_last_used',
        'title' => E::ts('Is Last Used'),
        'type' => 'Extra',
        'nullable' => FALSE,
        'data_type' => 'Boolean',
        'readonly' => TRUE,
        'description' => E::ts('Indicates the last used activity type. (Entity type specific.)'),
      ],
    ];
  }

}
