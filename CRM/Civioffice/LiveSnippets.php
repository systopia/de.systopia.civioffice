<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
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

class CRM_Civioffice_LiveSnippets
{
    public static $_liveSnippets;

    public static $_liveSnippetValues;

    public static $_liveSnippetTokens;

    public static function get()
    {
        if (!isset(self::$_liveSnippets)) {
            try {
                $option_group_id = civicrm_api3(
                    'OptionGroup',
                    'getvalue',
                    [
                        'name' => 'civioffice_live_snippets',
                        'return' => 'id',
                    ]
                );
                self::$_liveSnippets = civicrm_api3(
                    'OptionValue',
                    'get',
                    [
                        'option_group_id' => $option_group_id,
                    ]
                )['values'];
            } catch (Exception $exception) {
                // The option groupo doesn't seem to exist. This can only be the case when a DB upgrade after updating
                // is pending, so do nothing here.
                self::$_liveSnippets = [];
            }
        }
        return self::$_liveSnippets;
    }

    public static function getValues()
    {
        if (!isset(self::$_liveSnippetValues)) {
            self::$_liveSnippetValues = [];
            foreach (self::get() as $live_snippet) {
                self::$_liveSnippetValues[$live_snippet['name']] = Civi::contactSettings()->get(
                    'civioffice.live_snippets.' . $live_snippet['name']
                );
            }
        }
        return self::$_liveSnippetValues;
    }

    public static function getValue($name)
    {
        self::getValues();
        return self::$_liveSnippetValues[$name];
    }

    public static function setValue($name, $value, $store = false)
    {
        self::$_liveSnippetValues[$name] = $value;
        if ($store) {
            self::storeValue($name, $value);
        }
    }

    public static function storeValue($name, $value)
    {
        Civi::contactSettings()->set('civioffice.live_snippets.' . $name, $value);
    }

    public static function storeValues()
    {
        foreach (self::$_liveSnippetValues as $name => $value) {
            self::storeValue($name, $value);
        }
    }

    public static function getTokens()
    {
        if (!isset(self::$_liveSnippetTokens)) {
            self::$_liveSnippetTokens = [];
            foreach (self::get() as $live_snippet) {
                self::$_liveSnippetTokens['live_snippets.' . $live_snippet['name']] = $live_snippet['label'];
            }
        }
        return self::$_liveSnippetTokens;
    }

    /**
     * @param CRM_Core_Form $form
     * @param $defaults
     */
    public static function addFormElements(&$form, $name_prefix = '', $defaults = []) {
        $live_snippet_elements = [];
        $live_snippet_descriptions = [];
        $live_snippet_values = CRM_Civioffice_LiveSnippets::getValues();
        $default_values = [];
        $element_names = [];
        foreach (CRM_Civioffice_LiveSnippets::get() as $live_snippet) {
            $element_name = $name_prefix . 'live_snippets_' . $live_snippet['name'];
            $form->add(
                'textarea',
                $element_name,
                $live_snippet['label']
            );
            $element_names[] = $element_name;
            $default_values[$element_name] = $defaults[$live_snippet['name']] ?? $live_snippet_values[$live_snippet['name']];
            $live_snippet_elements[$live_snippet['name']] = 'live_snippets_' . $live_snippet['name'];
            $live_snippet_descriptions[$live_snippet['name']] = $live_snippet['description'];
        }
        $form->assign('live_snippet_elements', $live_snippet_elements);
        $form->assign('live_snippet_descriptions', $live_snippet_descriptions);
        $form->setDefaults($default_values);
        return $element_names;
    }

    /**
     * @param CRM_Core_Form $form
     * @param bool $store_defaults
     *
     * @return array
     */
    public static function getFormElementValues(&$form, $store_defaults = TRUE, $element_name_prefix = '') {
        $live_snippets = self::get();
        $live_snippet_values = [];
        foreach ($live_snippets as $live_snippet) {
            $element_name = $element_name_prefix . 'live_snippets_' . $live_snippet['name'];
            if ($element = $form->getElement($element_name)) {
                // Fake an element name that is in CRM_Utils_API_HTMLInputCoder->skipFields for not encoding its content
                // as HTML-safe, since we're inside an XML CDATA section.
                $form->_elementIndex['content'] = $form->_elementIndex[$element_name];
                $value = $form->exportValue('content');
                // Filter CDATA ending sections ("]]>") for not breaking the XML document.
                self::setValue(
                    $live_snippet['name'],
                    $value,
                    $store_defaults
                );
                $value = str_replace(']]>', ']]]]><![CDATA[>', $value);
                $live_snippet_values[$live_snippet['name']] = $value;
            }
        }
        if (isset($form->_elementIndex['content'])) {
            unset ($form->_elementIndex['content']);
        }
        return $live_snippet_values;
    }
}
