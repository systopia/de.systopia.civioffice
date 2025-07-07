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

namespace Civi\Civioffice\DependencyInjection\Compiler;

use Civi\Civioffice\DocumentRendererTypeContainer;
use Civi\Civioffice\DocumentRendererTypeInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class DocumentRendererTypePass implements CompilerPassInterface {

  /**
   * @inheritDoc
   */
  public function process(ContainerBuilder $container): void {
    $titles = [];
    $services = [];
    foreach ($container->findTaggedServiceIds(DocumentRendererTypeInterface::class) as $id => $attributes) {
      $class = $this->getServiceClass($container, $id);
      if (!is_a($class, DocumentRendererTypeInterface::class, TRUE)) {
        throw new \RuntimeException("$class does not implement " . DocumentRendererTypeInterface::class);
      }

      $titles[$class::getName()] = $class::getTitle();
      $services[$class::getName()] = new Reference($id);
    }

    $container->register(DocumentRendererTypeContainer::class, DocumentRendererTypeContainer::class)
      ->addArgument($titles)
      ->addArgument(ServiceLocatorTagPass::register($container, $services))
      ->setPublic(TRUE);

  }

  /**
   * @phpstan-return class-string
   */
  private function getServiceClass(ContainerBuilder $container, string $id): string {
    $definition = $container->getDefinition($id);

    /** @phpstan-var class-string $class */
    $class = $definition->getClass() ?? $id;

    return $class;
  }

}
