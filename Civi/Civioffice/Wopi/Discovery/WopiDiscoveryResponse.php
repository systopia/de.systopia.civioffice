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

namespace Civi\Civioffice\Wopi\Discovery;

use Civi\Civioffice\Wopi\WopiProofKey;

/**
 * @phpstan-type actionT array{
 *   name: non-empty-string,
 *   ext: string,
 *   default: bool,
 *   urlsrc: non-empty-string,
 *   appName: non-empty-string,
 *   favIconUrl: string,
 * }
 */
final class WopiDiscoveryResponse {

  private \SimpleXMLElement $discovery;

  /**
   * @throws \InvalidArgumentException
   */
  public function __construct(string $discoveryXml) {
    try {
      $this->discovery = new \SimpleXMLElement($discoveryXml);
    }
    catch (\Exception $e) {
      throw new \InvalidArgumentException('Invalid discovery XML', $e->getCode(), $e);
    }
  }

  /**
   * @phpstan-return list<actionT>
   */
  public function getActions(): array {
    $actions = [];
    $appElements = $this->queryXPath('//net-zone/app');
    foreach ($appElements as $appElement) {
      $actionElements = $appElement->xpath('action[@ext]');
      if (!is_array($actionElements)) {
        continue;
      }

      foreach ($actionElements as $actionElement) {
        $actions[] = $this->toActionArray($appElement, $actionElement);
      }
    }

    return $actions;
  }

  public function getActionUrlByExtension(string $extension, string $actionName): ?string {
    return $this->getActionByExtension($extension, $actionName)['urlsrc'] ?? NULL;
  }

  public function getActionUrlByMimeType(string $mimeType, string $actionName): ?string {
    return $this->getActionByMimeType($mimeType, $actionName)['urlsrc'] ?? NULL;
  }

  /**
   * @phpstan-return actionT|null
   */
  public function getActionByExtension(string $extension, string $actionName): ?array {
    $actions = $this->getActionsByExtension($extension, $actionName);

    return $this->getFirstOrDefaultAction($actions);
  }

  /**
   * @phpstan-return actionT|null
   */
  public function getActionByMimeType(string $mimeType, string $actionName): ?array {
    $actions = $this->getActionsByMimeType($mimeType, $actionName);

    return $this->getFirstOrDefaultAction($actions);
  }

  /**
   * @phpstan-return list<actionT>
   */
  public function getActionsByExtension(string $extension, string $actionName): array {
    $actions = [];
    $appElements = $this->queryXPath('//net-zone/app');
    foreach ($appElements as $appElement) {
      $actionElements = $appElement->xpath(sprintf('action[@ext="%s" and @name="%s"]', $extension, $actionName));

      if (!is_array($actionElements)) {
        continue;
      }

      foreach ($actionElements as $actionElement) {
        $actions[] = $this->toActionArray($appElement, $actionElement);
      }
    }

    return $actions;
  }

  /**
   * @phpstan-return list<actionT>
   */
  public function getActionsByMimeType(string $mimeType, string $actionName): array {
    $actions = [];
    $appElements = $this->queryXPath(sprintf("//net-zone/app[@name='%s']", $mimeType));
    foreach ($appElements as $appElement) {
      $actionElements = $appElement->xpath("action[@name='$actionName']");
      if (!is_array($actionElements)) {
        continue;
      }

      foreach ($actionElements as $actionElement) {
        $actions[] = $this->toActionArray($appElement, $actionElement);
      }
    }

    return $actions;
  }

  public function getCapabilitiesUrl(): string {
    return (string) $this->queryXPath("//net-zone/app[@name='Capabilities']/action/@urlsrc")[0];
  }

  public function hasProofKeys(): bool {
    return (bool) $this->discovery->xpath('//proof-key');
  }

  public function getProofKey(): string {
    return (string) $this->queryXPath('//proof-key/@value')[0];
  }

  public function getProofModulus(): string {
    return (string) $this->queryXPath('//proof-key/@modulus')[0];
  }

  public function getProofExponent(): string {
    return (string) $this->queryXPath('//proof-key/@exponent')[0];
  }

  public function getProofKeyRsa(): WopiProofKey {
    return new WopiProofKey($this->getProofModulus(), $this->getProofExponent());
  }

  public function getOldProofKey(): string {
    return (string) $this->queryXPath('//proof-key/@oldvalue')[0];
  }

  public function getOldProofModulus(): string {
    return (string) $this->queryXPath('//proof-key/@oldmodulus')[0];
  }

  public function getOldProofExponent(): string {
    return (string) $this->queryXPath('//proof-key/@oldexponent')[0];
  }

  public function getOldProofKeyRsa(): WopiProofKey {
    return new WopiProofKey($this->getOldProofModulus(), $this->getOldProofExponent());
  }

  /**
   * @param list<actionT> $actions
   *
   * @phpstan-return actionT|null
   */
  private function getFirstOrDefaultAction(array $actions): ?array {
    foreach ($actions as $action) {
      if ($action['default']) {
        return $action;
      }
    }

    return $actions[0] ?? NULL;
  }

  /**
   * @return  list<\SimpleXMLElement>
   */
  private function queryXPath(string $expression): array {
    $elements = $this->discovery->xpath($expression);
    if (!is_array($elements)) {
      throw new \RuntimeException('Could not find element in discovery XML');
    }

    // @phpstan-ignore return.type
    return $elements;
  }

  /**
   * @phpstan-return actionT
   */
  private function toActionArray(\SimpleXMLElement $appElement, \SimpleXMLElement $actionElement): array {
    $actionAttributes = $actionElement->attributes();
    $actionAttributes = (array) reset($actionAttributes);
    $actionAttributes['default'] = 'true' === ($actionAttributes['default'] ?? NULL);

    // @phpstan-ignore return.type
    return $actionAttributes + [
      'ext' => '',
      'appName' => (string) $appElement['name'],
      'favIconUrl' => (string) $appElement['favIconUrl'],
    ];
  }

}
