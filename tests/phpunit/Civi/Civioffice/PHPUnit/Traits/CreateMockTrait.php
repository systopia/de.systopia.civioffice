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

namespace Civi\Civioffice\PHPUnit\Traits;

use PHPUnit\Framework\MockObject\MockObject;

trait CreateMockTrait {

  /**
   * Creates an APIv4 action mock that behaves (mostly) like the mocked class
   * itself. However, getParamInfo() is mocked because otherwise option
   * callbacks would be called that (might) require a complete Civi env.
   *
   * @template RealInstanceType of \Civi\Api4\Generic\AbstractAction
   * @phpstan-param class-string<RealInstanceType> $className
   * @phpstan-param mixed ...$constructorArgs
   *
   * @return \PHPUnit\Framework\MockObject\MockObject&RealInstanceType
   */
  public function createApi4ActionMock(string $className, ...$constructorArgs): MockObject {
    return $this->getMockBuilder($className)
      ->onlyMethods(['getParamInfo'])
      ->setConstructorArgs($constructorArgs)
      ->getMock();
  }

  /**
   * @template RealInstanceType of \Civi\Api4\Generic\AbstractAction
   * @phpstan-param class-string<RealInstanceType> $className
   * @phpstan-param array<string> $methods
   *
   * @return \PHPUnit\Framework\MockObject\MockObject&RealInstanceType
   */
  public function createPartialApi4ActionMock(
    string $className,
    string $entityName,
    string $actionName,
    array $methods = []
  ): MockObject {
    $actionMock = $this->createPartialMock($className, array_merge([
      'getActionName',
      'getEntityName',
      // Required because otherwise option callbacks would be called that (might) require a complete Civi env.
      'getParamInfo',
    ], $methods));

    $actionMock->method('getEntityName')->willReturn($entityName);
    $actionMock->method('getActionName')->willReturn($actionName);

    return $actionMock;
  }

}
