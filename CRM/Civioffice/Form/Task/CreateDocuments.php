<?php

use CRM_Civioffice_ExtensionUtil as E;

/**
 * Search task implementation: create only office documents
 */
class CRM_Civioffice_Form_Task_CreateDocuments extends CRM_Contact_Form_Task
{
    public function buildQuickForm()
    {
        $this->setTitle(E::ts("CiviOffice - Generate Documents"));

        $config = CRM_Civioffice_Configuration::getConfig();

        // add list of converters
        $converter_list = [];
        foreach ($config->getConverters() as $converter) {
            /** @var CRM_Civioffice_Converter $converter */
            $converter_list[$converter->getID()] = $converter->getName();
        }
        $this->add(
            'select',
            'converter_id',
            E::ts("Converter"),
            $converter_list,
            true,
            ['class' => 'crm-select2 huge']
        );

        // build document list
        $document_list = [];
        foreach ($config->getDocumentStores() as $document_store) {
            foreach ($document_store->getDocuments() as $document) {  // todo: recursive
                /** @var \CRM_Civioffice_Document $document */
                $document_list[$document->getURI()] = "[{$document_store->getName()}] {$document->getName()}";
            }
        }
        $this->add(
            'select',
            'document_uri',
            E::ts("Document"),
            $document_list,
            true,
            ['class' => 'crm-select2 huge']
        );

        $this->add(
            'select',
            'target_mime_type',
            E::ts("Document Type"),
            [
                'application/pdf' => E::ts("PDF"),
            ],
            true,
            ['class' => 'crm-select2']
        );

        // add buttons
        CRM_Core_Form::addDefaultButtons(E::ts("Generate %1 Files", [1 => count($this->_contactIds)]));
    }


    public function postProcess()
    {
        $values = $this->exportValues();

        $config = CRM_Civioffice_Configuration::getConfig();
        $converter = $config->getConverter($values['converter_id']);
        $document = $config->getDocument($values['document_uri']);

        // run for all contacts
        // todo: $converter->generate($document, $values['target_mime_type'], $this->_contactIds, 'Contact');
        foreach ($this->_contactIds as $contactId) {
            $documents = $converter->convert([$document], $values['target_mime_type']);
        }

        // test 2: zip


        // test 3: download

        parent::postProcess();
    }

}
