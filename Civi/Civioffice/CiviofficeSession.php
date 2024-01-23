<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2023 SYSTOPIA GmbH                       |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

declare(strict_types = 1);

namespace Civi\Civioffice;

final class CiviofficeSession {

  private static ?self $instance = NULL;

  private \CRM_Core_Session $session;

  public static function getInstance(): self {
    // @phpstan-ignore-next-line
    return self::$instance ??= new self(\CRM_Core_Session::singleton());
  }

  public function __construct(\CRM_Core_Session $session) {
    $this->session = $session;
  }

  /**
   * @see storeTempFolderPath()
   */
  public function getTempFolderPath(string $hash): ?string {
    // @phpstan-ignore-next-line
    return $this->session->get('temp_' . $hash, 'civioffice');
  }

  /**
   * @see storeTempFolderPath()
   */
  public function removeTempFolderPath(string $temp_folder_path): void {
    $hash = sha1($temp_folder_path);
    $this->session->set('temp_' . $hash, NULL, 'civioffice');
  }

  /**
   * @return string A hash that can be used to retrieve the path again.
   *
   * @see getTempFolderPath()
   * @see removeTempFolderPath()
   */
  public function storeTempFolderPath(string $temp_folder_path): string {
    $hash = sha1($temp_folder_path);
    $this->session->set('temp_' . $hash, $temp_folder_path, 'civioffice');

    return $hash;
  }

}
