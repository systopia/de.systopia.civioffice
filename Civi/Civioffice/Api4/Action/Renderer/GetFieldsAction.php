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

namespace Civi\Civioffice\Api4\Action\Renderer;

use Civi\Api4\Generic\BasicGetFieldsAction;
use CRM_Civioffice_ExtensionUtil as E;

final class GetFieldsAction extends BasicGetFieldsAction {

  public function __construct() {
    parent::__construct('CiviofficeRenderer', 'getFields');
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
        'name' => 'description',
        'title' => E::ts('Description'),
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
        'name' => 'supported_mime_types',
        'title' => E::ts('Supported MIME Types'),
        'type' => 'Field',
        'nullable' => FALSE,
        'data_type' => 'String',
        'serialize' => 1,
        'readonly' => TRUE,
      ],
      [
        'name' => 'supported_output_mime_types',
        'title' => E::ts('Supported Output MIME Types'),
        'type' => 'Field',
        'nullable' => FALSE,
        'data_type' => 'String',
        'serialize' => 1,
        'readonly' => TRUE,
      ],
      [
        'name' => 'supported_output_file_types',
        'title' => E::ts('Supported Output File Types'),
        'type' => 'Field',
        'nullable' => FALSE,
        'data_type' => 'Array',
        'readonly' => TRUE,
        'description' => E::ts('Mapping of file type names to MIME types.'),
      ],
      [
        'name' => 'renderer_type_uri',
        'title' => E::ts('Renderer Type URI'),
        'type' => 'Field',
        'nullable' => FALSE,
        'data_type' => 'String',
        'readonly' => TRUE,
      ],
      [
        'name' => 'is_active',
        'title' => E::ts('Is Active'),
        'type' => 'Field',
        'nullable' => FALSE,
        'data_type' => 'Boolean',
        'readonly' => TRUE,
      ],
    ];
  }

}
