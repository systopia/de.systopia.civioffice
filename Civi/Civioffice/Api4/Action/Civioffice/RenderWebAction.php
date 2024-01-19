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

namespace Civi\Civioffice\Api4\Action\Civioffice;

use Assert\Assertion;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Civioffice\Render\Queue\RenderQueueBuilderFactory;
use Civi\Civioffice\Render\Queue\RenderQueueRunner;

/**
 * @method string getEntityType()
 * @phpstan-method list<int> getEntityIds()
 * @method string getRendererUri()
 * @method $this setRendererUri(string $rendererUri)
 * @method string getDocumentUri()
 * @method $this setDocumentUri(string $documentUri)
 * @method string getMimeType()
 * @method $this setMimeType(string $mimeType)
 * @method int getBatchSize()
 * @method $this setBatchSize(int $batchSize)
 * @method int|null getActivityTypeId()
 * @method $this setActivityTypeId(int|null $activityTypeId)
 * @phpstan-method array<string, string> getLiveSnippets()
 */
class RenderWebAction extends AbstractAction {

  /**
   * @var array
   * @phpstan-var list<int>
   * @required
   */
  protected array $entityIds = [];

  /**
   * @var string
   * @required
   */
  protected ?string $entityType = NULL;

  /**
   * @var string
   * @required
   */
  protected ?string $rendererUri = NULL;

  /**
   * @var string
   * @required
   */
  protected ?string $documentUri = NULL;

  /**
   * @var string
   * @required
   */
  protected ?string $mimeType = NULL;

  /**
   * @var int
   */
  protected int $batchSize = 10;

  /**
   * @var int|null
   */
  protected ?int $activityTypeId = NULL;

  /**
   * @var array
   * @phpstan-var array<string, string>
   */
  protected array $liveSnippets = [];

  private RenderQueueBuilderFactory $queueBuilderFactory;

  private RenderQueueRunner $queueRunner;

  public function __construct(RenderQueueBuilderFactory $queueBuilderFactory, RenderQueueRunner $queueRunner) {
    parent::__construct('Civioffice', 'render');
    $this->queueBuilderFactory = $queueBuilderFactory;
    $this->queueRunner = $queueRunner;
  }

  /**
   * @param list<int> $entityIds
   */
  public function setEntityIds(array $entityIds): self {
    Assertion::allInteger($entityIds, 'Entity IDs must be of type integer');
    $this->entityIds = $entityIds;

    return $this;
  }

  public function setEntityType(string $entityType): self {
    Assertion::alnum($entityType, 'entityType must only contain alpha-numeric characters.');
    $this->entityType = $entityType;

    return $this;
  }

  /**
   * @phpstan-param array<string, string> $liveSnippets
   */
  public function setLiveSnippets(array $liveSnippets): self {
    Assertion::allString($liveSnippets, 'liveSnippets may only contain strings.');
    Assertion::allString(array_keys($liveSnippets), 'liveSnippets may only contain string keys.');
    $this->liveSnippets = $liveSnippets;

    return $this;
  }

  /**
   * @inheritDoc
   */
  public function _run(Result $result): void {
    if ($this->getCheckPermissions()) {
      $this->entityIds = civicrm_api4($this->getEntityType(), 'get', [
        'select' => ['id'],
        'where' => [['id', 'IN', $this->entityIds]],
      ])->column('id');
    }

    $queue = $this->queueBuilderFactory->createQueueBuilder()
      ->setEntityType($this->getEntityType())
      ->setEntityIds($this->getEntityIds())
      ->setRendererUri($this->getRendererUri())
      ->setDocumentUri($this->getDocumentUri())
      ->setMimeType($this->getMimeType())
      ->setBatchSize($this->getBatchSize())
      ->setActivityTypeId($this->getActivityTypeId())
      ->setLiveSnippets($this->getLiveSnippets())
      ->build();

    /*
     * @todo: Instead of redirecting to the web-based queue-runner there should
     * be made an API call for each job and finally a redirect to an URL
     * providing the generated file as "inline" (content disposition).
     */
    $result['redirect'] = $this->queueRunner->runViaWebUrl($queue);

    $this->persistLastValues();
  }

  private function persistLastValues(): void {
    if (NULL !== \CRM_Core_Session::getLoggedInContactID()) {
      $contactSettings = \Civi::contactSettings();

      $contactSettings->set(
        'civioffice.create_' . $this->getEntityType() . '.activity_type_id',
        $this->getActivityTypeId()
      );

      foreach ($this->getLiveSnippets() as $name => $value) {
        $contactSettings->set('civioffice.live_snippets.' . $name, $value);
      }
    }
  }

}
