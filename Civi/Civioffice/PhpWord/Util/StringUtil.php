<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify it under
 *  the terms of the GNU Affero General Public License as published by the Free
 *  Software Foundation, either version 3 of the License, or (at your option) any
 *  later version.
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

namespace Civi\Civioffice\PhpWord\Util;

final class StringUtil {

  public static function findPos(string $string, string $search, int $offset = 0): ?int {
    if (self::isRegex($search)) {
      preg_match($search, $string, $matches, PREG_OFFSET_CAPTURE, $offset);

      return $matches[0][1] ?? NULL;
    }

    $pos = strpos($string, $search, $offset);

    return $pos === FALSE ? NULL : $pos;
  }

  /**
   * @return bool
   *   TRUE if the given string starts and ends with the same
   *   non-alphanumeric, non-backslash, non-whitespace character, e.g. "/abc/"
   *   or "#^abc$#".
   */
  public static function isRegex(string $string): bool {
    return preg_match('/^(?<delim>[^a-zA-Z0-9\\\s]).*\k<delim>$/', $string) === 1;
  }

}
