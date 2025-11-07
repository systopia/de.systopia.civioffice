<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
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

namespace Civi\Civioffice\Page;

use Civi\Civioffice\Controller\PageControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * @codeCoverageIgnore
 */
abstract class AbstractControllerPage extends \CRM_Core_Page {

  public function run(): void {
    $request = Request::createFromGlobals();
    $transaction = \CRM_Core_Transaction::create();
    try {
      $response = $this->handle($request);
      $transaction->commit();
    }
    catch (HttpExceptionInterface $e) {
      \Civi::log()->info(
        sprintf(
          'Access to "%s" failed with status code %d: %s',
          $request->getRequestUri(),
          $e->getStatusCode(),
          $e->getMessage(),
        ),
        [
          'exception' => $e,
        ],
      );

      $transaction->rollback()->commit();

      $message = $e->getMessage() !== '' ? $e->getMessage() : (Response::$statusTexts[$e->getStatusCode()] ?? '');
      $response = new Response($message, $e->getStatusCode(), $e->getHeaders());
    }
    catch (\Throwable $e) {
      $transaction->rollback()->commit();

      throw $e;
    }

    $response->send();
    \CRM_Utils_System::civiExit();
  }

  abstract protected function getController(): PageControllerInterface;

  /**
   * @throws \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
   */
  protected function handle(Request $request): Response {
    return $this->getController()->handle($request);
  }

}
