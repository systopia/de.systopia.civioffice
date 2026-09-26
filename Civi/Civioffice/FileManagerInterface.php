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

namespace Civi\Civioffice;

/**
 * @phpstan-type fileT array{
 *   id: int,
 *   file_type_id: int|null,
 *   mime_type: non-empty-string,
 *   uri: non-empty-string,
 *   description: string|null,
 *   upload_date: string,
 *   created_id: int|null,
 *   full_path: non-empty-string,
 * }
 */
interface FileManagerInterface {

  /**
   * @phpstan-return fileT|null
   *
   * @throws \CRM_Core_Exception
   */
  public function get(int $id): ?array;

  /**
   * The content has to be of the current file's MIME type.
   *
   * @throws \CRM_Core_Exception
   */
  public function writeContent(int $fileId, string $content): void;

}
