<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
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
 * This class is a rendering backend
 */
class CRM_Civioffice_Page_RenderDocuments extends CRM_Core_Page
{

    public function run()
    {
        CRM_Utils_System::setTitle(E::ts('CiviOffice - Render Document(s)'));
        $null = null;

        // unset snippet stuff
        unset($_GET['snippet']);
        unset($_REQUEST['snippet']);

        // get the input
        $document_uri = CRM_Utils_Request::retrieve('document_uri', 'String', $null, true);
        $entity_ids = CRM_Utils_Request::retrieve('contact_ids', 'CommaSeparatedIntegers', $null, true);
        $renderer_uri = CRM_Utils_Request::retrieve('renderer_uri', 'String', $null, true);
        $target_mime_type = CRM_Utils_Request::retrieve('target_mime_type', 'String', $null, false, CRM_Civioffice_MimeType::PDF);
        $entity_type = CRM_Utils_Request::retrieve('entity_type', 'String', $null, false, 'contact');
        $activity_type_id = CRM_Utils_Request::retrieve('activity_type_id', 'Integer', $null, false, '');
        $activity_attach_file = CRM_Utils_Request::retrieve('activity_attach_file', 'String', $null, false, '');

        // process input
        $document_uri = base64_decode($document_uri);
        $entity_ids = explode(',', $entity_ids);
        $renderer_uri = base64_decode($renderer_uri);
        $target_mime_type = base64_decode($target_mime_type);

        // then: run the API
        $render_result = civicrm_api3('CiviOffice', 'convert', [
            'document_uri'     => $document_uri,
            'entity_ids'       => $entity_ids,
            'entity_type'      => $entity_type,
            'renderer_uri'     => $renderer_uri,
            'target_mime_type' => $target_mime_type
        ]);
        $result_store_uri = $render_result['values'][0];
        $result_store = CRM_Civioffice_Configuration::getDocumentStore($result_store_uri);
        $rendered_documents = $result_store->getDocuments();

        // if user wants an activity, create + add the document
        if (!empty($activity_type_id)) {
            $activity = civicrm_api3('Activity', 'create', [
                'activity_type_id'   => $activity_type_id,
                'subject'            => E::ts("Document (CiviOffice)"),
                'status_id'          => 'Completed',
                'activity_date_time' => date("YmdHis"),
                'target_id'          => $entity_ids,
            ]);

            // generate & link attachment if requested
            if (!empty($activity_attach_file) && $activity_attach_file !== 'false') {
                foreach ($rendered_documents as $document) {
                    $path_of_local_copy = $document->getLocalTempCopy();
                    // attach rendered document
                    $attachments = [
                        'attachFile_1' => [
                            'location' => $path_of_local_copy,
                            'type' => mime_content_type($path_of_local_copy)
                        ]
                    ];
                    CRM_Core_BAO_File::processAttachment($attachments, 'civicrm_activity', $activity['id']);
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

        // we should not get here
        parent::run();
    }
}
