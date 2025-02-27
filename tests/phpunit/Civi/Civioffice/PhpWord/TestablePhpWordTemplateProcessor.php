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

namespace Civi\Civioffice\PhpWord;

final class TestablePhpWordTemplateProcessor extends PhpWordTemplateProcessor {

  /**
   * @param list<string> $headers
   * @param list<string> $footers
   *
   * @phpstan-ignore-next-line Parent constructor is not called.
   */
  public function __construct(string $mainPart, array $headers = [], array $footers = []) {
    $this->tempDocumentMainPart = $mainPart;
    $this->tempDocumentHeaders = $headers;
    $this->tempDocumentFooters = $footers;
  }

  public function getMainPart(): string {
    return $this->tempDocumentMainPart;
  }

  /**
   * @return list<string>
   */
  public function getHeaders(): array {
    return $this->tempDocumentHeaders;
  }

  /**
   * @return list<string>
   */
  public function getFooters(): array {
    return $this->tempDocumentFooters;
  }

}
