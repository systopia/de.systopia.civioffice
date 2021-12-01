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

    public static function get() {
        if (!isset(self::$_liveSnippets)) {
            $option_group_id = civicrm_api3('OptionGroup', 'getvalue', ['name' => 'civioffice_live_snippets', 'return' => 'id']);
            self::$_liveSnippets = civicrm_api3(
                'OptionValue',
                'get',
                [
                    'option_group_id' => $option_group_id
                ]
            )['values'];
        }
        return self::$_liveSnippets;
    }

    public static function getValues() {
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

    public static function getTokens() {
        if (!isset(self::$_liveSnippetTokens)) {
            self::$_liveSnippetTokens = [];
            foreach (self::get() as $live_snippet) {
                self::$_liveSnippetTokens['live_snippets.' . $live_snippet['name']] = $live_snippet['label'];
            }
        }
        return self::$_liveSnippetTokens;
    }
}
