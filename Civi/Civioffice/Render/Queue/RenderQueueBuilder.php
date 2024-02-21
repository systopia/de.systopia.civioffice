<?php
/*
 * Copyright (C) 2024 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\Civioffice\Render\Queue;

use Assert\Assertion;

class RenderQueueBuilder {

  private \CRM_Queue_Service $queueService;

  /**
   * @phpstan-var list<int>
   */
  private array $entityIds = [];

  private string $entityType;

  private string $rendererUri;

  private string $documentUri;

  private string $mimeType;

  /**
   * @phpstan-var positive-int
   */
  private int $batchSize = 10;

  private ?int $activityTypeId = NULL;

  /**
   * @phpstan-var array<string, string>
   */
  private array $liveSnippets = [];

  public static function new(\CRM_Queue_Service $queueService): self {
    return new self($queueService);
  }

  public function __construct(\CRM_Queue_Service $queueService) {
    $this->queueService = $queueService;
  }

  /**
   * @phpstan-param list<int> $entityIds
   */
  public function setEntityIds(array $entityIds): self {
    $this->entityIds = $entityIds;

    return $this;
  }

  public function setEntityType(string $entityType): self {
    $this->entityType = $entityType;

    return $this;
  }

  public function setRendererUri(string $rendererUri): self {
    $this->rendererUri = $rendererUri;

    return $this;
  }

  public function setDocumentUri(string $documentUri): self {
    $this->documentUri = $documentUri;

    return $this;
  }

  public function setMimeType(string $mimeType): self {
    $this->mimeType = $mimeType;

    return $this;
  }

  public function setBatchSize(int $batchSize): self {
    Assertion::min($batchSize, 1);
    /** @phpstan-var positive-int $batchSize */
    $this->batchSize = $batchSize;

    return $this;
  }

  public function setActivityTypeId(?int $activityTypeId): self {
    $this->activityTypeId = $activityTypeId;

    return $this;
  }

  /**
   * @phpstan-param array<string, string> $liveSnippets
   */
  public function setLiveSnippets(array $liveSnippets): self {
    $this->liveSnippets = $liveSnippets;

    return $this;
  }

  public function build(): RenderQueue {
    Assertion::true(isset($this->entityType), 'Entity type not set.');
    Assertion::notEmpty($this->entityIds, 'No entity IDs set.');
    Assertion::notEmpty($this->rendererUri, 'No renderer URI set.');
    Assertion::notEmpty($this->mimeType, 'No MIME type set.');

    $contactId = \CRM_Core_Session::getLoggedInContactID() ?? 0;

    $crmQueue = $this->queueService->create(
      [
        'type' => 'Sql',
        'name' => 'civioffice_document_task_' . $contactId,
        'payload' => 'task',
        'reset' => TRUE,
        'is_persistent' => FALSE,
      ]
    );

    $tempFolderPath = (new \CRM_Civioffice_DocumentStore_LocalTemp())->getBaseFolder();
    $queue = new RenderQueue($crmQueue, $tempFolderPath);

    foreach (array_chunk($this->entityIds, $this->batchSize) as $ids) {
      $queue->addJob(
        $this->rendererUri,
        $this->documentUri,
        $this->entityType,
        $ids,
        $this->mimeType,
        $this->liveSnippets,
        $this->activityTypeId
      );
    }

    return $queue;
  }

}
