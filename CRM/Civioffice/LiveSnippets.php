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

    public static function getFormElements(&$form, &$defaults) {
        $live_snippet_elements = [];
        $live_snippet_values = CRM_Civioffice_LiveSnippets::getValues();
        foreach (CRM_Civioffice_LiveSnippets::get() as $live_snippet) {
            $form->add(
                'textarea',
                'live_snippets_' . $live_snippet['name'],
                $live_snippet['label']
            /**
             * Do not add attributes, as otherwise HTML content is being encoded during processing of value.
             * @see \HTML_QuickForm::exportValues()
             * @url https://github.com/civicrm/civicrm-packages/commit/311a4f85180e144774e2f3aa2b163af4a79c99fa
             */
            );
            $defaults['live_snippets_' . $live_snippet['name']] = $live_snippet_values[$live_snippet['name']];
            $live_snippet_elements[] = 'live_snippets_' . $live_snippet['name'];
        }
        $form->assign('live_snippet_elements', $live_snippet_elements);
    }

    public static function getFormElementValues($values, $store_defaults = TRUE) {
        // Set live snippet setting values.
        $live_snippets = [];
        foreach (self::get() as $live_snippet) {
            $live_snippets[$live_snippet['name']] = $values['live_snippets_' . $live_snippet['name']];
            self::setValue(
                $live_snippet['name'],
                $values['live_snippets_' . $live_snippet['name']],
                true
            );
        }
        return $live_snippets;
    }
}