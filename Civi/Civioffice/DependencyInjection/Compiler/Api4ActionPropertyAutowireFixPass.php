<?php
declare(strict_types = 1);

namespace Civi\Civioffice\DependencyInjection\Compiler;

use Civi\Api4\Generic\AbstractAction;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Symfony DI (v5 and v6) tries to autowire properties annotated with @required.
 * Though CiviCRM action parameters can be annotated in this way to make them
 * mandatory. This pass clears the properties that are going to be injected for
 * APIv4 action classes registered as services in the
 * Civi\Civioffice\Api4\Action namespace. (In Symfony DI v7 support of
 * annotations is dropped in favor of PHP attributes.)
 */
final class Api4ActionPropertyAutowireFixPass implements CompilerPassInterface {

  public function process(ContainerBuilder $container): void {
    foreach ($container->getDefinitions() as $id => $definition) {
      if ([] === $definition->getProperties() || !str_starts_with($id, 'Civi\\Civioffice\\Api4\\Action\\')) {
        continue;
      }

      if (is_a($id, AbstractAction::class, TRUE)) {
        $definition->setProperties([]);
      }
    }
  }

}
