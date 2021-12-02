{*-------------------------------------------------------+
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
+-------------------------------------------------------*}

{* HEADER *}

{crmScope extensionKey='de.systopia.civioffice'}
  <div class="crm-block crm-form-block">

    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>

    <div class="crm-section">
      <div class="label">{$form.document_uri.label}</div>
      <div class="content">{$form.document_uri.html}</div>
      <div class="clear"></div>
    </div>

    <div class="crm-section">
      <div class="label">{$form.document_renderer_uri.label}</div>
      <div class="content">{$form.document_renderer_uri.html}</div>
      <div class="clear"></div>
    </div>

    <div class="crm-section">
      <div class="label">{$form.target_mime_type.label}</div>
      <div class="content">{$form.target_mime_type.html}</div>
      <div class="clear"></div>
    </div>

    <div class="crm-section">
      <div class="label">{$form.batch_size.label}</div>
      <div class="content">{$form.batch_size.html}</div>
      <div class="clear"></div>
    </div>

      {if !empty($live_snippet_elements)}
        <div class="crm-accordion-wrapper">
          <div class="crm-accordion-header">{ts}Live Snippets{/ts}</div>
          <div class="crm-accordion-body">
              {foreach from=$live_snippet_elements item="live_snippet_element"}
                <div class="crm-section">
                  <div class="label">{$form.$live_snippet_element.label}</div>
                  <div class="content">{$form.$live_snippet_element.html}</div>
                  <div class="clear"></div>
                </div>
              {/foreach}
          </div>
        </div>
      {/if}

    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>

  </div>
{/crmScope}