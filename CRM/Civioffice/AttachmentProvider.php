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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Mailbatch\AttachmentType\AttachmentTypeInterface;
use Civi\Mailbatch\Form\Task\AttachmentsTrait;

class CRM_Civioffice_AttachmentProvider implements EventSubscriberInterface, AttachmentTypeInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'civi.mailbatch.attachmentTypes' => 'getAttachmentTypes',
        ];
    }

    public static function getAttachmentTypes($event)
    {
        $event->attachment_types['civioffice_document'] = [
            'label' => E::ts('CiviOffice Document'),
            'controller' => self::class,
            'context' => [
                'entity_types' => ['contact', 'contribution'],
            ],
        ];
    }

    public static function buildAttachmentForm(&$form, $attachment_id)
    {
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
        $form->add(
            'select',
            'attachments--' . $attachment_id . '--document_renderer_uri',
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
                foreach ($config->getDocumentRenderers(true) as $dr) {
                    foreach ($dr->getSupportedMimeTypes() as $mime_type) {
                        // TODO: Mimetype checks could be handled differently in the future: https://github.com/systopia/de.systopia.civioffice/issues/2
                        if (CRM_Civioffice_MimeType::hasSpecificFileNameExtension($document->getName(), $mime_type)) {
                            // only return if mimetype matches with supported mimetypes
                            $document_list[$document->getURI(
                            )] = "[{$document_store->getName()}] {$document->getName()}";
                        }
                    }
                }
            }
        }
        $form->add(
            'select',
            'attachments--' . $attachment_id . '--document_uri',
            E::ts("Document"),
            $document_list,
            true,
            ['class' => 'crm-select2 huge']
        );
        $form->add(
            'select',
            'attachments--' . $attachment_id . '--target_mime_type',
            E::ts("Target document type"),
            $output_mimetypes,
            true,
            ['class' => 'crm-select2']
        );

        // Add Live Snippets.
        $live_snippet_elements = CRM_Civioffice_LiveSnippets::addFormElements(
            $form,
            'attachments--' . $attachment_id . '--'
        );

        $form->add(
            'text',
            'attachments--' . $attachment_id . '--name',
            E::ts('Attachment Name'),
            ['class' => 'huge'],
            false
        );

        $form->add(
            'checkbox',
            'attachments--' . $attachment_id . '--prepare_docx',
            E::ts('Prepare DOCX documents'),
            false
        );

        return [
            'attachments--' . $attachment_id . '--document_renderer_uri' => 'attachment-civioffice_document-document_renderer_uri',
            'attachments--' . $attachment_id . '--document_uri' => 'attachment-civioffice_document-document_uri',
            'attachments--' . $attachment_id . '--target_mime_type' => 'attachment-civioffice_document-target_mime_type',
            'attachments--' . $attachment_id . '--name' => 'attachment-civioffice_document-name',
            'attachments--' . $attachment_id . '--prepare_docx' => 'attachment-civioffice_document-prepare_docx',
        ] + array_fill_keys($live_snippet_elements, 'attachment-civioffice_document-live_snippet');
    }

    public static function getAttachmentFormTemplate() {
        return 'CRM/Civioffice/Form/AttachmentProvider.tpl';
    }

    public static function processAttachmentForm(&$form, $attachment_id)
    {
        $values = $form->exportValues();
        $live_snippet_values = CRM_Civioffice_LiveSnippets::getFormElementValues(
            $form,
            false,
            'attachments--' . $attachment_id . '--'
        );
        return [
            'document_renderer_uri' => $values['attachments--' . $attachment_id . '--document_renderer_uri'],
            'document_uri' => $values['attachments--' . $attachment_id . '--document_uri'],
            'target_mime_type' => $values['attachments--' . $attachment_id . '--target_mime_type'],
            'name' => $values['attachments--' . $attachment_id . '--name'],
            'live_snippets' => $live_snippet_values,
            'prepare_docx' => !empty($values['attachments--' . $attachment_id . '--prepare_docx'])
        ];
    }

    public static function buildAttachment($context, $attachment_values)
    {
        $civioffice_result = civicrm_api3(
            'CiviOffice',
            'convert',
            [
                'document_uri' => $attachment_values['document_uri'],
                'entity_ids' => [$context['entity_id']],
                'entity_type' => $context['entity_type'],
                'renderer_uri' => $attachment_values['document_renderer_uri'],
                'target_mime_type' => $attachment_values['target_mime_type'],
                'live_snippets' => $attachment_values['live_snippets'],
                'prepare_docx' => $attachment_values['prepare_docx'],
            ]
        );
        if (!empty($civioffice_result['is_error']) || empty($civioffice_result['values'][0])) {
            throw new Exception($civioffice_result['error_message']);
        }
        $result_store_uri = $civioffice_result['values'][0];
        $result_store = CRM_Civioffice_Configuration::getDocumentStore($result_store_uri);
        foreach ($result_store->getDocuments() as $document) {
            $attachment_file = $document->getLocalTempCopy();
            $attachment = [
                'fullPath' => $attachment_file,
                'mime_type' => AttachmentsTrait::getMimeType($attachment_file),
                'cleanName' => $attachment_values['name'],
            ];
        }
        return $attachment ?? null;
    }
}
