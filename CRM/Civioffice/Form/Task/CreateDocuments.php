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

        // add list of document renderers
        $document_renderer_list = [];
        foreach ($config->getDocumentRenderers() as $dr) {
            /** @var CRM_Civioffice_DocumentRenderer $dr */
            $document_renderer_list[$dr->getID()] = $dr->getName();
        }
        $this->add(
            'select',
            'document_renderer_id',
            E::ts("Document Renderer"),
            $document_renderer_list,
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

        $batch_size = [5, 10, 20, 30, 40, 50];

        $this->add(
            'select',
            'batch_size',
            E::ts("Batch size"),
            $batch_size,
            true,
            ['class' => 'crm-select2 huge']
        );

        // add buttons
        CRM_Core_Form::addDefaultButtons(E::ts("Generate %1 Files", [1 => count($this->_contactIds)]));
    }


    public function postProcess()
    {
        $values = $this->exportValues();

        $batch_size = $values['batch_size'];

        $config = CRM_Civioffice_Configuration::getConfig();
        $document_renderer = $config->getDocumentRenderer($values['document_renderer_id']);
        $document = $config->getDocument($values['document_uri']);

        // run for all contacts
        // todo: $document_renderer->generate($document, $values['target_mime_type'], $this->_contactIds, 'Contact');
        foreach ($this->_contactIds as $contactId) {
            $documents = $document_renderer->render([$document], $values['target_mime_type']);
        }

        // test 2: zip


        // test 3: download

        parent::postProcess();
    }

}
