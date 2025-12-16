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

use Civi\Civioffice\DocumentEditorTypeContainer;
use Civi\Civioffice\DocumentEditorTypeInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class DocumentEditorTypePass implements CompilerPassInterface {

  /**
   * @inheritDoc
   */
  public function process(ContainerBuilder $container): void {
    $titles = [];
    $services = [];
    foreach ($container->findTaggedServiceIds(DocumentEditorTypeInterface::class) as $id => $attributes) {
      $class = $this->getServiceClass($container, $id);
      if (!is_a($class, DocumentEditorTypeInterface::class, TRUE)) {
        throw new \RuntimeException("$class does not implement " . DocumentEditorTypeInterface::class);
      }

      $titles[$class::getName()] = $class::getTitle();
      $services[$class::getName()] = new Reference($id);
    }

    $container->register(DocumentEditorTypeContainer::class, DocumentEditorTypeContainer::class)
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
