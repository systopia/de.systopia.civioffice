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

namespace Civi\Civioffice\PhpWord;

final class StyleMerger {

  private \DOMElement $styleElement;

  /**
   * @phpstan-var array<string, \DOMElement>
   */
  private array $elements = [];

  public static function mergeStyles(string $style, string ...$styles): string {
    $styleMerger = new self($style);
    foreach ($styles as $styleToMerge) {
      $styleMerger->merge($styleToMerge);
    }

    return $styleMerger->getStyleString();
  }

  public function __construct(string $style) {
    $this->styleElement = $this->createStyleElement($style);
    foreach ($this->styleElement->childNodes as $node) {
      if ($node instanceof \DOMElement) {
        $this->elements[$node->tagName] = $node;
      }
    }
  }

  public function merge(string $style): self {
    $styleElement = $this->createStyleElement($style);
    foreach ($styleElement->childNodes as $node) {
      if ($node instanceof \DOMElement) {
        // @todo Do we need recursive merging for some elements?
        if (!isset($this->elements[$node->tagName])) {
          // @phpstan-ignore-next-line
          $importedNode = $this->styleElement->ownerDocument->importNode($node, TRUE);
          if (!$importedNode instanceof \DOMElement) {
            throw new \RuntimeException('Importing node failed');
          }

          $this->styleElement->appendChild($importedNode);
          $this->elements[$node->tagName] = $importedNode;
        }
      }
    }

    return $this;
  }

  public function getStyleString(): string {
    // @phpstan-ignore-next-line
    return $this->styleElement->ownerDocument->saveXML($this->styleElement);
  }

  private function createStyleElement(string $style): \DOMElement {
    if (NULL === $style = preg_replace('/>\s+</', '><', $style)) {
      throw new \RuntimeException('Error processing style');
    }

    $doc = new \DOMDocument();
    $doc->loadXML(
      '<root xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' . $style . '</root>'
    );

    // @phpstan-ignore-next-line
    foreach ($doc->documentElement->childNodes as $node) {
      if ($node instanceof \DOMElement) {
        return $node;
      }
    }

    throw new \RuntimeException('Could not create style element');
  }

}
