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
    public abstract function getOutputMimeTypes() : array;

    /**
     * Convert the list of documents to the given mime type
     *
     * @param array $documents
     *   list of CRM_Civioffice_Document objects
     *
     * @param string $target_mime_type
     *   mime type to convert to
     *
     * @return array
     *   list of CRM_Civioffice_Document objects
     */
    public abstract function render(array $documents, string $target_mime_type) : array;


    public function resolveTokens($token_names, $entity_id, $entity_type) : array {
        return [];
    }

    public function replaceAllTokens($string, $entity_id, $entity_type) : string {
        return $string;
    }

}
