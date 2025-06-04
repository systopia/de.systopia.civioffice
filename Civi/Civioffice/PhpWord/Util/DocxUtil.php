<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
 *
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Lesser General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\Civioffice\PhpWord\Util;

final class DocxUtil {

  /**
   * Combines runs (element r) that have no visible impact, i.e. have the same
   * properties as the previous run (precisely: have identical content before
   * t element), but only differ in identifiers used to track the editing session
   * (attributes rsidDel, rsidRPr, and rsidR).
   *
   * Runs won't be combined if there are elements between the runs. Thus, you
   * might want to drop annotations before.
   *
   * See: https://ooxml.info/docs/17/17.3/17.3.2/17.3.2.25/#attributes
   *
   * @template T of string|array<string>
   *
   * @phpstan-param T $xml
   *
   * @phpstan-return T
   *
   * @see dropAnnotations()
   */
  public static function combineRuns(string|array $xml): string|array {
    $rRegex = '<w:r(?: w:(?:rsidDel|rsidRPr|rsidR)="[^"]+")*>';
    $regex =
      '#'
      . $rRegex . '(?<not_t>(?:(?!<w:t>).)*)<w:t>(?<t1>(?:(?!</w:t>).)*)</w:t>[\s]*</w:r>[\s]*'
      . $rRegex . '\k<not_t><w:t>(?<t2>(?:(?!</w:t>).)*)</w:t>#s';

    $xmlNew = $xml;
    do {
      /** @phpstan-var T $xmlNew */
      $xml = $xmlNew;
      $xmlNew = preg_replace($regex, '<w:r>$1<w:t>$2$3</w:t>', $xml);
    } while ($xml !== $xmlNew);

    return $xmlNew;
  }

  /**
   * Drops annotations. See https://ooxml.info/docs/17/17.13/.
   * This covers not all annotations, but at least those that affect text runs.
   *
   * @template T of string|array<string>
   *
   * @phpstan-param T $xml
   *
   * @phpstan-return T
   */
  public static function dropAnnotations(string|array $xml): string|array {
    // Remove bookmarks https://ooxml.info/docs/17/17.13/17.13.6/
    $xml = XmlUtil::dropEmptyElement($xml, 'w:bookmarkStart');
    $xml = XmlUtil::dropEmptyElement($xml, 'w:bookmarkEnd');

    // Remove comment anchor https://ooxml.info/docs/17/17.13/17.13.4/
    $xml = XmlUtil::dropEmptyElement($xml, 'w:commentRangeStart');
    $xml = XmlUtil::dropEmptyElement($xml, 'w:commentRangeEnd');
    $xml = XmlUtil::dropEmptyElement($xml, 'w:commentReference');

    // Remove deleted content https://ooxml.info/docs/17/17.13/17.13.5/17.13.5.14/
    $xml = XmlUtil::dropElement($xml, 'w:del');

    // Drop ins elements, but keep inserted content https://ooxml.info/docs/17/17.13/17.13.5/17.13.5.18/
    $xml = XmlUtil::blockElement($xml, 'w:ins');

    // Drop move annotation https://ooxml.info/docs/17/17.13/17.13.5/17.13.5.21/
    $xml = XmlUtil::dropEmptyElement($xml, 'w:moveFromRangeStart');
    $xml = XmlUtil::dropEmptyElement($xml, 'w:moveFromRangeEnd');
    $xml = XmlUtil::dropEmptyElement($xml, 'w:moveFrom');

    // https://ooxml.info/docs/17/17.13/17.13.5/17.13.5.22/
    $xml = XmlUtil::dropEmptyElement($xml, 'w:moveToRangeStart');
    $xml = XmlUtil::dropEmptyElement($xml, 'w:moveToRangeEnd');
    $xml = XmlUtil::blockElement($xml, 'w:moveTo');
    $xml = XmlUtil::dropElement($xml, 'w:moveFrom');

    // Remove pPrChange https://ooxml.info/docs/17/17.13/17.13.5/17.13.5.29/
    $xml = XmlUtil::dropElement($xml, 'w:pPrChange');

    // Remove rPrChange https://ooxml.info/docs/17/17.13/17.13.5/17.13.5.30/
    $xml = XmlUtil::dropElement($xml, 'w:rPrChange');

    // There are more revision annotations, but they don't affect run elements.
    // https://ooxml.info/docs/17/17.13/17.13.5/

    // Remove range permissions https://ooxml.info/docs/17/17.13/17.13.7/
    $xml = XmlUtil::dropEmptyElement($xml, 'w:permStart');
    $xml = XmlUtil::dropEmptyElement($xml, 'w:permEnd');

    // Remove spelling annotations https://ooxml.info/docs/17/17.13/17.13.8/
    return XmlUtil::dropEmptyElement($xml, 'w:proofErr');
  }

  /**
   * Splits a w:p into a list of w:p where each macro}is in a separate w:p.
   *
   * @param string $extractedParagraphStyle
   *   Is set to the extracted paragraph style (w:pPr).
   * @param string $extractedTextRunStyle
   *   Is set to the extracted text run style (w:rPr).
   */
  public static function splitParagraphIntoParagraphs(
    string $paragraph,
    string $macroOpeningChars,
    string $macroClosingChars,
    string &$extractedParagraphStyle = '',
    string &$extractedTextRunStyle = ''
  ): string {
    $paragraph = XmlUtil::flattenXml($paragraph);

    $matches = [];
    preg_match('#<w:pPr(?:(?!<w:pPr).)*</w:pPr>#', $paragraph, $matches);
    $extractedParagraphStyle = $matches[0] ?? '';

    // <w:pPr> may contain <w:rPr> itself so we have to match for <w:rPr> inside <w:r>
    preg_match('#<w:r>(?:(?!<w:r>).)*(<w:rPr(?:(?!<w:rPr).)*</w:rPr>)(?:(?!<w:r>).)*</w:r>#', $paragraph, $matches);
    $extractedTextRunStyle = $matches[1] ?? '';

    $result = str_replace(
      [
        '<w:t>',
        $macroOpeningChars,
        $macroClosingChars,
      ],
      [
        '<w:t xml:space="preserve">',
        sprintf(
          '</w:t></w:r></w:p><w:p>%s<w:r><w:t xml:space="preserve">%s' . $macroOpeningChars,
          $extractedParagraphStyle,
          $extractedTextRunStyle
        ),
        sprintf(
          $macroClosingChars . '</w:t></w:r></w:p><w:p>%s<w:r>%s<w:t xml:space="preserve">',
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
