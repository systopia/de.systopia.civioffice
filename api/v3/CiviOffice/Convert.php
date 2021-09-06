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

require_once 'civioffice.civix.php';

use CRM_Civioffice_ExtensionUtil as E;

/**
 * CiviOffice.convert specification
 * @param array $spec
 *   API specification blob
 */
function _civicrm_api3_civi_office_convert_spec(&$spec)
{
    // first jump
    $spec['document_uri'] = [
        'name'         => 'document_uri',
        'api.required' => 1,
        'title'        => E::ts('Document URI. E.g.: "local::common/vorlage_kontakte_und_zuwendungen.docx"'),
        'description'  => E::ts('URI of document.'),
    ];
    $spec['entity_ids'] = [
        'name'         => 'entity_ids',
        'api.required' => 1,
        'title'        => E::ts('Array of entity ids. E.g.: "[362, 614]"'),
        'description'  => E::ts('One or multiple entity as an array'),
    ];
    $spec['entity_type'] = [
        'name'         => 'entity_type',
        'api.required' => 1,
        'title'        => E::ts('Entity type. E.g.: "contact", "contribution",...'),
        'description'  => E::ts('Entity type for token replacement'),
    ];
    $spec['renderer_uri']            = [
        'name'         => 'renderer_uri',
        'api.required' => 1,
        'title'        => E::ts('Renderer URI. E.g.: "unoconv-local"'),
        'description'  => E::ts('URI of the renderer.'),
    ];

    $spec['target_mime_type']            = [
        'name'         => 'target_mime_type',
        'api.required' => 1,
        'title'        => E::ts('Target / final mime type. E.g.: "application/pdf"'),
        'description'  => E::ts('Renderer converts given file to this mimetype'),
    ];
}

/**
 * CiviOffice.convert: Converter
 *
 * @param array $params
 *   API call parameters
 *
 * @return array
 *   API3 response
 */
function civicrm_api3_civi_office_convert($params)
{
    $document_uri = $params['document_uri'];
    $entity_ids = $params['entity_ids'];
    $entity_type = $params['entity_type'];
    $renderer_uri = $params['renderer_uri'];
    $target_mime_type = $params['target_mime_type'];

    $configuration = CRM_Civioffice_Configuration::getConfig();
    $document_renderer = $configuration->getDocumentRenderer($renderer_uri);
    $document = $configuration->getDocument($document_uri);

    $temp_store = new CRM_Civioffice_DocumentStore_LocalTemp($target_mime_type);

    $documents = $document_renderer->render($document, $entity_ids, $temp_store, $target_mime_type, $entity_type);

    createActivity(
        3, // pdf letter type
        "CiviOffice: $document_uri",
        CRM_Core_Session::getLoggedInContactID(),
        $entity_ids,
        'Beliebiger Text',
        "CiviOffice $entity_type document: Detail text",
        null
    );

    $uri = $temp_store->getURI();

    return [$uri];
}

/**
 * Create an activity
 *
 * @param integer $activity_type_id
 * @param string $subject
 * @param string $status
 * @param string $details
 * @param integer $sender_contact_id
 * @param array $target_contact_ids
 */
function createActivity($activity_type_id, $subject, $sender_contact_id, $target_contact_ids, $status, $details = null, $assignees = '')
{
    try {
        $activity_data = [
            'activity_type_id'  => $activity_type_id,
            'status_id'         => $status,
            'source_contact_id' => $sender_contact_id,
            'target_contact_id' => $target_contact_ids,
            'assignee_id'       => empty($assignees) ? '' : explode(',', $assignees),
            'subject'           => $subject,
            'details'           => $details,
        ];
        civicrm_api3('Activity', 'create', $activity_data);
    } catch (CiviCRM_API3_Exception $ex) {
        Civi::log()->debug("CiviOffice: Couldn't create activity: " . json_encode($activity_data) . ' - error was: ' . $ex->getMessage());
    }
}