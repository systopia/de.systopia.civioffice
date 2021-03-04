<?php

use CRM_Civioffice_ExtensionUtil as E;

class CRM_Civioffice_GenerateConversionJob
{
    /**
     * @var string $renderer_id
     *  ID which specifies the selected renderer being used
     */
    protected $renderer_id;

    /**
     * @var $document_id
     *   The template to use for generating the letter.
     */
    protected $document_id;

    protected $entity_IDs;

    /**
     * @var string $target_mime_type
     *   Mime type like pdf of target file
     */
    protected $target_mime_type;

    protected $entity_type;

    public $title;

    public function __construct($renderer_id, $document_id, $entity_IDs, $target_mime_type, $entity_type, $title)
    {
        $this->renderer_id = $renderer_id;
        $this->document_id = $document_id;
        $this->entity_IDs = $entity_IDs;
        $this->target_mime_type = $target_mime_type;
        $this->entity_type = $entity_type;
        $this->title = $title;
    }

    public function run(): bool
    {

        $configuration = new CRM_Civioffice_Configuration();
        $config = $configuration::getConfig();
        $document_renderer = $configuration->getDocumentRenderer($this->renderer_id);

        $document = $config->getDocument($this->document_id);

        $document_renderer->render($document, $this->entity_IDs, $this->target_mime_type, $this->entity_type);

        return true;
    }
}