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

namespace Civi\Civioffice\Wopi\Controller;

use Civi\Civioffice\Wopi\Request\CollaboraWopiRequestHandler;
use Civi\Civioffice\Wopi\Validation\WopiRequestValidator;

final class CollaboraWopiFileController extends WopiFileController {

  public function __construct(CollaboraWopiRequestHandler $requestHandler, WopiRequestValidator $requestValidator) {
    parent::__construct($requestHandler, $requestValidator);
  }

}
