<?php
declare(strict_types = 1);

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

use Civi\Civioffice\Token\CiviOfficeTokenProcessorInterface;
use CRM_Civioffice_ExtensionUtil as E;

/**
 * CiviOffice.convert specification
 * @param array<string, array<string, mixed>> $spec
 *   API specification blob
 */
function _civicrm_api3_civi_office_convert_spec(array &$spec): void {
  $spec['document_uri'] = [
    'name'         => 'document_uri',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
    'title'        => E::ts('Document URI'),
    'description'  => E::ts('URI of document, e.g. "local::common/example.docx".'),
  ];
  $spec['entity_ids'] = [
    'name'         => 'entity_ids',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
    'title'        => E::ts('Array of entity IDs'),
    'description'  => E::ts('One or more entity IDs as an array, e.g. "[123, 456]".'),
  ];
  $spec['entity_type'] = [
    'name'         => 'entity_type',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
    'title'        => E::ts('Entity type'),
    'description'  => E::ts('Entity type for token replacement, e.g. "contact" or "contribution".'),
  ];
  $spec['renderer_uri']            = [
    'name'         => 'renderer_uri',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
    'title'        => E::ts('Renderer URI'),
    'description'  => E::ts('URI of the renderer, e.g. "unoconv-local".'),
  ];
  $spec['target_mime_type']            = [
    'name'         => 'target_mime_type',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
    'title'        => E::ts('Target MIME type'),
    'description'  => E::ts('Renderer converts given file to this MIME type, e.g. "application/pdf".'),
  ];
  $spec['live_snippets'] = [
    'name' => 'live_snippets',
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => [],
    'title' => E::ts('Live Snippets'),
    'description' => E::ts('Contents for tokens referring to configured Live Snippets.'),
  ];
}

/**
 * CiviOffice.convert: Converter
 *
 * @phpstan-param array<string, mixed> $params
 *   API call parameters
 *
 * @phpstan-return array<string, mixed>
 *   API3 response
 *
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_civi_office_convert(array $params): array {
  /** @var string $documentUri */
  $documentUri = $params['document_uri'];
  /** @phpstan-var list<int> $entityIds */
  $entityIds = $params['entity_ids'];
  /** @var string $entityType */
  $entityType = $params['entity_type'];
  /** @var string $rendererUri */
  $rendererUri = $params['renderer_uri'];
  /** @var string $targetMimeType */
  $targetMimeType = $params['target_mime_type'];
  /** @phpstan-var array<string, string> $liveSnippets */
  $liveSnippets = $params['live_snippets'];

  $configuration = CRM_Civioffice_Configuration::getConfig();
  $renderer = $configuration->getDocumentRenderer($rendererUri);
  if (NULL === $renderer) {
    throw new CRM_Core_Exception(sprintf('Renderer for URI "%s" not found', $rendererUri));
  }

  $inputDocument = $configuration->getDocument($documentUri);
  if (NULL === $inputDocument) {
    throw new CRM_Core_Exception(sprintf('Document for URI "%s" not found', $documentUri));
  }

  $tempStore = new CRM_Civioffice_DocumentStore_LocalTemp();

  /** @var \Civi\Civioffice\Token\CiviOfficeTokenProcessorInterface $tokenProcessor */
  $tokenProcessor = Civi::service(CiviOfficeTokenProcessorInterface::class);

  $inputFileExtension = pathinfo($inputDocument->getPath(), PATHINFO_EXTENSION);

  if (method_exists($inputDocument, 'getAbsolutePath')) {
    $inputFilePath = $inputDocument->getAbsolutePath();
  }
  else {
    $inputFilePath = $tmpInputFilePath = $inputDocument->getLocalTempCopy();
  }

  foreach ($entityIds as $entityId) {
    $tmpDocument = $tempStore->addFile(uniqid('convert') . '.' . $inputFileExtension);
    try {
      $tokenProcessor->replaceTokens(
        $inputFilePath,
        $tmpDocument->getAbsolutePath(),
        $entityType,
        $entityId,
        $liveSnippets
      );
      $outputFileExtension = CRM_Civioffice_MimeType::mapMimeTypeToFileExtension($targetMimeType);
      $outputDocument = $tempStore->addFile("Document-$entityId.$outputFileExtension");
      $renderer->render($tmpDocument->getAbsolutePath(), $outputDocument->getAbsolutePath(), $targetMimeType);
    }
    finally {
      unlink($tmpDocument->getAbsolutePath());
      if (isset($tmpInputFilePath)) {
        unlink($tmpInputFilePath);
      }
    }
  }

  return civicrm_api3_create_success([$tempStore->getURI()], $params, 'CiviOffice', 'convert');
}
