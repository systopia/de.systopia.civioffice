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
     * @var string $renderer_id
     *  ID which specifies the selected renderer being used
     */
    protected $renderer_id;

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
     * @var string $title
     *   Title for runner state
     */
    public $title;

    public function __construct($renderer_id, $document_uri, $temp_folder_path, $entity_IDs, $entity_type, $target_mime_type, $title)
    {
        $this->renderer_id = $renderer_id;
        $this->document_uri = $document_uri;
        $this->temp_store = new CRM_Civioffice_DocumentStore_LocalTemp($target_mime_type, $temp_folder_path);
        $this->entity_IDs = $entity_IDs;
        $this->entity_type = $entity_type;
        $this->target_mime_type = $target_mime_type;
        $this->title = $title;
    }

    public function run(): bool
    {

        $configuration = new CRM_Civioffice_Configuration();
        $config = $configuration::getConfig();
        $document_renderer = $configuration->getDocumentRenderer($this->renderer_id);

        $document = $config->getDocument($this->document_uri);

        $document_renderer->render($document, $this->entity_IDs, $this->temp_store, $this->target_mime_type, $this->entity_type);

        return true;
    }
}