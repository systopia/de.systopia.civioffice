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
 * CiviOffice Configuration
 */
class CRM_Civioffice_Configuration
{
    protected static $singleton = null;

    /**
     * @return CRM_Civioffice_Configuration
     *  the current configuration
     */
    public static function getConfig()
    {
        if (self::$singleton === null) {
            self::$singleton = new CRM_Civioffice_Configuration();
        }
        return self::$singleton;
    }

    /**
     * Get a list of eligible activity types
     *
     * @return array
     */
    public static function getActivityTypes()
    {
        $types = ['' => E::ts("- none -")];
        $type_query = civicrm_api3('OptionValue', 'get', [
            'option_group_id' => 'activity_type',
            // TODO: Any reason for why to exclude reserved activity types?
            'is_reserved' => 0,
            'option.limit' => 0,
            'return' => 'value,label',
        ]);
        foreach ($type_query['values'] as $type) {
            $types[$type['value']] = $type['label'];
        }
        return $types;
    }


    /**
     * Get the list of active/all document stores
     *
     * @param boolean $only_show_active
     *   return only active/all objects
     *
     * @return \CRM_Civioffice_DocumentStore[]
     */
    public static function getDocumentStores(bool $only_show_active) : array
    {
        // todo: get from config
        $available_document_stores = [
            new CRM_Civioffice_DocumentStore_Local('local_folder', "Local Folder", false, true),
            new CRM_Civioffice_DocumentStore_Upload(true),
            new CRM_Civioffice_DocumentStore_Upload(false),
        ];

        if (!$only_show_active) return $available_document_stores;

        $active_document_stores = [];
        foreach ($available_document_stores as $ds) {
            /** @var $ds CRM_Civioffice_DocumentStore */
            if ($ds->isReady()) {
                $active_document_stores[] = $ds;
            }
        }

        return $active_document_stores;
    }


    /**
     * Get the list of active document stores
     *
     * @param boolean $only_show_active
     *   Whether to return only active objects.
     *
     * @return \CRM_Civioffice_DocumentRenderer[]
     *   An array of document renderers.
     *
     * @throws \Exception
     *   When a renderer could not be loaded from configuration.
     */
    public static function getDocumentRenderers(bool $only_show_active = false): array
    {
        /* @var \CRM_Civioffice_DocumentRenderer[] $renderers */
        $renderers = array_map(
            function ($uri) {
                return CRM_Civioffice_DocumentRenderer::load($uri);
            },
            array_keys(Civi::settings()->get('civioffice_renderers')) ?? []
        );

        if ($only_show_active) {
            $renderers = array_filter($renderers, function ($renderer) {
                return $renderer->isReady();
            });
        }

        return $renderers;
    }

    public static function getDocumentRendererTypes()
    {
        // TODO: Implement using an event.
        $types = [];
        foreach (
            [
                new CRM_Civioffice_DocumentRendererType_LocalUnoconv(),
                new CRM_Civioffice_DocumentRendererType_LocalUnoconvPhpWord(),
            ] as $type
        ) {
            /* @var \CRM_Civioffice_DocumentRendererType $type */
            $types[$type->getURI()] = [
                'label' => $type->getName(),
                'class' => get_class($type),
            ];
        }
        return $types;
    }

    /**
     * Get the list of active document stores
     *
     * @param boolean $active_only
     *   return only active objects
     *
     * @return array
     */
    public static function getEditors($active_only = true) : array
    {
        // todo: get from config
        // todo: filter for $only_show_active
        return [];
    }

    /**
     * Find/get the document renderer with the given URI
     *
     * @param string $document_renderer_uri
     *   document renderer URI
     *
     * @return CRM_Civioffice_DocumentRenderer|null
     */
    public function getDocumentRenderer(string $document_renderer_uri)
    {
        $document_renderers = self::getDocumentRenderers(false);
        foreach ($document_renderers as $dr) {
            if ($document_renderer_uri == $dr->getURI()) {
                return $dr;
            }
        }
        return null; // not found
    }

    public function getDocuments($select2 = false)
    {
        $document_list = [];
        // todo: only show supported source mime types
        foreach ($this->getDocumentStores(true) as $document_store) {
            foreach ($document_store->getDocuments() as $document) {  // todo: recursive
                // TODO: Mimetype checks could be handled differently in the future: https://github.com/systopia/de.systopia.civioffice/issues/2
                if (!CRM_Civioffice_MimeType::hasSpecificFileNameExtension(
                    $document->getName(),
                    CRM_Civioffice_MimeType::DOCX
                )) {
                    continue; // for now only allow/return docx files
                }

                $document_list[$document_store->getURI()][$document->getURI()] = $document;
            }
        }

        if ($select2) {
            $select2_options = array_map(function ($documents, $document_store_uri) {
                return [
                    'text' => self::getDocumentStore($document_store_uri)->getName(),
                    'children' => array_values(
                        array_map(function ($document) {
                            return [
                                'id' => $document->getUri(),
                                'text' => $document->getName(),
                            ];
                        }, $documents)
                    ),
                ];
            }, $document_list, array_keys($document_list));
            $document_list = $select2_options;
        }

        return $document_list;
    }

    /**
     * Get the document with the given URI
     *
     * @param string $document_uri
     *   document URI
     *
     * @return CRM_Civioffice_Document|null
     */
    public function getDocument(string $document_uri): ?CRM_Civioffice_Document
    {
        $stores = self::getDocumentStores(false);
        foreach ($stores as $store) {
            // see if this one has the file
            /** @var  $store CRM_Civioffice_DocumentStore*/
            $document = $store->getDocumentByURI($document_uri);
            if ($document) {
                return $document;
            }
        }
        return null; // not found
    }

    /**
     * Get the document store with the given URI
     *
     * @param string $document_store_uri
     *   document store URI
     *
     * @return \CRM_Civioffice_DocumentStore|null
     */
    public static function getDocumentStore(string $document_store_uri): ?CRM_Civioffice_DocumentStore
    {
        // check for tmp store first
        $tmp_store = CRM_Civioffice_DocumentStore_LocalTemp::getByURI($document_store_uri);
        if ($tmp_store) {
            return $tmp_store;
        }

        // then: check other stores
        $other_stores = self::getDocumentStores(false);
        /** @var CRM_Civioffice_DocumentStore $store */
        foreach ($other_stores as $store) {
            if ($store->isStoreURI($document_store_uri)) {
                return $store;
            }
        }
        return null; // not found
    }


    /**
     * Get the home folder of the current user (usually webserver)
     *
     * @return string
     */
    public static function getHomeFolder(): string
    {
        // try environment
        if (!empty($_SERVER['HOME'])) {
            return $_SERVER['HOME'];
        }

        // Get process user's home directory.
        $user_info = posix_getpwuid(posix_getuid());
        if (!empty($user_info['dir'])) {
            return $user_info['dir'];
        }

        // todo: what else to check?
        Civi::log()->warning("CiviOffice: Couldn't determine web user's home folder.");
        return '~';
    }
}
