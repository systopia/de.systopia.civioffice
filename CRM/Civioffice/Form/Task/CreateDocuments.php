<?php

use CRM_Civioffice_ExtensionUtil as E;

/**
 * Search task implementation: create only office documents
 */
class CRM_Civioffice_Form_Task_CreateDocuments extends CRM_Contact_Form_Task
{
    public function buildQuickForm()
    {
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
            []
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
            []
        );

        $this->add(
            'select',
            'target_mime_type',
            E::ts("Document Type"),
            [
                'application/pdf' => E::ts("PDF"),
            ]
        );

        $this->addButtons(
            [
                [
                    'type' => 'submit',
                    'name' => E::ts('Generate'),
                    'isDefault' => true,
                ],
            ]
        );

        // export form elements
        parent::buildQuickForm();
    }


    public function postProcess()
    {
        $values = $this->exportValues();

        $config = CRM_Civioffice_Configuration::getConfig();
        $converter = $config->getConverter($values['converter_id']);
        $document = $config->getDocument($values['document_uri']);

        // test: simply run
        $converter->convert([$document], $values['target_mime_type']);


        parent::postProcess();
    }

}
