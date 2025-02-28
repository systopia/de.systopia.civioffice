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

final class TemplateUtil {

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
  public static function findContainingXmlBlock(string $xml, string $search, string $blockType = 'w:p') {
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

  /**
   * Splits a w:p into a list of w:p where each ${macro} is in a separate w:p.
   *
   * @param string $extractedParagraphStyle
   *   Is set to the extracted paragraph style (w:pPr).
   * @param string $extractedTextRunStyle
   *   Is set to the extracted text run style (w:rPr).
   *
   * @throws \PhpOffice\PhpWord\Exception\Exception
   */
  public static function splitParagraphIntoParagraphs(
    string $paragraph,
    string &$extractedParagraphStyle = '',
    string &$extractedTextRunStyle = ''
  ): string {
    if (NULL === $paragraph = preg_replace('/>\s+</', '><', $paragraph)) {
      throw new \PhpOffice\PhpWord\Exception\Exception('Invalid paragraph.');
    }

    $matches = [];
    preg_match('#<w:pPr.*</w:pPr>#i', $paragraph, $matches);
    $extractedParagraphStyle = $matches[0] ?? '';

    // <w:pPr> may contain <w:rPr> itself so we have to match for <w:rPr> inside of <w:r>
    preg_match('#<w:r>.*(<w:rPr.*</w:rPr>).*</w:r>#i', $paragraph, $matches);
    $extractedTextRunStyle = $matches[1] ?? '';

    $result = str_replace(
      [
        '<w:t>',
        '${',
        '}',
      ],
      [
        '<w:t xml:space="preserve">',
        sprintf(
          '</w:t></w:r></w:p><w:p>%s<w:r><w:t xml:space="preserve">%s${',
          $extractedParagraphStyle,
          $extractedTextRunStyle
        ),
        sprintf(
          '}</w:t></w:r></w:p><w:p>%s<w:r>%s<w:t xml:space="preserve">',
          $extractedParagraphStyle,
          $extractedTextRunStyle
        ),
      ],
      $paragraph
    );

    // Remove empty paragraphs that might have been created before/after the
    // macro.
    $emptyParagraph = sprintf(
      '<w:p>%s<w:r>%s<w:t xml:space="preserve"></w:t></w:r></w:p>',
      $extractedParagraphStyle,
      $extractedTextRunStyle
    );

    return str_replace($emptyParagraph, '', $result);
  }

}
