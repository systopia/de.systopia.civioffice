<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
 *
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\Civioffice\Wopi\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When calling any of the methods the access token has been validated, and it
 * has been verified that the contact still exists. It is not verified, if the
 * file still exists.
 */
interface WopiRequestHandlerInterface {

  /**
   * @return array{
   *   BaseFileName: string,
   *   Size: int,
   *   UserCanWrite: bool,
   *   }
   *   Might contain additional entries.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
   */
  public function checkFileInfo(int $fileId, int $contactId, Request $request): array;

  /**
   * @throws \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
   */
  public function getFile(int $fileId, int $contactId, Request $request): Response;

  /**
   * @return array<string, mixed>
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
   */
  public function putFile(
    int $fileId,
    int $contactId,
    string $content,
    Request $request
  ): array;

}
