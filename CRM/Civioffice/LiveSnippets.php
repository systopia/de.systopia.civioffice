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

declare(strict_types = 1);

class CRM_Civioffice_LiveSnippets {

  private static array $liveSnippets;

  private static array $liveSnippetValues;

  private static array $liveSnippetTokens;

  public static function get(string|null $index = NULL): array {
    if (!isset(self::$liveSnippets)) {
      try {
        $option_group_id = civicrm_api3(
        'OptionGroup',
        'getvalue',
          [
            'name' => 'civioffice_live_snippets',
            'return' => 'id',
          ]
        );
        self::$liveSnippets = civicrm_api3(
        'OptionValue',
        'get',
          [
            'option_group_id' => $option_group_id,
          ]
        )['values'];

        foreach (self::$liveSnippets as &$lifeSnippet) {
          // APIv3 doesn't return empty description.
          $lifeSnippet['description'] ??= NULL;
        }
      }
      catch (Exception $e) {
        // The option group doesn't seem to exist. This can only be the case when a DB upgrade after updating
        // is pending, so do nothing here.
        self::$liveSnippets = [];
      }
    }
    return $index
      ? array_combine(array_column(self::$liveSnippets, $index), self::$liveSnippets)
      : self::$liveSnippets;
  }

  public static function getValues(): array {
    if (!isset(self::$liveSnippetValues)) {
      self::$liveSnippetValues = [];
      foreach (self::get() as $live_snippet) {
        self::$liveSnippetValues[$live_snippet['name']] = Civi::contactSettings()->get(
        'civioffice.live_snippets.' . $live_snippet['name']
        );
      }
    }
    return self::$liveSnippetValues;
  }

  public static function getValue(string $name) {
    self::getValues();
    return self::$liveSnippetValues[$name];
  }

  public static function setValue(string $name, $value, bool $store = FALSE): void {
    self::$liveSnippetValues[$name] = $value;
    if ($store) {
      self::storeValue($name, $value);
    }
  }

  public static function storeValue(string $name, $value): void {
    Civi::contactSettings()->set('civioffice.live_snippets.' . $name, $value);
  }

  public static function storeValues(): void {
    foreach (self::$liveSnippetValues as $name => $value) {
      self::storeValue($name, $value);
    }
  }

  public static function getTokens(): array {
    if (!isset(self::$liveSnippetTokens)) {
      self::$liveSnippetTokens = [];
      foreach (self::get() as $live_snippet) {
        self::$liveSnippetTokens['live_snippets.' . $live_snippet['name']] = $live_snippet['label'];
      }
    }

    return self::$liveSnippetTokens;
  }

  public static function addFormElements(CRM_Core_Form $form, string $name_prefix = '', array $defaults = []): array {
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
      $live_snippet_descriptions[$live_snippet['name']] = $live_snippet['description'] ?? '';
    }
    $form->assign('live_snippet_elements', $live_snippet_elements);
    $form->assign('live_snippet_descriptions', $live_snippet_descriptions);
    $form->setDefaults($default_values);
    return $element_names;
  }

  public static function getFormElementValues(
    CRM_Core_Form $form,
    bool $store_defaults = TRUE,
    string $element_name_prefix = ''
  ): array {
    $live_snippets = self::get();
    $live_snippet_values = [];
    foreach ($live_snippets as $live_snippet) {
      $element_name = $element_name_prefix . 'live_snippets_' . $live_snippet['name'];
      if ($form->getElement($element_name)) {
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
      unset($form->_elementIndex['content']);
    }
    return $live_snippet_values;
  }

}
