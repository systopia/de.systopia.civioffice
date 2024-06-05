# SearchKit Task for Custom Entities

If you want to have the SearchKit task to create documents for your custom
entities, you need to extend
`\Civi\Civioffice\EventSubscriber\AbstractCiviOfficeSearchKitTaskSubscriber` like
[this](https://github.com/systopia/de.systopia.civioffice/blob/master/Civi/Civioffice/EventSubscriber/CiviOfficeSearchKitTaskSubscriber.php).

A second subscriber is required to make tokens for your custom entity available.
This one needs to extend `\Civi\Token\AbstractTokenSubscriber` and additionally
subscribe to the event `civi.civioffice.tokenContext`. It might look similar to
this:

```php
final class MyCiviOfficeTokenSubscriber extends AbstractTokenSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      'civi.civioffice.tokenContext' => 'onCiviOfficeTokenContext',
    ] + parent::getSubscribedEvents();
  }

  public function __construct() {
    // Register {my_entity.my_first_field} and {my_entity.my_second_field} as tokens.
    // Instead of 'my_entity' this code could be used:
    // \CRM_Core_DAO_AllCoreTables::convertEntityNameToLower('MyEntity')
    parent::__construct('my_entity', [
      'my_first_field' => 'My first field label',
      'my_second_field' => 'My second field label',
    ]);
  }

  public function onCiviOfficeTokenContext(GenericHookEvent $event): void {
    if ('MyEntity' === $event->entity_type) {
      $event->context['myEntityId'] = $event->entity_id;
    }
  }

  /**
   * @inheritDoc
   */
  public function checkActive(TokenProcessor $processor): bool {
    return in_array('myEntityId', $processor->context['schema'] ?? [], TRUE)
      || [] !== $processor->getContextValues('myEntityId');
  }

  /**
   * @inheritDoc
   */
  public function evaluateToken(TokenRow $row, $entityName, $field, $prefetch = NULL): void {
    $entityId = $row->context['myEntityId'];
    // Load the field value.
    $value = ...;
    $row->tokens($entityName, $field, $value);
  }

}
```

(Note: It might make sense to replace some strings with constants or return
values of methods.)

Register both subscribers in the service container during
[`hook_civicrm_container`](https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_container/):

```php
$container->autowire(MyCiviOfficeSearchKitTaskSubscriber::class)
  ->addTag('kernel.event_subscriber');

$container->autowire(MyCiviOfficeTokenSubscriber::class)
  ->addTag('kernel.event_subscriber');
```
