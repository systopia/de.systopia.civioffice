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

namespace Civi\Civioffice;

use FilesystemIterator;

final class FilesystemUtil {

  public static function isDirEmpty(string $path): bool {
    $iterator = new FilesystemIterator($path);

    return !$iterator->valid();
  }

  public static function removeRecursive(string $path): void {
    if (!is_dir($path)) {
      if (!unlink($path)) {
        throw new \RuntimeException("Unable to remove '$path'");
      }

      return;
    }

    /** @var iterable<\SplFileInfo> $fileInfoIterator */
    $fileInfoIterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($fileInfoIterator as $fileInfo) {
      if ($fileInfo->isDir()) {
        if (!rmdir($fileInfo->getRealPath())) {
          throw new \RuntimeException("Unable to remove '{$fileInfo->getRealPath()}'");
        }
      }
      else {
        if (!unlink($fileInfo->getRealPath())) {
          throw new \RuntimeException("Unable to remove '{$fileInfo->getRealPath()}'");
        }
      }
    }

    if (!rmdir($path)) {
      throw new \RuntimeException("Unable to remove '$path'");
    }
  }

}
