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

use Civi\Api4\File;

/**
 * @phpstan-import-type fileT from FileManagerInterface
 */
final class FileManager implements FileManagerInterface {

  private \CRM_Core_Config $config;

  /**
   * @var array<int, fileT|null>
   */
  private array $files = [];

  public function __construct(?\CRM_Core_Config $config = NULL) {
    $this->config = $config ?? \CRM_Core_Config::singleton();
  }

  public function get(int $id): ?array {
    if (array_key_exists($id, $this->files)) {
      return $this->files[$id];
    }

    $file = File::get(FALSE)
      ->addWhere('id', '=', $id)
      ->addWhere('uri', 'IS NOT NULL')
      ->addWhere('uri', '!=', '')
      ->addWhere('mime_type', 'IS NOT NULL')
      ->addWhere('mime_type', '!=', '')
      ->execute()
      ->first();
    if (NULL !== $file) {
      $file['full_path'] = $this->config->customFileUploadDir . '/' . $file['uri'];
      /** @phpstan-var fileT $file */
      if (!is_file($file['full_path']) || !is_readable($file['full_path'])) {
        $file = NULL;
      }
    }

    return $this->files[$id] = $file;
  }

  public function writeContent(int $fileId, string $content): void {
    $file = $this->get($fileId);
    if (NULL === $file) {
      throw new \RuntimeException("File with ID $fileId not found");
    }

    $uploadDate = $file['upload_date'] = date('Y-m-d H:i:s');
    if (\CRM_Core_Transaction::isActive()) {
      $tmpFilename = tempnam(sys_get_temp_dir(), 'civioffice');
      if (FALSE === file_put_contents($tmpFilename, $content)) {
        throw new \RuntimeException("Failed to write to $tmpFilename");
      }
      \CRM_Core_Transaction::addCallback(
        \CRM_Core_Transaction::PHASE_POST_COMMIT,
        fn() => rename($tmpFilename, $file['full_path']),
      );
      \CRM_Core_Transaction::addCallback(
        \CRM_Core_Transaction::PHASE_POST_ROLLBACK,
        fn() => @unlink($tmpFilename),
      );
    }
    else {
      if (FALSE === file_put_contents($file['full_path'], $content)) {
        throw new \RuntimeException("Failed to write to {$file['full_path']}");
      }
    }

    File::update(FALSE)
      ->addValue('upload_date', $uploadDate)
      ->addWhere('id', '=', $fileId)
      ->execute();
    $this->files[$fileId] = $file;
  }

}
