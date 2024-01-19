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

namespace Civi\Civioffice\Api4\Action\Document;

use Civi\Api4\Generic\BasicGetFieldsAction;
use CRM_Civioffice_ExtensionUtil as E;

final class GetFieldsAction extends BasicGetFieldsAction {

  public function __construct() {
    parent::__construct('CiviofficeDocument', 'getFields');
  }

  /**
   * @phpstan-return list<array<string, array<string, scalar>|array<scalar>|scalar|null>>
   */
  protected function getRecords(): array {
    return [
      [
        'name' => 'name',
        'title' => E::ts('Name'),
        'type' => 'Field',
        'nullable' => FALSE,
        'data_type' => 'String',
        'readonly' => TRUE,
      ],
      [
        'name' => 'uri',
        'title' => E::ts('URI'),
        'type' => 'Field',
        'nullable' => FALSE,
        'data_type' => 'String',
        'readonly' => TRUE,
      ],
      [
        'name' => 'mime_type',
        'title' => E::ts('MIME Type'),
        'type' => 'Field',
        'nullable' => FALSE,
        'data_type' => 'String',
        'readonly' => TRUE,
      ],
      [
        'name' => 'path',
        'title' => E::ts('Path'),
        'type' => 'Field',
        'nullable' => FALSE,
        'data_type' => 'String',
        'readonly' => TRUE,
      ],
      [
        'name' => 'document_store_uri',
        'title' => E::ts('Document Store URI'),
        'type' => 'Field',
        'nullable' => FALSE,
        'data_type' => 'String',
        'readonly' => TRUE,
      ],
    ];
  }

}
