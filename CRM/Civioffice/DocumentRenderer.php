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

use Civi\Token\TokenProcessor;
use Civi\Api4;
use CRM_Civioffice_ExtensionUtil as E;

/**
 * CiviOffice Document Renderer
 */
abstract class CRM_Civioffice_DocumentRenderer extends CRM_Civioffice_OfficeComponent
{
    /**
     * Get the output/generated mime types for this document renderer
     *
     * @return array
     *   list of mime types
     */
    public abstract function getSupportedOutputMimeTypes(): array;

    /**
     * Render a document for a list of entities
     *
     * @param $document_with_placeholders
     * @param array $entity_ids
     *   entity ID, e.g. contact_id
     * @param CRM_Civioffice_DocumentStore_LocalTemp $temp_store
     * @param string $target_mime_type
     * @param string $entity_type
     *   entity type, e.g. 'contact'
     * @param array $live_snippets
     *   Values for Live Snippet tokens in the document.
     *
     * @return array
     *   list of token_name => token value
     */
    public abstract function render(
        $document_with_placeholders,
        array $entity_ids,
        CRM_Civioffice_DocumentStore_LocalTemp $temp_store,
        string $target_mime_type,
        string $entity_type = 'contact',
        array $live_snippets = []
    ): array;

    /**
     * resolve all tokens
     *
     * @param array $token_names
     *   the list of all token names to be replaced
     *
     * @param integer $entity_id
     *   entity ID, e.g. contact_id
     *
     * @param string $entity_type
     *   entity type, e.g. 'contact'
     *
     * @return array
     *   list of token_name => token value
     */
    public function resolveTokens($token_names, $entity_id, $entity_type = 'contact'): array
    {
        // TODO: implement
        // TODO: use additional token system
        throw new Exception('resolveTokens not implemented');
    }

    /**
     * Replace all tokens with {token_name} and {$smarty_var.attribute} format
     *
     * @param $string
     *   A string including tokens to be replaced.
     * @param string $entity_type
     *   The entity type, e.g. 'contact'. The entity type "civioffice" refers to tokens defined by CiviOffice itself.
     * @param array $token_contexts
     *   A list of token contexts, keyed by token entity (e.g. "contact" or "civioffice").
     *
     * @return string
     *   The input string with the tokens replaced.
     *
     * @throws \Exception
     *   When replacing tokens fails or replacing tokens for the given entity is not implemented.
     */
    public function replaceAllTokens($string, $token_contexts = []): string
    {
        // Add implicit contact token context for contributions.
        if (
            array_key_exists('contribution', $token_contexts)
            && !array_key_exists('contact', $token_contexts)
        ) {
            $contribution = Api4\Contribution::get()
                ->addWhere('id', '=', $token_contexts['contribution']['entity_id'])
                ->execute()
                ->single();
            $token_contexts['contact'] = ['entity_id' => $contribution['contact_id']];
        }

        // Add implicit contact and event token contexts for participants.
        if (array_key_exists('participant', $token_contexts)) {
            $participant = Api4\Participant::get()
                ->addWhere('id', '=', $token_contexts['participant']['entity_id'])
                ->execute()
                ->single();
            if (!array_key_exists('contact', $token_contexts)) {
                $token_contexts['contact'] = ['entity_id' => $participant['contact_id']];
            }
            if (!array_key_exists('event', $token_contexts)) {
                $token_contexts['event'] = ['entity_id' => $participant['event_id']];
            }
        }

        $identifier = 'document';
        $processor = new TokenProcessor(
            Civi::service('dispatcher'),
            array_keys($token_contexts) + [
                'controller' => __CLASS__,
                'smarty' => false,
            ]
        );
        $processor->addMessage($identifier, $string, 'text/plain');
        $token_row = $processor->addRow();

        foreach ($token_contexts as $entity_type => $context) {
            switch ($entity_type) {
                case 'civioffice':
                    $token_row->context('civioffice.live_snippets', $context['live_snippets']);
                    break;
                case 'contact':
                    $token_row->context('contactId', $context['entity_id']);

                    /*
                     * FIXME: Unfortunately we get &lt; and &gt; from civi backend so we need to decode them back to < and > with htmlspecialchars_decode()
                     * This is postponed as it might be risky as it either breaks xml or leads to further problems
                     * https://github.com/systopia/de.systopia.civioffice/issues/3
                     */

                    break;
                case 'contribution':
                    $token_row->context('contributionId', $context['entity_id']);
                    $token_row->context('contribution', $context['entity']);
                    break;
                case 'participant':
                    $token_row->context('participantId', $context['entity_id']);
                    $token_row->context('participant', $context['entity']);
                    break;
                case 'event':
                    $token_row->context('eventId', $context['entity_id']);
                    $token_row->context('event', $context['entity']);
                    break;
                default:
                    // todo: implement?
                    throw new Exception('replaceAllTokens not implemented for entity ' . $entity_type);
                    break;
            }
        }

        $processor->evaluate();
        return $token_row->render($identifier);
    }

    /*
     * Could be used to convert larger batches of strings and/or contact ids
     */
    public function multipleReplaceAllTokens()
    {
    }
}
