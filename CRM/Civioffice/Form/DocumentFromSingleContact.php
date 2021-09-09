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

        $this->contact_id = CRM_Utils_Request::retrieve('cid', 'Int', $this);

        if (empty($this->contact_id)) {
            // todo redirect with error
        }

        $this->assign('user_id', $this->contact_id);

        CRM_Utils_System::setTitle(E::ts('Document creation for single contact')); // fixme duplicated?

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

        $api_input['document_uri'] = $values['document_uri'];
        $api_input['entity_ids'] = [$this->contact_id];
        $api_input['entity_type'] = 'contact';
        $api_input['renderer_uri'] = $values['document_renderer_uri'];
        $api_input['target_mime_type'] = $values['target_mime_type'];

        $result = civicrm_api3('CiviOffice', 'convert', $api_input);

        $temp_folder_path = $result[0]; //fixme remove tmp:: --> helper method?
        $temp_folder_path = '/var/www/civicrm/office/sites/default/files/civicrm/templates_c/civioffice/temp/civioffice_202_613a433473488';


        // Save current page link (e.g. search page)
        $return_link = html_entity_decode(CRM_Core_Session::singleton()->readUserContext());
        $return_link = base64_encode($return_link);

        // Start a runner on the queue.
        $download_link = CRM_Utils_System::url(
            'civicrm/civioffice/download',
            "tmp_folder={$temp_folder_path}&return_url={$return_link}&instant_download=1"
        );

        CRM_Utils_System::redirect($download_link);

        $dl = new CRM_Civioffice_Form_Download();
        $dl->zipIfNeededAndDownload($temp_folder_path);

        parent::postProcess();
    }
}
