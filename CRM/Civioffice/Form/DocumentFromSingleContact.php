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
class CRM_Civioffice_Form_DocumentFromSingleContact extends CRM_Core_Form
{

    /**
     * @var integer $contact_id
     *   The ID of the contact to create a document for.
     */
    public $contact_id = null;

    const UNOCONV_CREATE_SINGLE_ACTIVIY_TYPE = 'civioffice_create_single_activity_type';

    const UNOCONV_CREATE_SINGLE_ACTIVIY_ATTACHMENT = 'civioffice_create_single_activity_attachment';

    public function buildQuickForm()
    {
        $config = CRM_Civioffice_Configuration::getConfig();
        $defaults = [
            'activity_type_id' => Civi::contactSettings()->get(
                self::UNOCONV_CREATE_SINGLE_ACTIVIY_TYPE
            ),
            'activity_attach_doc' => Civi::contactSettings()->get(
                self::UNOCONV_CREATE_SINGLE_ACTIVIY_ATTACHMENT
            ),
        ];
        $this->setAttribute('data-no-ajax-submit', 'true');
        $this->contact_id = CRM_Utils_Request::retrieve('cid', 'Int', $this, true);
        $this->assign('user_id', $this->contact_id);
        $this->setTitle(E::ts("CiviOffice - Generate a single Document"));

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
                // TODO: Mimetype checks could be handled differently in the future: https://github.com/systopia/de.systopia.civioffice/issues/2
                if (!CRM_Civioffice_MimeType::hasSpecificFileNameExtension(
                    $document->getName(),
                    CRM_Civioffice_MimeType::DOCX
                )) {
                    continue; // for now only allow/return docx files
                }

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

        $this->add(
            'select',
            'activity_type_id',
            E::ts("Create Activity"),
            $this->getActivityTypes(),
            false,
            ['class' => 'crm-select2', 'placeholder' => E::ts("- don't create activity -")]
        );

        $this->add(
            'checkbox',
            'activity_attach_doc',
            E::ts("Attach Rendered Document")
        );

        // Add fields for Live Snippets.
        CRM_Civioffice_LiveSnippets::addFormElements($this);

        // Set default values.
        $this->setDefaults($defaults);

        // add buttons
        $this->addButtons(
            [
                [
                    'type' => 'upload',
                    'name' => ts('Download Document'),
                    'isDefault' => true,
                    'icon' => 'fa-download',
                ],
                [
                    'type' => 'submit',
                    'name' => ts('Preview'),
                    'subName' => 'preview',
                    'icon' => 'fa-search',
                    'isDefault' => false,
                ],
                [
                    'type' => 'cancel',
                    'name' => ts('Cancel'),
                ],
            ]
        );
    }


    public function postProcess()
    {
        // TODO: Do not filter live snippet values.
        $values = $this->exportValues();

        // Extract and store live snippet values.
        $live_snippets = CRM_Civioffice_LiveSnippets::getFormElementValues($this);

        $render_result = civicrm_api3('CiviOffice', 'convert', [
            'document_uri' => $values['document_uri'],
            'entity_ids' => [$this->contact_id],
            'entity_type' => 'contact',
            'renderer_uri' => $values['document_renderer_uri'],
            'target_mime_type' => $values['target_mime_type'],
            'live_snippets' => $live_snippets,
        ]);
        $result_store_uri = $render_result['values'][0];
        $result_store = CRM_Civioffice_Configuration::getDocumentStore($result_store_uri);
        $rendered_documents = $result_store->getDocuments();

        if ($this->isLiveMode()) {
            // Create activity, if requested.
            if (!empty($values['activity_type_id'])) {
                $activity = civicrm_api3('Activity', 'create', [
                    'activity_type_id' => $values['activity_type_id'],
                    'subject' => E::ts("Document (CiviOffice)"),
                    'status_id' => 'Completed',
                    'activity_date_time' => date("YmdHis"),
                    'target_id' => $entity_ids,
                ]);

                // generate & link attachment if requested
                if (!empty($values['activity_attach_doc'])) {
                    foreach ($rendered_documents as $document) {
                        $path_of_local_copy = $document->getLocalTempCopy();
                        // attach rendered document
                        $attachments = [
                            'attachFile_1' => [
                                'location' => $path_of_local_copy,
                                'type' => mime_content_type($path_of_local_copy),
                            ],
                        ];
                        CRM_Core_BAO_File::processAttachment($attachments, 'civicrm_activity', $activity['id']);
                    }
                }
            }
        }

        switch (count($rendered_documents)) {
            case 0: // something's wrong
                throw new Exception(E::ts("Rendering Error!"));

            case 1: // single document -> direct download
                /** @var \CRM_Civioffice_Document $rendered_document */
                $rendered_document = reset($rendered_documents);
                $rendered_document->download();

            default:
                $result_store->downloadZipped();
        }

        // save defaults
        try {
            Civi::contactSettings()->set(self::UNOCONV_CREATE_SINGLE_ACTIVIY_TYPE, $values['activity_type_id'] ?? '');
            Civi::contactSettings()->set(
                self::UNOCONV_CREATE_SINGLE_ACTIVIY_ATTACHMENT,
                $values['activity_attach_doc'] ?? 0
            );
        } catch (CRM_Core_Exception $ex) {
            Civi::log()->warning("CiviOffice: Couldn't save defaults: " . $ex->getMessage());
        }
    }

    /**
     * Is the form in live mode (as opposed to being run as a preview).
     *
     * Returns true if the user has clicked the Download Document button on a
     * "Create CiviOffice document" contact task form, or false if the Preview
     * button was clicked.
     *
     * @return bool
     *   TRUE if the Download Document button was clicked (also defaults to TRUE
     *   if the form controller does not exist), else FALSE.
     */
    protected function isLiveMode(): bool
    {
        return strpos($this->controller->getButtonName(), '_preview') === false;
    }


    /**
     * Get a list of eligible activity types
     */
    protected function getActivityTypes()
    {
        $types = ['' => E::ts("- none -")];
        $type_query = civicrm_api3('OptionValue', 'get', [
            'option_group_id' => 'activity_type',
            // TODO: Any reason for why to exclude reserved activity types?
            'is_reserved' => 0,
            'option.limit' => 0,
            'return' => 'value,label',
        ]);
        foreach ($type_query['values'] as $type) {
            $types[$type['value']] = $type['label'];
        }
        return $types;
    }
}
