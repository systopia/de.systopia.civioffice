<?php
use CRM_Civioffice_ExtensionUtil as E;

class CRM_Civioffice_Page_DocumentFromSingleContact extends CRM_Core_Page {

  public function run() {
      $contact_id = CRM_Utils_Request::retrieve('cid', 'Int', $this);

      if (empty($contact_id)) {
          // todo redirect with error
      }

      CRM_Utils_System::setTitle(E::ts('Document creation for single contact'));


      $this->assign('currentUser', $contact_id);

    parent::run();
  }

}
