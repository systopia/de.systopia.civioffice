<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: J. Franz (franz@systopia.de)                   |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

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
        // todo: only show supported source mime types
        foreach ($config->getDocumentStores() as $document_store) {
            foreach ($document_store->getDocuments() as $document) {  // todo: recursive
                /** @var CRM_Civioffice_Document $document */
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
                CRM_Civioffice_MimeType::PDF => E::ts("PDF"), // todo: add a file name to mime type mapping in CRM_Civioffice_MimeType 2/2
                CRM_Civioffice_MimeType::DOCX => E::ts('DOCX (fastest)')
            ],
            true,
            ['class' => 'crm-select2']
        );

        $this->add(
            'select',
            'batch_size',
            E::ts("batch size for processing"),
            [
                10 => 10,
                20 => 20,
                50 => 50,
                100 => 100,
                200 => 200
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

        // Initialize a queue.
        $queue = CRM_Queue_Service::singleton()->create(
          [
              'type' => 'Sql',
              'name' => 'civioffice_document_task_' . CRM_Core_Session::singleton()->getLoggedInContactId(),
              'reset' => true
          ]
        );

        $chunked_entities = array_chunk($this->_contactIds, $values['batch_size'],false);
        $temp_folder_path = (new CRM_Civioffice_DocumentStore_LocalTemp(CRM_Civioffice_MimeType::PDF))->getBaseFolder();

        foreach ($chunked_entities as $entity_IDs) {
            $queue->createItem(
                $job = new CRM_Civioffice_ConversionJob(
                    $values['document_renderer_id'],
                    $values['document_uri'],
                    $temp_folder_path,
                    $entity_IDs,
                    'contact',
                    $values['target_mime_type'],
                    E::ts('Initialized')
                )
            );
        }

        // Save current page link (e.g. search page)
        $return_link = html_entity_decode(CRM_Core_Session::singleton()->readUserContext());
        $return_link = base64_encode($return_link);

        // Start a runner on the queue.
        $download_link = CRM_Utils_System::url(
            'civicrm/civioffice/download',
            "tmp_folder={$temp_folder_path}&return_url={$return_link}"
        );

        $runner = new CRM_Queue_Runner(
          [
              'title' => E::ts(
                  "Generating %1 files",
                  [1 => count($this->_contactIds)]
              ),
              'queue' => $queue,
              'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
              'onEndUrl' => $download_link
          ]
        );
        $runner->runAllViaWeb();

        parent::postProcess();
    }

}
