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

declare(strict_types = 1);

use Civi\Api4\ActivityContact;
use Civi\Api4\CaseContact;
use Civi\Api4\Contribution;
use Civi\Api4\Membership;
use Civi\Api4\Participant;
use CRM_Civioffice_ExtensionUtil as E;

class CRM_Civioffice_ConversionJob {
  /**
   * @var string
   *  URI which specifies the selected renderer being used
   */
  protected string $renderer_uri;

  /**
   * @var string
   *   The template uri which is used for generating a document
   */
  protected string $document_uri;


  protected CRM_Civioffice_DocumentStore_LocalTemp $temp_store;

  /**
   * @var array
   *   Array with entity IDs (like contact IDs)
   */
  protected array $entity_IDs;

  /**
   * @var string
   *   Type of entity (e.g. 'Contact')
   */
  protected string $entity_type;

  /**
   * @var string
   *   MIME type of target file (like application/pdf)
   */
  protected string $target_mime_type;

  /**
   * @var array
   *   Values for live snippet tokens in the document.
   */
  protected array $live_snippets;

  protected ?int $activity_type_id;

  /**
   * @var string
   *   Title for runner state
   */
  public string $title;

  public function __construct(
    string $renderer_uri,
    string $document_uri,
    string $temp_folder_path,
    array $entity_IDs,
    string $entity_type,
    string $target_mime_type,
    string $title,
    array $live_snippets = [],
    ?int $activity_type_id = NULL
  ) {
    $this->renderer_uri = $renderer_uri;
    $this->document_uri = $document_uri;
    $this->temp_store = new CRM_Civioffice_DocumentStore_LocalTemp($temp_folder_path);
    $this->entity_IDs = $entity_IDs;
    $this->entity_type = $entity_type;
    $this->target_mime_type = $target_mime_type;
    $this->title = $title;
    $this->live_snippets = $live_snippets;
    $this->activity_type_id = $activity_type_id;
  }

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh

  /**
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function run(): bool {
  // phpcs:enable
    // run the API
    $render_result = civicrm_api3('CiviOffice', 'convert', [
      'document_uri'     => $this->document_uri,
      'entity_ids'       => $this->entity_IDs,
      'entity_type'      => $this->entity_type,
      'renderer_uri'     => $this->renderer_uri,
      'target_mime_type' => $this->target_mime_type,
      'live_snippets' => $this->live_snippets,
    ]);

    $result_store_uri = $render_result['values'][0];
    $result_store = CRM_Civioffice_Configuration::getDocumentStore($result_store_uri);

    $source_folder = $result_store->getBaseFolder();
    $destination_folder = $this->temp_store->getBaseFolder();

    // copy files from source to target and overwrite existing files on retry
    shell_exec("cp -rf $source_folder/* $destination_folder");

    if (!$this->temp_store->isReadOnly()) {
      $this->removeFilesAndFolder($source_folder);
    }

    // Create activity, if requested.
    if (!empty($this->activity_type_id)) {
      $live_snippets = CRM_Civioffice_LiveSnippets::get('name');
      $live_snippet_values = $this->live_snippets;
      foreach ($this->entity_IDs as $entity_id) {
        $contact_id = NULL;
        switch ($this->entity_type) {
          case 'Contact':
            $contact_id = $entity_id;
            break;

          case 'Contribution':
            $contact_id = Contribution::get()
              ->addSelect('contact_id')
              ->addWhere('id', '=', $entity_id)
              ->execute()
              ->single()['contact_id'];
            break;

          case 'Participant':
            $contact_id = Participant::get()
              ->addSelect('contact_id')
              ->addWhere('id', '=', $entity_id)
              ->execute()
              ->single()['contact_id'];
            break;

          case 'Membership':
            $contact_id = Membership::get()
              ->addSelect('contact_id')
              ->addWhere('id', '=', $entity_id)
              ->execute()
              ->single()['contact_id'];
            break;

          case 'Activity':
            $contact_id = ActivityContact::get()
              ->addSelect('contact_id')
              ->addWhere('activity_id', '=', $entity_id)
              ->addWhere('record_type_id:name', '=', 'Activity Source')
              ->execute()
                            // Use the first record, if any, as there might be more than one.
              ->first()['contact_id'] ?? NULL;
            break;

          case 'Case':
            $contact_id = CaseContact::get()
              ->addSelect('contact_id')
              ->addWhere('case_id', '=', $entity_id)
              ->execute()
                            // Use the first record, if any, as there might be more than one.
              ->first()['contact_id'] ?? NULL;
            break;
        }
        $filename = CRM_Civioffice_Configuration::getConfig()->getDocument($this->document_uri)->getName();
        civicrm_api3('Activity', 'create', [
          'activity_type_id' => $this->activity_type_id,
          'subject' => E::ts('Document (CiviOffice)'),
          'status_id' => 'Completed',
          'activity_date_time' => date('YmdHis'),
          'target_id' => [$contact_id],
          'details' => '<p>'
          . E::ts('Created from document: %1', [1 => "<code>$filename</code>"])
          . '</p>'
          . '<p>' . E::ts('Live Snippets used:') . '</p>'
          . (!empty($live_snippet_values) ? '<table><tr>' . implode(
                '</tr><tr>',
                array_map(function ($name, $value) use ($live_snippets) {
                    return '<th>' . $live_snippets[$name]['label'] . '</th>'
                        . '<td>' . $value . '</td>';
                }, array_keys($live_snippet_values), $live_snippet_values)
            ) . '</tr></table>' : ''),
        ]);
      }
    }

    return TRUE;
  }

  private function removeFilesAndFolder(string $folder_path): void {
    // delete tmp folder
    foreach (scandir($folder_path) as $file) {
      if ($file != '.' && $file != '..') {
        unlink($folder_path . DIRECTORY_SEPARATOR . $file);
      }
    }
    rmdir($folder_path);
  }

}
