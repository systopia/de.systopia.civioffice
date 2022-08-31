<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
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
trait CRM_Civioffice_Form_Task_CreateDocumentsTrait
{
    /**
     * @var string $entityType
     *   The type of entities to create documents for.
     */
    protected $entityType;

    /**
     * @var array $entityIds
     *   A list of IDs of entities to create documents for.
     */
    protected $entityIds;

    /**
     * {@inheritDoc}
     */
    public function buildQuickForm()
    {
        $this->setTitle(E::ts('CiviOffice - Generate multiple Documents'));

        $config = CRM_Civioffice_Configuration::getConfig();
        $defaults = [];

        // add list of document renderers and supported output mime types
        $output_mimetypes = null;
        $document_renderer_list = [];
        foreach ($config->getDocumentRenderers(true) as $dr) {
            foreach ($dr->getType()->getSupportedOutputMimeTypes() as $mime_type) {
                $output_mimetypes[$mime_type] = CRM_Civioffice_MimeType::mapMimeTypeToFileExtension($mime_type);
            }
            $document_renderer_list[$dr->getURI()] = $dr->getName();
        }
        $this->add(
            'select',
            'document_renderer_uri',
            E::ts("Document Renderer"),
            $document_renderer_list,
            true,
            ['class' => 'crm-select2 huge']
        );

        // build document list
        $document_list = [];
        // TODO: Only show supported source mime types.
        foreach ($config->getDocumentStores(true) as $document_store) {
            foreach ($document_store->getDocuments() as $document) {  // todo: recursive
                /** @var CRM_Civioffice_Document $document */
                foreach ($config->getDocumentRenderers(true) as $dr) {
                    foreach ($dr->getType()->getSupportedMimeTypes() as $mime_type) {
                        // TODO: Mimetype checks could be handled differently in the future:
                        //       https://github.com/systopia/de.systopia.civioffice/issues/2
                        if (CRM_Civioffice_MimeType::hasSpecificFileNameExtension($document->getName(), $mime_type)) {
                            // only return if mimetype matches with supported mimetypes
                            $document_list[$document->getURI(
                            )] = "[{$document_store->getName()}] {$document->getName()}";
                        }
                    }
                }
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
            E::ts("Target document type"),
            $output_mimetypes,
            true,
            ['class' => 'crm-select2']
        );

        $this->add(
            'checkbox',
            'prepare_docx',
            E::ts("Prepare DOCX documents")
        );

        $this->add(
            'select',
            'activity_type_id',
            E::ts("Create Activity"),
            CRM_Civioffice_Configuration::getActivityTypes(),
            false,
            ['class' => 'crm-select2', 'placeholder' => E::ts("- don't create activity -")]
        );

        $this->add(
            'select',
            'batch_size',
            E::ts("batch size for processing"),
            [
                10 => 10,
                20 => 20,
                50 => 50,
                // // As soon as parallel conversion is solved and lock is removed higher values can be enabled again https://github.com/systopia/de.systopia.civioffice/issues/6
                //                100 => 100,
                //                200 => 200,
                //                500 => 500,
                //                1000 => 1000,
                //                2000 => 2000
            ],
            true,
            ['class' => 'crm-select2']
        );

        // Add fields for Live Snippets.
        CRM_Civioffice_LiveSnippets::addFormElements($this);

        // set default values.
        $this->setDefaults($defaults);

        // add buttons
        $this->addDefaultButtons(E::ts('Generate %1 Files', [1 => count($this->entityIds)]));
    }

    /**
     * {@inheritDoc}
     */
    public function getTemplateFileName()
    {
        // Instead of separate templates for each class using the trait, provide a generic template for the trait.
        $ext = CRM_Extension_System::singleton()->getMapper();
        if ($ext->isExtensionClass(__TRAIT__)) {
            $filename = $ext->getTemplateName(__TRAIT__);
            $tplname = $ext->getTemplatePath(__TRAIT__) . DIRECTORY_SEPARATOR . $filename;
        }
        else {
            $tplname = strtr(
                    __TRAIT__,
                    [
                        '_' => DIRECTORY_SEPARATOR,
                        '\\' => DIRECTORY_SEPARATOR,
                    ]
                ) . '.tpl';
        }
        return $tplname;
    }

    /**
     * {@inheritDoc}
     */
    public function postProcess()
    {
        $values = $this->exportValues();

        // Extract and store live snippet values.
        $live_snippets = CRM_Civioffice_LiveSnippets::getFormElementValues($this);

        // Initialize a queue.
        $queue = CRM_Queue_Service::singleton()->create(
            [
                'type' => 'Sql',
                'name' => 'civioffice_document_task_' . CRM_Core_Session::singleton()->getLoggedInContactId(),
                'reset' => true,
            ]
        );

        $chunked_entities = array_chunk($this->entityIds, $values['batch_size']);
        $temp_folder_path = (new CRM_Civioffice_DocumentStore_LocalTemp())->getBaseFolder();

        foreach ($chunked_entities as $entity_IDs) {
            $queue->createItem(
                new CRM_Civioffice_ConversionJob(
                    $values['document_renderer_uri'],
                    $values['document_uri'],
                    $temp_folder_path,
                    $entity_IDs,
                    $this->entityType,
                    $values['target_mime_type'],
                    E::ts('Initialized'),
                    $live_snippets,
                    !empty($values['prepare_docx']),
                    $values['activity_type_id']
                )
            );
        }

        // Save current page link (e.g. search page)
        $return_link = html_entity_decode(CRM_Core_Session::singleton()->readUserContext());
        $return_link = base64_encode($return_link);

        // Start a runner on the queue.
        $download_link = CRM_Utils_System::url(
            'civicrm/civioffice/download',
            "tmp_folder=$temp_folder_path&return_url=$return_link&instant_download=0"
        );

        $runner = new CRM_Queue_Runner(
            [
                'title' => E::ts(
                    "Generating %1 files",
                    [1 => count($this->entityIds)]
                ),
                'queue' => $queue,
                'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
                'onEndUrl' => $download_link,
            ]
        );
        $runner->runAllViaWeb();

        parent::postProcess();
    }
}
