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

namespace Civi\Civioffice\Wopi;

final class WopiHeaders {

  public const HEADER_EDITORS = 'X-WOPI-Editors';

  public const HEADER_ITEM_VERSION = 'X-WOPI-ItemVersion';

  public const HEADER_LOCK = 'X-WOPI-Lock';

  public const HEADER_OLD_LOCK = 'X-WOPI-OldLock';

  public const HEADER_OVERRIDE = 'X-WOPI-Override';

  public const HEADER_OVERWRITE_RELATIVE_TARGET = 'X-WOPI-OverwriteRelativeTarget';

  public const HEADER_PROOF = 'X-WOPI-Proof';

  public const HEADER_PROOF_OLD = 'X-WOPI-ProofOld';

  public const HEADER_RELATIVE_TARGET = 'X-WOPI-RelativeTarget';

  public const HEADER_REQUESTED_NAME = 'X-WOPI-RequestedName';

  public const HEADER_SIZE = 'X-WOPI-Size';

  public const HEADER_SUGGESTED_TARGET = 'X-WOPI-SuggestedTarget';

  public const HEADER_TIMESTAMP = 'X-WOPI-Timestamp';

  public const HEADER_URL_TYPE = 'X-WOPI-UrlType';

  public const HEADER_VALID_RELATIVE_TARGET = 'X-WOPI-ValidRelativeTarget';

  public const HEADER_INVALID_FILE_NAME_ERROR = 'X-WOPI-InvalidFileNameError';

}
