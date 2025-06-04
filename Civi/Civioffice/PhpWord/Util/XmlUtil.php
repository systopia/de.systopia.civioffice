<?php
/*
 * Refactored code extracted from PHPWord.
 *
 * Additional code Copyright (C) 2025 SYSTOPIA GmbH.
 *
 * @license http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

declare(strict_types = 1);

namespace Civi\Civioffice\PhpWord\Util;

final class XmlUtil {

  /**
   * Removes elements with the given tag type, but keeps their children.
   *
   * @template T of string|array<string>
   *
   * @phpstan-param T $xml
   *
   * @phpstan-return T
   */
  public static function blockElement(string|array $xml, string $tagType): string|array {
    $regex = "#(?:\n\s*)?<$tagType(?: [^>]*)?>((?:(?!</$tagType>).)*)</$tagType>\n?#s";

    $result = array_map(
      function (string $xml) use ($regex) {
        // Indent is corrected (if any).
        preg_match('/\n([ \t]+)/', $xml, $matches);
        $indent = $matches[1] ?? '';

        return '' === $indent ? preg_replace($regex, '$1', $xml) : preg_replace_callback(
          $regex,
          fn (array $matches) => preg_replace("/\n$indent/", "\n", rtrim($matches[1])) . "\n",
          $xml
        );
      },
      (array) $xml
    );

    // @phpstan-ignore return.type
    return is_string($xml) ? $result[0] : $result;
  }

  /**
   * Removes elements with the given tag type including their children. For
   * empty elements use dropEmptyElement().
   *
   * @template T of string|array<string>
   *
   * @phpstan-param T $xml
   *
   * @phpstan-return T
   *
   * @see dropEmptyElement()
   */
  public static function dropElement(string|array $xml, string $tagType): string|array {
    // @phpstan-ignore return.type
    return preg_replace("#(?:\n\s*)?<$tagType(?: [^>]*)?>(?:(?!</$tagType>).)*</$tagType>#s", '', $xml);
  }

  /**
   * Removes empty elements with the given tag type. For elements with children
   * use dropElement().
   *
   * @template T of string|array<string>
   *
   * @phpstan-param T $xml
   *
   * @phpstan-return T
   *
   * @see dropElement()
   */
  public static function dropEmptyElement(string|array $xml, string $tagType): string|array {
    // @phpstan-ignore return.type
    return preg_replace("#(?:\n\s*)?<$tagType(?: [^/>]*)?/>#", '', $xml);
  }

  /**
   * Get a slice of a string.
   */
  public static function getSlice(string $string, int $startPosition, int $endPosition = 0): string {
    if ($endPosition === 0) {
      $endPosition = strlen($string);
    }

    return substr($string, $startPosition, ($endPosition - $startPosition));
  }

  /**
   * Find start and end of XML block containing the given search string
   * e.g. <w:p>...$search...</w:p>.
   *
   * Note that only the first instance of the search string will be found
   *
   * @param string $blockType XML tag for block
   *
   * @return false|array{start: int, end: int} FALSE if not found, otherwise array with start and end
   */
  public static function findContainingXmlBlock(string $xml, string $search, string $blockType = 'w:p'): bool|array {
    $pos = strpos($xml, $search);
    if (FALSE === $pos) {
      return FALSE;
    }
    $start = static::findXmlBlockStart($xml, $pos, $blockType);
    if (0 > $start) {
      return FALSE;
    }
    $end = static::findXmlBlockEnd($xml, $start, $blockType);
    // If not found or if resulting string does not contain the string we are searching for
    if (0 > $end || strstr(static::getSlice($xml, $start, $end), $search) === FALSE) {
      return FALSE;
    }

    return ['start' => $start, 'end' => $end];
  }

  /**
   * Find the start position of the nearest XML block start before $offset.
   *
   * @param int $offset Search position
   * @param string $blockType XML Block tag
   *
   * @return int -1 if block start not found
   */
  public static function findXmlBlockStart(string $xml, int $offset, string $blockType): int {
    $reverseOffset = (strlen($xml) - $offset) * -1;
    // first try XML tag with attributes
    $blockStart = strrpos($xml, '<' . $blockType . ' ', $reverseOffset);
    // if not found, or if found but contains the XML tag without attribute
    if (FALSE === $blockStart || strrpos(static::getSlice($xml, $blockStart, $offset), '<' . $blockType . '>') > 0) {
      // also try XML tag without attributes
      $blockStart = strrpos($xml, '<' . $blockType . '>', $reverseOffset);
    }

    return ($blockStart === FALSE) ? -1 : $blockStart;
  }

  /**
   * Find the nearest block end position after $offset.
   *
   * @param int $offset Search position
   * @param string $blockType XML Block tag
   *
   * @return int -1 if block end not found
   */
  public static function findXmlBlockEnd(string $xml, int $offset, string $blockType): int {
    $blockEndStart = strpos($xml, '</' . $blockType . '>', $offset);
    // return position of end of tag if found, otherwise -1

    return ($blockEndStart === FALSE) ? -1 : $blockEndStart + 3 + strlen($blockType);
  }

  /**
   * Removes whitespace between tags.
   *
   * @template T of string|array<string>
   *
   * @phpstan-param T $xml
   *
   * @phpstan-return T
   */
  public static function flattenXml(string|array $xml): string|array {
    // @phpstan-ignore return.type
    return preg_replace('/>\s+</', '><', $xml);
  }

  /**
   * Replace an XML block surrounding a string with a new block. Only the first
   * block containing the search string is replaced.
   *
   * @param string $block New block content
   * @param string $blockType XML tag type of block
   *
   * @return string The XML with replaced block
   */
  public static function replaceXmlBlock(
    string $xml,
    string $search,
    string $block,
    string $blockType = 'w:p'
  ): string {
    $where = static::findContainingXmlBlock($xml, $search, $blockType);
    if (is_array($where)) {
      return static::getSlice($xml, 0, $where['start']) . $block . static::getSlice($xml, $where['end']);
    }

    return $xml;
  }

}
