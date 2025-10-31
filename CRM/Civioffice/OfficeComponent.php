<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: J. Franz (franz@systopia.de)                   |
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

use CRM_Civioffice_ExtensionUtil as E;

/**
 * abstract CiviOffice component
 *
 * phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
 */
abstract class CRM_Civioffice_OfficeComponent {
// phpcs:enable
  /**
   * Component ID (unique name).
   */
  protected string $uri;

  /**
   * Localized component name (title).
   */
  protected string $name;

  protected function __construct(string $uri, string $name) {
    $this->uri = $uri;
    $this->name = $name;
  }

  /**
   * Get the URL to configure this component
   *
   * @return string
   *   URL to the configuration page, or an empty string if this component
   *   needs no configuration.
   */
  abstract public function getConfigPageURL() : string;

  /**
   * Get the URL to delete this component
   *
   * @return string|null
   */
  public function getDeleteURL(): ?string {
    return NULL;
  }

  /**
   * Is this component ready, i.e. properly
   *   configured and connected
   *
   * @return boolean
   *   URL
   */
  abstract public function isReady() : bool;

  /**
   * Get the component ID (unique name).
   */
  public function getURI(): string {
    return $this->uri;
  }

  /**
   * Get the localized component name (title).
   */
  public function getName(): string {
    return $this->name;
  }

  public function setName(string $name): void {
    $this->name = $name;
  }

  /**
   * Get the localized component description.
   */
  public function getDescription(): string {
    return E::ts('- empty -');
  }

}
