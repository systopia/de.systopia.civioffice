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
    protected TokenProcessor $tokenProcessor;

    protected array $liveSnippets = [];

    protected static $tokenContext;

    public function __construct($uri = null, $name = null, array &$configuration = []) {
        parent::__construct($uri, $name);
        foreach (static::supportedConfiguration() as $config_item) {
            $this->{$config_item} = $configuration[$config_item] ?? null;
        }

        $this->tokenProcessor = new TokenProcessor(
            Civi::service('dispatcher'),
            [
                'controller' => __CLASS__,
                'smarty' => false,
            ]
        );
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
     * Adds implicit token contexts, builds the corresponding TokenProcessor context schema for the token processor, and
     * adds a token row.
     *
     * @param string $entity_type
     *   The CiviCRM entity type to process token context for.
     *
     * @param int $entity_id
     *   The CiviCRM entity ID to process token context for.
     *
     * @return \Civi\Token\TokenRow
     *   The token processor row with the processed token context.
     *
     * @throws \CRM_Core_Exception
     * @throws \Civi\API\Exception\UnauthorizedException
     */
    public function processTokenContexts(string $entity_type, int $entity_id): \Civi\Token\TokenRow
    {
        $token_contexts = [
            $entity_type => ['entity_id' => $entity_id],
        ];

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
        $entity_token_context = static::entityTokenContext();
        foreach ($token_contexts as $entity_type => $context) {
            if (!isset($entity_token_context[$entity_type])) {
                throw new Exception(
                    E::ts('Could not determine token context for entity type %1.', [1 => $entity_type])
                );
            }
            $token_contexts_schema[$entity_token_context[$entity_type]] = $context['entity_id'];
        }

        $this->tokenProcessor->addSchema(array_keys($token_contexts_schema));
        $token_row = $this->tokenProcessor->addRow($token_contexts_schema)
            ->format('text/html');

        // Replace tokens in Live Snippets and update token contexts.
        $this->replaceLiveSnippetTokens($token_row);
        $token_contexts_schema['civioffice.live_snippets'] = $this->liveSnippets;
        $token_row->context($token_contexts_schema);

        return $token_row;
    }

    /**
     * Builds a mapping of entity type names and their corresponding token context schema identifiers.
     *
     * @return string[]
     */
    public static function entityTokenContext(): array {
        if (!isset(static::$tokenContext)) {
            // Define token contexts for entity types natively supported by CiviOffice.
            static::$tokenContext = [
                'contact' => 'contactId',
                'contribution' => 'contributionId',
                'participant' => 'participantId',
                'event' => 'eventId',
                'membership' => 'membershipId',
            ];

            // Let other extensions define token contexts for additional entity types.
            $token_context_event = \Civi\Core\Event\GenericHookEvent::create(['context' => &static::$tokenContext]);
            Civi::dispatcher()->dispatch('civi.civioffice.entitytokencontext', $token_context_event);
        }

        return static::$tokenContext;
    }

    abstract public function replaceTokens(CRM_Civioffice_Document $document, string $entity_type, int $entity_id);

    public function replaceLiveSnippetTokens(\Civi\Token\TokenRow $row) {
        foreach ($this->liveSnippets as $live_snippet_name => $live_snippet) {
            $this->tokenProcessor->addMessage($live_snippet_name, $live_snippet, 'text/html');
        }
        $this->tokenProcessor->evaluate();
        foreach ($this->liveSnippets as $live_snippet_name => &$live_snippet) {
            $live_snippet = $this->tokenProcessor->render($live_snippet_name, $row);
        }
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
