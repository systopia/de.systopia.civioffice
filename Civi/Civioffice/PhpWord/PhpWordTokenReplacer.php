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

  private TokenProcessor $tokenProcessor;

  public function __construct(TokenProcessor $tokenProcessor) {
    $this->tokenProcessor = $tokenProcessor;
  }

  public function replaceTokens(string $inputFile, string $outputFile, TokenRow $tokenRow): void {
    try {
      $templateProcessor = new \CRM_Civioffice_DocumentRendererType_LocalUnoconv_PhpWordTemplateProcessor(
        $inputFile
      );
    }
    // @phpstan-ignore catch.neverThrown
    catch (\PhpOffice\PhpWord\Exception\Exception $e) {
      throw new \RuntimeException('Unoconv: Docx (zip) file seems to be broken or path is wrong', $e->getCode(), $e);
    }

    $usedTokens = $templateProcessor->civiTokensToMacros();

    // Register all tokens as token messages and evaluate.
    foreach ($usedTokens as $token => $token_params) {
      $this->tokenProcessor->addMessage($token, $token, 'text/html');
    }
    $this->tokenProcessor->evaluate();

    // Replace contained tokens.
    $usedMacroVariables = $templateProcessor->getVariables();
    foreach ($usedMacroVariables as $macroVariable) {
      // Format each variable as a CiviCRM token and render it.
      $renderedTokenMessage = $this->tokenProcessor->render('{' . $macroVariable . '}', $tokenRow);
      $templateProcessor->replaceHtmlToken($macroVariable, $renderedTokenMessage);
    }
    $templateProcessor->saveAs($outputFile);
  }

}
