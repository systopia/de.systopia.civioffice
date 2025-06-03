<?php
/*
 * Copyright (C) 2025 SYSTOPIA GmbH
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

use Civi\Token\TokenProcessor;
use Civi\Token\TokenRow;

final class PhpWordTokenReplacer {

  public function replaceTokens(
    string $inputFile,
    string $outputFile,
    TokenProcessor $tokenProcessor,
    TokenRow $tokenRow
  ): void {
    try {
      $templateProcessor = new PhpWordTemplateProcessor($inputFile);
    }
    // @phpstan-ignore catch.neverThrown
    catch (\PhpOffice\PhpWord\Exception\Exception $e) {
      throw new \RuntimeException('DOCX file seems to be broken or path is wrong', $e->getCode(), $e);
    }

    $usedTokens = $templateProcessor->civiTokensToMacros();

    // Register all tokens as token messages and evaluate.
    foreach ($usedTokens as $token => $tokenParams) {
      $tokenProcessor->addMessage($token, $token, 'text/html');
    }
    $tokenProcessor->evaluate();

    // Replace contained tokens.
    $usedMacroVariables = $templateProcessor->getVariables();
    foreach ($usedMacroVariables as $macroVariable) {
      // Format each variable as a CiviCRM token and render it.
      $renderedTokenMessage = $tokenProcessor->render('{' . $macroVariable . '}', $tokenRow);
      $templateProcessor->replaceHtmlToken($macroVariable, $renderedTokenMessage);
    }
    $templateProcessor->saveAs($outputFile);
  }

}
