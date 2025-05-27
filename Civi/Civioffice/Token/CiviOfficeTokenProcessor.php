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

namespace Civi\Civioffice\Token;

use Civi\Civioffice\PhpWord\PhpWordTokenReplacer;
use Civi\Core\CiviEventDispatcherInterface;
use Civi\Core\Event\GenericHookEvent;
use Civi\Token\TokenProcessor;
use Civi\Token\TokenRow;

final class CiviOfficeTokenProcessor implements CiviOfficeTokenProcessorInterface {

  private CiviEventDispatcherInterface $eventDispatcher;

  private PhpWordTokenReplacer $phpWordTokenReplacer;

  public function __construct(
    CiviEventDispatcherInterface $eventDispatcher,
    PhpWordTokenReplacer $phpWordTokenReplacer
  ) {
    $this->eventDispatcher = $eventDispatcher;
    $this->phpWordTokenReplacer = $phpWordTokenReplacer;
  }

  public function replaceTokens(
    string $inputFile,
    string $outputFile,
    string $entityName,
    int $entityId,
    array $liveSnippets = [],
  ): void {
    $tokenProcessor = $this->createTokenProcessor($entityName, $entityId);
    $tokenRow = $tokenProcessor->addRow()->format('text/html');
    $this->processLiveSnippets($tokenProcessor, $liveSnippets, $tokenRow);
    $this->phpWordTokenReplacer->replaceTokens($inputFile, $outputFile, $tokenProcessor, $tokenRow);
  }

  /**
   * Adds implicit token contexts, builds the corresponding TokenProcessor context schema for the token processor, and
   * adds a token row.
   *
   * @phpstan-param array<string, string> $liveSnippets
   */
  private function processLiveSnippets(TokenProcessor $tokenProcessor, array $liveSnippets, TokenRow $tokenRow): void {
    // Replace tokens in Live Snippets and update token contexts.
    foreach ($liveSnippets as $liveSnippetName => $liveSnippet) {
      $tokenProcessor->addMessage($liveSnippetName, $liveSnippet, 'text/html');
    }
    $tokenProcessor->evaluate();
    // @todo Consider rendering live snippets only when actually used, i.e. in token subscriber.
    foreach ($liveSnippets as $liveSnippetName => &$liveSnippet) {
      $liveSnippet = $tokenProcessor->render($liveSnippetName, $tokenRow);
    }
    $tokenRow->context('civioffice.live_snippets', $liveSnippets);
  }

  private function createTokenProcessor(string $entityName, int $entityId): TokenProcessor {
    $context = [];

    // Let the token contexts be defined, for the given entity or generically.
    $this->eventDispatcher->dispatch(
      'civi.civioffice.tokenContext',
      GenericHookEvent::create([
        'context' => &$context,
        'entity_type' => $entityName,
        'entity_id' => $entityId,
      ])
    );

    return new TokenProcessor(
      // @phpstan-ignore argument.type
      $this->eventDispatcher,
      [
        'controller' => __CLASS__,
        'smarty' => FALSE,
      ] + $context
    );
  }

}
