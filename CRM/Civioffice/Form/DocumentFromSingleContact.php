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
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Civioffice_Form_DocumentFromSingleContact extends CRM_Core_Form {

    public $contact_id = null;

    public function buildQuickForm() {

        $this->contact_id = CRM_Utils_Request::retrieve('cid', 'Int', $this, true);

        $this->assign('user_id', $this->contact_id);

        $this->setTitle(E::ts("CiviOffice - Generate Document"));

        $config = CRM_Civioffice_Configuration::getConfig();

        // add list of document renderers and supported output mime types
        $output_mimetypes = null;
        $document_renderer_list = [];
        foreach ($config->getDocumentRenderers(true) as $dr) {
            foreach ($dr->getSupportedOutputMimeTypes() as $mime_type) {
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
        // todo: only show supported source mime types
        foreach ($config->getDocumentStores(true) as $document_store) {
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
            E::ts("Target document type"),
            $output_mimetypes,
            true,
            ['class' => 'crm-select2']
        );

        // add buttons
        CRM_Core_Form::addDefaultButtons(E::ts("Generate File"));
    }


    public function postProcess() {
        $values = $this->exportValues();

        $render_result = civicrm_api3('CiviOffice', 'convert', [
            'document_uri'     => $values['document_uri'],
            'entity_ids'       => [$this->contact_id],
            'entity_type'      => 'contact',
            'renderer_uri'     => $values['document_renderer_uri'],
            'target_mime_type' => $values['target_mime_type']
        ]);

        // get the result (@todo adjust to proper APIv3 result)
        $result_store_uri = $render_result[0];

        // get the document from the store
        $store = CRM_Civioffice_Configuration::getDocumentStore($result_store_uri);
        $rendered_documents = $store->getDocuments();

        // make sure nothing funny is going on...
        if (count($rendered_documents) > 1) {
            throw new Exception("Multiple documents returned!");
        }
        if (count($rendered_documents) == 0) {
            throw new Exception("Document not rendered.");
        }

        // and simply trigger the download
        /** @var \CRM_Civioffice_Document $rendered_document */
        $rendered_document = reset($rendered_documents);
        $rendered_document->download();

        // we shouldn't get here
        parent::postProcess();
    }
}
