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
abstract class CRM_Civioffice_DocumentRendererType extends CRM_Civioffice_OfficeComponent
{
    public function __construct($uri = null, $name = null, array &$configuration = []) {
        parent::__construct($uri, $name);
        foreach (static::supportedConfiguration() as $config_item) {
            $this->{$config_item} = $configuration[$config_item] ?? null;
        }
    }

    /**
     * @param array $configuration
     *   The configuration for the Document Renderer Type.
     *
     * @return \CRM_Civioffice_DocumentRendererType
     *   The document renderer type object.
     *
     * @throws \Exception
     *   When the given document renderer type does not exist.
     */
    public static function create(string $type, array $configuration = []): CRM_Civioffice_DocumentRendererType
    {
        $types = CRM_Civioffice_Configuration::getDocumentRendererTypes();
        if (!isset($types[$type]) || !class_exists($types[$type]['class'])) {
            throw new Exception('Document renderer type %s does not exist.', $type);
        }
        return new $types[$type]['class'](null, null, $configuration);
    }

    abstract public function buildSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form);

    abstract public function validateSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form);

    abstract public function postProcessSettingsForm(CRM_Civioffice_Form_DocumentRenderer_Settings $form);

    public static function getNextUri() {
        return (new static())->getURI() . '-' . count(CRM_Civioffice_Configuration::getDocumentRenderers());
    }

    /**
     * Get a list of document mime types supported by this component
     *
     * @return array
     *   list of mime types as strings
     */
    abstract public function getSupportedMimeTypes() : array;

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
        $token_contexts_schema = static::processTokenContexts($token_contexts);
        $identifier = 'document';
        $processor = new TokenProcessor(
            Civi::service('dispatcher'),
            [
                'controller' => __CLASS__,
                'smarty' => false,
                'schema' => array_keys($token_contexts_schema),
            ]
        );
        $processor->addMessage($identifier, $string, 'text/plain');
        $token_row = $processor->addRow($token_contexts_schema);
        $processor->evaluate();
        return $token_row->render($identifier);
    }

    /**
     * Adds implicit token contexts and builds the corresponding TokenProcessor context schema.
     *
     * @param array $token_contexts
     *   An array of token contexts, with entity types as keys and an array with context as values, e. g.
     *   [
     *     'contribution' => ['entity_id' => 123],
     *   ]
     *   This will be extended with the corresponding contact, and anything else that can be implicitly derived from the
     *   entity given.
     *
     * @return array
     *   The TokenProcessor context schema, including all explicit and implicit token contexts.
     *
     * @throws \CRM_Core_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public static function processTokenContexts(array &$token_contexts): array
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
            if (!array_key_exists('contribution', $token_contexts)) {
                try {
                    $participant_payment = civicrm_api3(
                        'ParticipantPayment',
                        'getsingle',
                        ['participant_id' => $participant['id']]
                    );
                    $token_contexts['contribution'] = ['entity_id' => $participant_payment['contribution_id']];
                } catch (Exception $exception) {
                    // No participant payment, nothing to do.
                }
            }
        }

        // Add implicit contact token context for memberships.
        if (
            array_key_exists('membership', $token_contexts)
            && !array_key_exists('contact', $token_contexts)
        ) {
            $membership = Api4\Membership::get()
                ->addWhere('id', '=', $token_contexts['membership']['entity_id'])
                ->execute()
                ->single();
            $token_contexts['contact'] = ['entity_id' => $membership['contact_id']];
        }

        // Translate entity types into token contexts.
        $token_contexts_schema = [];
        foreach ($token_contexts as $entity_type => $context) {
            switch ($entity_type) {
                case 'civioffice':
                    $token_contexts_schema['civioffice.live_snippets'] = $context['live_snippets'];
                    break;
                case 'contact':
                    $token_contexts_schema['contactId'] = $context['entity_id'];
                    break;
                case 'contribution':
                    $token_contexts_schema['contributionId'] = $context['entity_id'];
                    break;
                case 'participant':
                    $token_contexts_schema['participantId'] = $context['entity_id'];
                    break;
                case 'event':
                    $token_contexts_schema['eventId'] = $context['entity_id'];
                    break;
                case 'membership':
                    $token_contexts_schema['membershipId'] = $context['entity_id'];
                    break;
                default:
                    // TODO: Implement for token contexts from external token providers.
                    throw new Exception('replaceAllTokens not implemented for entity ' . $entity_type);
                    break;
            }
        }
        return $token_contexts_schema;
    }

    /*
     * Could be used to convert larger batches of strings and/or contact ids
     */
    public function multipleReplaceAllTokens()
    {
    }

    abstract public static function supportedConfiguration(): array;

    abstract public static function defaultConfiguration(): array;

    public static function supportsConfigurationItem($configurationItem): bool
    {
        return in_array($configurationItem, static::supportedConfiguration());
    }

    /**
     * @throws \Exception
     *   When the renderer type does not support a configuration item with the given name.
     */
    public function checkConfigurationSupported($configurationItem) {
        if (!$this->supportsConfigurationItem($configurationItem)) {
            throw new Exception(
                'Document renderer type %s does not support configuration item %s',
                $this->getName(),
                $configurationItem
            );
        }
    }
}
