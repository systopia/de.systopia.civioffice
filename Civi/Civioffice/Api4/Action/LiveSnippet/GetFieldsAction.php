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

namespace Civi\Civioffice\Api4\Action\LiveSnippet;

use Civi\Api4\Generic\BasicGetFieldsAction;
use Civi\Api4\OptionValue;
use CRM_Civioffice_ExtensionUtil as E;

final class GetFieldsAction extends BasicGetFieldsAction {

  public function __construct() {
    parent::__construct('CiviofficeLiveSnippet', 'getFields');
  }

  /**
   * @phpstan-return list<array<string, array<string, scalar>|array<scalar>|scalar|null>>
   */
  protected function getRecords(): array {
    $action = OptionValue::getFields($this->getCheckPermissions())
      ->setAction($this->getAction())
      ->setLoadOptions($this->getLoadOptions())
      ->setOrderBy($this->getOrderBy())
      ->setLanguage($this->language)
      ->addWhere('name', 'IN', ['label', 'name', 'description'])
      ->addValue('option_group_id:name', 'civioffice_live_snippets');

    /** @var list<array<string, array<string, scalar>|array<scalar>|scalar|null>> $fields */
    $fields = $action->execute()->getArrayCopy();

    $fields[] = [
      'name' => 'last_value',
      'title' => E::ts('Last Value'),
      'type' => 'Extra',
      'data_type' => 'String',
      'nullable' => TRUE,
      'required' => FALSE,
      'operators' => [],
    ];

    return $fields;
  }

}
