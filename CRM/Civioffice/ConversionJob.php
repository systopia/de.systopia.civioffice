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

class CRM_Civioffice_ConversionJob
{
    /**
     * @var string $renderer_uri
     *  URI which specifies the selected renderer being used
     */
    protected $renderer_uri;

    /**
     * @var $document_uri
     *   The template uri which is used for generating a document
     */
    protected $document_uri;


    protected $temp_store;

    /**
     * @var array $entity_IDs
     *   Array with entity IDs (like contact IDs)
     */
    protected $entity_IDs;

    /**
     * @var string $entity_type
     *   Type of entity (like 'contact' ID)
     */
    protected $entity_type;

    /**
     * @var string $target_mime_type
     *   Mime type of target file (like pdf)
     */
    protected $target_mime_type;

    /**
     * @var array $live_snippets
     *   Values for live snippet tokens in the document.
     */
    protected $live_snippets;

    /**
     * @var string $title
     *   Title for runner state
     */
    public $title;

    public function __construct(
        $renderer_uri,
        $document_uri,
        $temp_folder_path,
        $entity_IDs,
        $entity_type,
        $target_mime_type,
        $title,
        $live_snippets = []
    )
    {
        $this->renderer_uri = $renderer_uri;
        $this->document_uri = $document_uri;
        $this->temp_store = new CRM_Civioffice_DocumentStore_LocalTemp($target_mime_type, $temp_folder_path);
        $this->entity_IDs = $entity_IDs;
        $this->entity_type = $entity_type;
        $this->target_mime_type = $target_mime_type;
        $this->title = $title;
        $this->live_snippets = $live_snippets;
    }

    public function run(): bool
    {
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
        $copy_successful = shell_exec("cp -rf $source_folder/* $destination_folder");

        if (!$this->temp_store->isReadOnly()) {
            $this->removeFilesAndFolder($source_folder);
        }

        return true;
    }

    private function removeFilesAndFolder(string $folder_path): void
    {
        // delete tmp folder
        foreach (scandir($folder_path) as $file) {
            if ($file != '.' && $file != '..') {
                unlink($folder_path . DIRECTORY_SEPARATOR . $file);
            }
        }
        rmdir($folder_path);
    }
}