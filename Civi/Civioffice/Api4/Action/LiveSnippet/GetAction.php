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

use Civi\Api4\CiviofficeLiveSnippet;
use Civi\Api4\Generic\AbstractGetAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\OptionValue;

final class GetAction extends AbstractGetAction {

  public function __construct() {
    parent::__construct('CiviofficeLiveSnippet', 'get');
  }

  /**
   * @inheritDoc
   */
  public function _run(Result $result): void {
    $action = OptionValue::get(FALSE)
      ->setSelect($this->buildOptionValueSelect())
      ->setOffset($this->getOffset())
      ->setLimit($this->getLimit())
      ->setWhere($this->getWhere())
      ->setOrderBy($this->getOrderBy())
      ->setLanguage($this->language)
      ->addWhere('option_group_id:name', '=', 'civioffice_live_snippets');

    $result->exchangeArray($action->execute()->getArrayCopy());

    $lastValueSelected = $this->isLastValueSelected();
    if ($lastValueSelected && NULL !== \CRM_Core_Session::getLoggedInContactID()) {
      $contactSettings = \Civi::contactSettings();
    }
    else {
      $contactSettings = NULL;
    }

    /** @phpstan-var array<string, scalar|null> $record */
    foreach ($result as &$record) {
      unset($record['id']);

      if ($lastValueSelected) {
        $record['last_value'] = NULL === $contactSettings ? NULL
          : $contactSettings->get('civioffice.live_snippets.' . $record['name']);
      }
    }
  }

  /**
   * @phpstan-return list<string>
   */
  private function buildOptionValueSelect(): array {
    /** @var list<string> $fieldNames */
    $fieldNames = CiviofficeLiveSnippet::getFields(FALSE)
      ->addSelect('name')
      ->execute()
      ->column('name');

    if ([] === $this->getSelect() || in_array('*', $this->getSelect(), TRUE)) {
      return $fieldNames;
    }

    $select = array_values(array_intersect($fieldNames, $this->getSelect()));
    if ($this->isLastValueSelected() && !in_array('name', $select, TRUE)) {
      $select[] = 'name';
    }

    return $select;
  }

  private function isLastValueSelected(): bool {
    return in_array('last_value', $this->getSelect(), TRUE);
  }

}
