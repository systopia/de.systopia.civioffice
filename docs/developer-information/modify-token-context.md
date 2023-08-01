# Modify Token Context

CiviOffice dispatches an event that allows to modify the
[context](https://docs.civicrm.org/dev/en/latest/framework/token/#appendix-token-context)
that is used for token replacement. The event has the name
`civi.civioffice.tokenContext` and is of  type `GenericHookEvent`. It has the
attributes `entity_type` and `entity_id` which specify the entity to create the
document for. The token context is stored in the attribute `context`.

An example usage would be an entity that references a contact and tokens for
this contact should be available in the template to render:

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class MyEntityCiviOfficeTokenSubscriber implements EventSubscriberInterface {

 /**
  * @inheritDoc
  */
  public static function getSubscribedEvents(): array {
    return ['civi.civioffice.tokenContext' => 'onCiviOfficeTokenContext'];
  }

  public function onCiviOfficeTokenContext(GenericHookEvent $event): void {
    if ('MyEntity' === $event->entity_type) {
      $event->context['contactId'] = $this->getContactId($event->entity_type, $event->entity_id);
    }
  }

  private function getContactId(string $entityType, int $entityId): int {
    ...
  }

}
```
