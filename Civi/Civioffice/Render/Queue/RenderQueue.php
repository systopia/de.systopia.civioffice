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

use CRM_Civioffice_ExtensionUtil as E;

class RenderQueue {

  private \CRM_Queue_Queue $crmQueue;

  private int $entityIdCount = 0;

  private string $tempFolderPath;

  public function __construct(\CRM_Queue_Queue $crmQueue, string $tempFolderPath) {
    $this->crmQueue = $crmQueue;
    $this->tempFolderPath = $tempFolderPath;
  }

  /**
   * @phpstan-param list<int> $entityIds
   * @phpstan-param array<string, string> $liveSnippets
   */
  public function addJob(
    string $rendererUri,
    string $documentUri,
    string $entityType,
    array $entityIds,
    string $mimeType,
    array $liveSnippets,
    ?int $activityTypeId
  ): self {
    $this->entityIdCount += count($entityIds);

    $this->crmQueue->createItem(
      new \CRM_Civioffice_ConversionJob(
        $rendererUri,
        $documentUri,
        $this->tempFolderPath,
        $entityIds,
        $entityType,
        $mimeType,
        E::ts('Initialized'),
        $liveSnippets,
        $activityTypeId
      )
    );

    return $this;
  }

  public function getCRMQueue(): \CRM_Queue_Queue {
    return $this->crmQueue;
  }

  public function getEntityIdCount(): int {
    return $this->entityIdCount;
  }

  public function getTempFolderPath(): string {
    return $this->tempFolderPath;
  }

}
