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

use Civi\Civioffice\CiviofficeSession;
use CRM_Civioffice_ExtensionUtil as E;

class RenderQueueRunner {

  /**
   * Sends an HTTP redirect to a page displaying the process and redirecting to
   * result on success.
   *
   * @phpstan-return never
   *
   * @see \CRM_Queue_Runner::runAllViaWeb()
   */
  public function runViaWebRedirect(RenderQueue $queue, ?string $returnUrl = NULL): void {
    $this->createRunner($queue, $returnUrl)->runAllViaWeb();
  }

  /**
   * Like runViaWebRedirect(), but returns the URL instead of sending an HTTP
   * redirect response.
   */
  public function runViaWebUrl(RenderQueue $queue, ?string $returnUrl = NULL): string {
    $runner = $this->createRunner($queue, $returnUrl);
    // @phpstan-ignore-next-line
    $_SESSION['queueRunners'][$runner->qrid] = serialize($runner);

    return \CRM_Utils_System::url(
      $runner->pathPrefix . '/runner',
      'reset=1&qrid=' . urlencode((string) $runner->qrid),
      FALSE,
      NULL,
      FALSE
    );
  }

  private function createRunner(RenderQueue $queue, ?string $returnUrl): \CRM_Queue_Runner {
    $tempFolderPathHash = CiviofficeSession::getInstance()->storeTempFolderPath($queue->getTempFolderPath());
    $query = [
      'id' => $tempFolderPathHash,
      'instant_download' => '1',
    ];

    if (NULL !== $returnUrl) {
      $query['instant_download'] = '0';
      $query['return_url'] = base64_encode(html_entity_decode($returnUrl));
    }

    $downloadLink = \CRM_Utils_System::url('civicrm/civioffice/download', $query, FALSE, NULL, FALSE);

    return new \CRM_Queue_Runner(
      [
        'title' => E::ts('Generating %1 files', [1 => $queue->getEntityIdCount()]),
        'queue' => $queue->getCRMQueue(),
        'errorMode' => \CRM_Queue_Runner::ERROR_ABORT,
        'onEndUrl' => $downloadLink,
      ]
    );
  }

}
