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

namespace Civi\Civioffice\Api4\Action\Renderer;

use Civi\Api4\Generic\BasicGetAction;
use Civi\Civioffice\DocumentRenderer;

final class GetAction extends BasicGetAction {

  public function __construct() {
    parent::__construct('CiviofficeRenderer', 'get');
  }

  /**
   * @phpstan-return list<array<string, mixed>>
   */
  protected function getRecords(): array {
    return array_map(
      fn (DocumentRenderer $renderer) => [
        'name' => $renderer->getName(),
        'description' => $renderer->getDescription(),
        'uri' => $renderer->getURI(),
        'supported_mime_types' => $renderer->getSupportedInputMimeTypes(),
        'supported_output_mime_types' => $renderer->getSupportedOutputMimeTypes(),
        'supported_output_file_types' => $this->getSupportedOutputFileTypes($renderer),
        'renderer_type' => $renderer->getTypeName(),
        'renderer_type_uri' => $renderer->getTypeName(),
        'is_active' => $renderer->isReady(),
      ],
      array_values(\CRM_Civioffice_Configuration::getDocumentRenderers())
    );
  }

  /**
   * @phpstan-return array<string, string>
   */
  private function getSupportedOutputFileTypes(DocumentRenderer $renderer): array {
    $fileTypes = [];
    foreach ($renderer->getSupportedOutputMimeTypes() as $mimeType) {
      $fileTypes[\CRM_Civioffice_MimeType::mapMimeTypeToFileExtension($mimeType)] = $mimeType;
    }

    return $fileTypes;
  }

}
