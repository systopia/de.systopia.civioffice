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
    public function processTokenContext(string $entity_type, int $entity_id): \Civi\Token\TokenRow
    {
        $entities = [
            $entity_type => ['entity_id' => $entity_id],
        ];

        // Add implicit contact token context for contributions.
        if (
            array_key_exists('contribution', $entities)
            && !array_key_exists('contact', $entities)
        ) {
            $contribution = Api4\Contribution::get()
                ->addWhere('id', '=', $entities['contribution']['entity_id'])
                ->execute()
                ->single();
            $entities['contact'] = ['entity_id' => $contribution['contact_id']];
        }

        // Add implicit contact and event token contexts for participants.
        if (array_key_exists('participant', $entities)) {
            $participant = Api4\Participant::get()
                ->addWhere('id', '=', $entities['participant']['entity_id'])
                ->execute()
                ->single();
            if (!array_key_exists('contact', $entities)) {
                $entities['contact'] = ['entity_id' => $participant['contact_id']];
            }
            if (!array_key_exists('event', $entities)) {
                $entities['event'] = ['entity_id' => $participant['event_id']];
            }
            if (!array_key_exists('contribution', $entities)) {
                try {
                    $participant_payment = civicrm_api3(
                        'ParticipantPayment',
                        'getsingle',
                        ['participant_id' => $participant['id']]
                    );
                    $entities['contribution'] = ['entity_id' => $participant_payment['contribution_id']];
                } catch (Exception $exception) {
                    // No participant payment, nothing to do.
                }
            }
        }

        // Add implicit contact token context for memberships.
        if (
            array_key_exists('membership', $entities)
            && !array_key_exists('contact', $entities)
        ) {
            $membership = Api4\Membership::get()
                ->addWhere('id', '=', $entities['membership']['entity_id'])
                ->execute()
                ->single();
            $entities['contact'] = ['entity_id' => $membership['contact_id']];
        }

        // Add implicit contact and case token context for activities.
        if (
            array_key_exists('activity', $entities)
        ) {
            if (!array_key_exists('contact', $entities)) {
                if (
                    $activityContact = Api4\ActivityContact::get()
                        ->addSelect('contact_id')
                        ->addWhere('activity_id', '=', $entities['activity']['entity_id'])
                        ->addWhere('record_type_id:name', '=', 'Activity Source')
                        ->execute()
                        // Use the first record, as there might be more than one.
                        ->first()
                ) {
                    $entities['contact'] = ['entity_id' => $activityContact['contact_id']];
                }
            }
            if (!array_key_exists('case', $entities)) {
                $activity = Api4\Activity::get()
                    ->addSelect('case_id')
                    ->addWhere('id', '=', $entities['activity']['entity_id'])
                    ->addWhere('case_id', 'IS NOT EMPTY')
                    ->execute()
                    ->single();
                $entities['case'] = ['entity_id' => $activity['case_id']];
            }
        }

        // Add implicit contact token context for cases.
        if (
            array_key_exists('case', $entities)
            && !array_key_exists('contact', $entities)
        ) {
            if (
                $caseContact = Api4\CaseContact::get()
                    ->addSelect('contact_id')
                    ->addWhere('case_id', '=', $entities['case']['entity_id'])
                    ->execute()
                    // Use the first record, as there might be more than one.
                    ->first()
            ) {
                $entities['contact'] = ['entity_id' => $caseContact['contact_id']];
            }
        }

        // Translate entity types into token contexts known to CiviOffice.
        $entity_token_contexts = [
            'contact' => 'contactId',
            'contribution' => 'contributionId',
            'participant' => 'participantId',
            'event' => 'eventId',
            'membership' => 'membershipId',
            'activity' => 'activityId',
            'case' => 'caseId',
        ];
        $context = [];
        foreach ($entities as $entity_type => $contextId) {
            if (isset($entity_token_contexts[$entity_type])) {
                $context[$entity_token_contexts[$entity_type]] = $contextId['entity_id'];
            }
        }

        // Let other extensions define additional token contexts, for the given entity or generically.
        Civi::dispatcher()->dispatch(
            'civi.civioffice.tokenContext',
            \Civi\Core\Event\GenericHookEvent::create(
                [
                    'context' => &$context,
                    'entity_type' => $entity_type,
                    'entity_id' => $entity_id,
                ]
            )
        );

        // Reset (re-instantiate) the token processor per document for not evaluating with previous token rows.
        $this->tokenProcessor = static::createTokenProcessor($context);
        $token_row = $this->tokenProcessor->addRow($context)
            ->format('text/html');

        // Replace tokens in Live Snippets and update token contexts.
        $this->replaceLiveSnippetTokens($context);
        $context['civioffice.live_snippets'] = $this->liveSnippets;
        $token_row->context($context);

        return $token_row;
    }

    protected static function createTokenProcessor(array $context)
    {
        $token_processor = new TokenProcessor(
            Civi::service('dispatcher'),
            [
                'controller' => __CLASS__,
                'smarty' => false,
            ]
        );
        $token_processor->addSchema(array_keys($context));
        return $token_processor;
    }

    /**
     * @param \CRM_Civioffice_Document $document
     * @param string $entity_type
     * @param int[] $entity_ids
     *
     * @return void
     */
    abstract public function replaceTokens(CRM_Civioffice_Document $document, string $entity_type, array $entity_ids): void;

    public function replaceLiveSnippetTokens(array $context) {
        // Use a separate token processor for replacing Live Snippet tokens (with the same context).
        $token_processor = static::createTokenProcessor($context);
        $token_row = $token_processor->addRow($context)
            ->format('text/html');
        foreach ($this->liveSnippets as $live_snippet_name => $live_snippet) {
            $token_processor->addMessage($live_snippet_name, $live_snippet, 'text/html');
        }
        $token_processor->evaluate();
        foreach ($this->liveSnippets as $live_snippet_name => &$live_snippet) {
            $live_snippet = $token_processor->render($live_snippet_name, $token_row);
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
