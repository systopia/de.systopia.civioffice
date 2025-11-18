<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
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

namespace Civi\Civioffice;

use Psr\Container\ContainerInterface;

final class DocumentEditorTypeContainer {

  private ContainerInterface $container;

  /**
   * @var array<string, string>
   */
  private array $titles;

  /**
   * This method should only be used, if service injection isn't possible.
   */
  public static function getInstance(): self {
    // @phpstan-ignore return.type
    return \Civi::service(self::class);
  }

  /**
   * @param array<string, string> $titles
   *   Mapping of editor type names to titles.
   * @param \Psr\Container\ContainerInterface $container
   *   Contains services implementing
   *   \Civi\Civioffice\DocumentEditorTypeInterface with the type name as key.
   */
  public function __construct(array $titles, ContainerInterface $container) {
    $this->titles = $titles;
    $this->container = $container;
  }

  /**
   * @return array<string, string>
   *   Mapping of editor type name to title.
   */
  public function getTitles(): array {
    return $this->titles;
  }

  /**
   * @throws \InvalidArgumentException
   */
  public function get(string $name): DocumentEditorTypeInterface {
    if (!$this->has($name)) {
      throw new \InvalidArgumentException("An editor type with name $name does not exist.");
    }

    // @phpstan-ignore return.type
    return $this->container->get($name);
  }

  public function has(string $name): bool {
    return isset($this->titles[$name]);
  }

}
