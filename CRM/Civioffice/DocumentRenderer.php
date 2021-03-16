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
    public abstract function getOutputMimeTypes(): array;

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
     *
     * @return array
     *   list of token_name => token value
     */
    public abstract function render(
        $document_with_placeholders,
        array $entity_ids,
        CRM_Civioffice_DocumentStore_LocalTemp $temp_store,
        string $target_mime_type,
        $entity_type = 'contact'
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
     *
     * @throws \Exception
     */
    public function replaceAllTokens($string, $entity_id, $entity_type = 'contact'): string
    {
        // TODO: use additional token system
        $additional_tokens = [];

        if ($entity_type == 'contact') {
            // first: replace CiviCRM style contact tokens
            [$contact] = CRM_Utils_Token::getTokenDetails([$entity_id]); //fixme returns empty array?
            $string = CRM_Utils_Token::replaceContactTokens(
                $string,
                $contact[$entity_id],
                false,
                $additional_tokens
            );

            // second: replace SMARTY style tokens
            /* @var CRM_Core_Smarty $smarty */
            $smarty = CRM_Core_Smarty::singleton();
            foreach ($additional_tokens as $key => $value) {
                $smarty->assign($key, $value);
            }
            $string = $smarty->fetch("string:$string");

            return $string;
        }

        // todo: implement?
        throw new Exception('replaceAllTokens not implemented for entity ' . $entity_type);
    }

    /*
     * Could be used to convert larger batches of strings and/or contact ids
     */
    public function multipleReplaceAllTokens()
    {
    }
}
