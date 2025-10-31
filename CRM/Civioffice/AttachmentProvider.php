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

declare(strict_types = 1);

use Civi\Mailattachment\AttachmentType\AttachmentTypeInterface;
use Civi\Mailattachment\Form\Attachments;
use CRM_Civioffice_ExtensionUtil as E;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CRM_Civioffice_AttachmentProvider implements EventSubscriberInterface, AttachmentTypeInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.mailattachment.attachmentTypes' => 'getAttachmentTypes',
    ];
  }

  public static function getAttachmentTypes($event): void {
    $event->attachment_types['civioffice_document'] = [
      'label' => E::ts('CiviOffice Document'),
      'controller' => self::class,
      'context' => [
        'entity_types' => ['contact', 'contribution', 'participant', 'membership', 'activity', 'case'],
      ],
    ];
  }

  /**
   * {@inheritDoc}
   *
   * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
   */
  public static function buildAttachmentForm(&$form, $attachment_id, $prefix = '', $defaults = []) {
  // phpcs:enable
    $config = CRM_Civioffice_Configuration::getConfig();

    // add list of document renderers and supported output MIME types
    $output_mimetypes = NULL;
    $document_renderer_list = [];
    foreach ($config->getDocumentRenderers(TRUE) as $dr) {
      foreach ($dr->getSupportedOutputMimeTypes() as $mime_type) {
        $output_mimetypes[$mime_type] = CRM_Civioffice_MimeType::mapMimeTypeToFileExtension($mime_type);
      }
      $document_renderer_list[$dr->getURI()] = $dr->getName();
    }
    $form->add(
        'select',
        $prefix . 'attachments--' . $attachment_id . '--document_renderer_uri',
        E::ts('Document Renderer'),
        $document_renderer_list,
        TRUE,
        ['class' => 'crm-select2 huge']
    );

    // build document list
    $document_list = [];
    // todo: only show supported source MIME types
    foreach ($config->getDocumentStores(TRUE) as $document_store) {
      // todo: recursive
      foreach ($document_store->getDocuments() as $document) {
        /** @var CRM_Civioffice_Document $document */
        foreach ($config->getDocumentRenderers(TRUE) as $dr) {
          foreach ($dr->getSupportedInputMimeTypes() as $mime_type) {
            // TODO: Mimetype checks could be handled differently in the future:
            // https://github.com/systopia/de.systopia.civioffice/issues/2
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
        $prefix . 'attachments--' . $attachment_id . '--document_uri',
        E::ts('Document'),
        $document_list,
        TRUE,
        ['class' => 'crm-select2 huge']
    );
    $form->add(
        'select',
        $prefix . 'attachments--' . $attachment_id . '--target_mime_type',
        E::ts('Target document type'),
        $output_mimetypes,
        TRUE,
        ['class' => 'crm-select2']
    );

    // Add Live Snippets.
    $live_snippet_elements = CRM_Civioffice_LiveSnippets::addFormElements(
        $form,
        $prefix . 'attachments--' . $attachment_id . '--',
        $defaults['live_snippets'] ?? []
    );

    $form->add(
        'text',
        $prefix . 'attachments--' . $attachment_id . '--name',
        E::ts('Attachment Name'),
        ['class' => 'huge'],
        FALSE
    );

    $form->setDefaults(
        [
          $prefix . 'attachments--' . $attachment_id . '--document_renderer_uri'
          => $defaults['document_renderer_uri'] ?? NULL,
          $prefix . 'attachments--' . $attachment_id . '--document_uri' => $defaults['document_uri'] ?? NULL,
          $prefix . 'attachments--' . $attachment_id . '--target_mime_type' => $defaults['target_mime_type'] ?? NULL,
          $prefix . 'attachments--' . $attachment_id . '--name' => $defaults['name'] ?? NULL,
        ]
    );

    return [
      $prefix . 'attachments--' . $attachment_id . '--document_renderer_uri'
      => 'attachment-civioffice_document-document_renderer_uri',
      $prefix . 'attachments--' . $attachment_id . '--document_uri' => 'attachment-civioffice_document-document_uri',
      $prefix . 'attachments--' . $attachment_id . '--target_mime_type'
      => 'attachment-civioffice_document-target_mime_type',
      $prefix . 'attachments--' . $attachment_id . '--name' => 'attachment-civioffice_document-name',
    ] + array_fill_keys($live_snippet_elements, 'attachment-civioffice_document-live_snippet');
  }

  public static function getAttachmentFormTemplate($type = 'tpl') {
    return in_array($type, ['tpl', 'hlp']) ? 'CRM/Civioffice/Form/AttachmentProvider.' . $type : NULL;
  }

  /**
   * {@inheritDoc}
   */
  public static function processAttachmentForm(&$form, $attachment_id, $prefix = '') {
    $values = $form->exportValues();
    $live_snippet_values = CRM_Civioffice_LiveSnippets::getFormElementValues(
        $form,
        FALSE,
        $prefix . 'attachments--' . $attachment_id . '--'
    );
    return [
      'document_renderer_uri' => $values[$prefix . 'attachments--' . $attachment_id . '--document_renderer_uri'],
      'document_uri' => $values[$prefix . 'attachments--' . $attachment_id . '--document_uri'],
      'target_mime_type' => $values[$prefix . 'attachments--' . $attachment_id . '--target_mime_type'],
      'name' => $values[$prefix . 'attachments--' . $attachment_id . '--name'],
      'live_snippets' => $live_snippet_values,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function buildAttachment($context, $attachment_values) {
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
        ]
    );
    if (!empty($civioffice_result['is_error']) || empty($civioffice_result['values'][0])) {
      throw new Exception($civioffice_result['error_message']);
    }
    $result_store_uri = $civioffice_result['values'][0];
    $result_store = CRM_Civioffice_Configuration::getDocumentStore($result_store_uri);
    foreach ($result_store->getDocuments() as $document) {
      $attachment_file = $document->getLocalTempCopy();
      $mime_type = Attachments::getMimeType($attachment_file);
      $file_extension = CRM_Civioffice_MimeType::mapMimeTypeToFileExtension($mime_type);

      if (
        !empty($attachment_values['name'])
        && !empty($name_parts = explode('.', $attachment_values['name']))
        && end($name_parts) != $file_extension
      ) {
        $attachment_values['name'] .= '.' . $file_extension;
      }
      else {
        $attachment_values['name'] = $document->getName();
      }
      $attachment = [
        'fullPath' => $attachment_file,
        'mime_type' => $mime_type,
        'cleanName' => $attachment_values['name'] ?: $document->getName(),
      ];
    }
    return $attachment ?? NULL;
  }

}
