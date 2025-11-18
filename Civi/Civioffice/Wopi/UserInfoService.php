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

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Contact;
use Civi\RemoteTools\RequestContext\RequestContextInterface;

final class UserInfoService {

  // @phpstan-ignore class.notFound
  private ?RequestContextInterface $requestContext;

  /**
   * When de.systopia.remotetools is active the request context will be
   * injected.
   *
   * @phpstan-ignore class.notFound
   */
  public function __construct(?RequestContextInterface $requestContext = NULL) {
    $this->requestContext = $requestContext;
  }

  /**
   * @throws \Civi\API\Exception\UnauthorizedException
   *   If no user is logged in.
   */
  public function getContactId(): int {
    // @phpstan-ignore class.notFound
    if ($this->requestContext?->isRemote()) {
      // @phpstan-ignore class.notFound
      $contactId = $this->requestContext->getResolvedContactId();
    }
    else {
      $contactId = \CRM_Core_Session::getLoggedInContactID();
    }

    if (NULL === $contactId) {
      throw new UnauthorizedException('No user is logged in');
    }

    return $contactId;
  }

  public function getDisplayName(int $contactId): string {
    return Contact::get(FALSE)
      ->addSelect('display_name')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->single()['display_name'];
  }

  public function isAdmin(int $contactId): bool {
    return \CRM_Core_Permission::check('administer CiviCRM', $contactId);
  }

  public function isValidContactId(int $contactId): bool {
    return Contact::get(FALSE)
      ->addSelect('id')
      ->addWhere('id', '=', $contactId)
      ->addWhere('is_deleted', '=', FALSE)
      ->execute()
      ->count() === 1;
  }

}
