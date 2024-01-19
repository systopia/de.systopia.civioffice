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

namespace Civi\Civioffice\Api4\Action\Document;

use Civi\Api4\Generic\BasicGetAction;

final class GetAction extends BasicGetAction {

  public function __construct() {
    parent::__construct('CiviofficeDocument', 'get');
  }

  /**
   * @phpstan-return list<array<string, scalar|null>>
   */
  protected function getRecords(): array {
    $records = [];
    foreach (\CRM_Civioffice_Configuration::getConfig()->getDocuments() as $documentStoreUri => $documents) {
      /** @var \CRM_Civioffice_Document $document */
      foreach ($documents as $document) {
        $records[] = [
          'name' => $document->getName(),
          'uri' => $document->getURI(),
          'mime_type' => $document->getMimeType(),
          'path' => $document->getPath(),
          'document_store_uri' => $documentStoreUri,
        ];
      }
    }

    return $records;
  }

}
