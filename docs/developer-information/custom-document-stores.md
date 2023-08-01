# Custom Document Stores

CiviOffice allows to add custom document stores to the built-in ones. This
allows for example to load documents from a remote file storage. CiviOffice
dispatches a `GenericHookEvent` under the name `civi.civioffice.documentStores`
to collect the document stores. Document stores are added to the array in the
event attribute `document_stores`. A custom document store might look like this:

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class MyCiviOfficeDocumentStore extends \CRM_Civioffice_DocumentStore implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return ['civi.civioffice.documentStores' => 'onRegister'];
  }

  public function __construct() {
    parent::__construct('my-document-store', 'My Document Store');
  }

  public function onRegister(GenericHookEvent $event): void {
    $event->document_stores[] = $this;
  }

  // Implementations of methods inherited from \CRM_Civioffice_DocumentStore.

}
```
