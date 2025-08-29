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
   * Runs without content (i.e. no t element) are ignored.
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
    if (is_array($xml)) {
      return array_map(__METHOD__, $xml);
    }

    $rRegex = '<w:r(?: w:(rsidDel|rsidRPr|rsidR)="[^"]+")*>';
    $tRegex = '<w:t(?: xml:space="[^"]*")?>';
    // Matches if first w:t and second w:t have the same characters between w:r and w:t.
    // <not_t>: Characters between first w:r and first w:t.
    // <t1_tag>: The first opening w:t tag.
    // <t1_content>: Content of first w:t.
    // <t2_tag>: The second opening w:t tag.
    // <t2_content>: Content of second w:t.
    // <remaining>: Characters after second w:t. (Normally only white space and closing w:r tag.)
    $regex =
      "#$rRegex(?<not_t>(?:(?!$tRegex).)*)(?<t1_tag>$tRegex)(?<t1_content>(?:(?!</w:t>).)*)</w:t>\s*</w:r>\s*"
      . "$rRegex\k<not_t>(?<t2_tag>$tRegex)(?<t2_content>(?:(?!</w:t>).)*)</w:t>(?<remaining>.*)#s";

    // Find run that contains text.
    $run1 = XmlUtil::findContainingXmlBlock($xml, "/$tRegex/", 'w:r');
    while (FALSE !== $run1) {
      // Find second run that contains text.
      $run2 = XmlUtil::findContainingXmlBlock($xml, "/$tRegex/", 'w:r', $run1['end']);
      if (FALSE === $run2) {
        break;
      }

      $betweenRunsSlice = XmlUtil::getSlice($xml, $run1['end'], $run2['start']);
      if (preg_match('/^\s*$/', $betweenRunsSlice) !== 1) {
        // There's not only whitespace between the two runs.
        $run1 = $run2;
        continue;
      }

      $runsSlice = XmlUtil::getSlice($xml, $run1['start'], $run2['end']);

      $match = preg_match($regex, $runsSlice, $matches);
      if (FALSE === $match) {
        throw new \RuntimeException(preg_last_error_msg());
      }

      if (0 === $match) {
        $run1 = $run2;
        continue;
      }

      $t1Content = $matches['t1_content'];
      $t2Content = $matches['t2_content'];

      if ($matches['t1_tag'] === $matches['t2_tag']) {
        $tTag = $matches['t1_tag'];
      }
      else {
        $t1Content = self::toPreserveSpace($matches['t1_tag'], $t1Content);
        $t2Content = self::toPreserveSpace($matches['t2_tag'], $t2Content);
        $tTag = '<w:t xml:space="preserve">';
      }

      $runsCombined = "<w:r>{$matches['not_t']}$tTag$t1Content$t2Content</w:t>{$matches['remaining']}";

      $xml = XmlUtil::getSlice($xml, 0, $run1['start']) . $runsCombined . XmlUtil::getSlice($xml, $run2['end']);
      $run1['end'] = $run1['start'] + strlen($runsCombined);
    }

    return $xml;
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
   * Splits a w:p into a list of w:p where each macro is in a separate w:p.
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

  private static function toPreserveSpace(string $tTag, string $tContent): string {
    if ('<w:t xml:space="replace">' === $tTag) {
      // Replace all white space characters with spaces.
      // @phpstan-ignore return.type
      return preg_replace('/\s/', ' ', $tContent);
    }

    if ('<w:t xml:space="preserve">' !== $tTag) {
      // Collapse white space.
      // @phpstan-ignore return.type
      return preg_replace('/\s+/', ' ', $tContent);
    }

    return $tContent;
  }

}
