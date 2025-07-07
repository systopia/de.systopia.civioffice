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

{crmScope extensionKey='de.systopia.civioffice'}

  <div class="crm-block crm-content-block">

    <h3>{ts}CiviOffice Document Stores{/ts}</h3>
    <div id="help">{ts}Document Stores are used to store files{/ts}</div>

    <table class="row-highlight">
      <thead>
      <tr>
        <th>{ts}Name{/ts}</th>
        <th>{ts}Description{/ts}</th>
        <th>{ts}Ready to use{/ts}</th>
        <th>{ts}Config{/ts}</th>
      </tr>
      </thead>
      <tbody>
      {if !empty($ui_components.document_stores)}
          {foreach from=$ui_components.document_stores item=component}
            <tr class="{if not $component.is_ready}disabled{/if}">

              <td>{$component.name}</td>
              <td>{$component.description}</td>
              <td>{if $component.is_ready}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
              <td>
                  {if $component.config_url}
                    <a class="button crm-popup" href="{$component.config_url}">{ts}configure{/ts}</a>
                  {else}
                      {ts}no configuration available{/ts}
                  {/if}
              </td>

            </tr>
          {/foreach}
      {else}
        <tr>
          <td colspan="4">{ts}No document stores available{/ts}</td>
        </tr>
      {/if}
      </tbody>
    </table>

  </div>

  <div class="crm-block crm-content-block">

    <h3>{ts}CiviOffice Document Renderers (Converters){/ts}</h3>
    <div id="help">{ts}Renders or converts documents{/ts}</div>

    {foreach from=$document_renderer_types item="label" key="id"}
      {capture assign="addDocumentRendererUrl"}{crmURL p="civicrm/admin/civioffice/settings/renderer" q="action=add&type=$id"}{/capture}
      <a class="button crm-popup" href="{$addDocumentRendererUrl}">
          {ts 1=$label}Add %1 Document Renderer{/ts}
      </a>
    {/foreach}

    <table class="row-highlight">
      <thead>
      <tr>
        <th>{ts}Name{/ts}</th>
        <th>{ts}Type{/ts}</th>
        <th>{ts}Ready to use{/ts}</th>
        <th>{ts}Config{/ts}</th>
      </tr>
      </thead>
      <tbody>
      {if !empty($ui_components.document_renderers)}
          {foreach from=$ui_components.document_renderers item=component}
            <tr>

              <td>{$component.name}</td>
              <td>{$component.description}</td>
              <td>{if $component.is_ready}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
              <td>
                  {if $component.config_url}
                    <a class="button crm-popup" href="{$component.config_url}">{ts}Configure{/ts}</a>
                  {else}
                      {ts}no configuration available{/ts}
                  {/if}
                <a class="button crm-popup" href="{$component.delete_url}">{ts}Delete{/ts}</a>
              </td>

            </tr>
          {/foreach}
      {else}
        <tr>
          <td colspan="4">{ts}No document renderers available{/ts}</td>
        </tr>
      {/if}
      </tbody>
    </table>

  </div>

  <div class="crm-block crm-content-block">

    <h3>{ts}CiviOffice Document Editors{/ts}</h3>
    <div id="help">{ts}Editors can be used to edit CiviOffice documents.{/ts}</div>

    <table class="row-highlight">
      <thead>
      <tr>
        <th>{ts}Name{/ts}</th>
        <th>{ts}Description{/ts}</th>
        <th>{ts}Ready to use{/ts}</th>
        <th>{ts}Config{/ts}</th>
      </tr>
      </thead>
      <tbody>
      {if !empty($ui_components.document_editors)}
          {foreach from=$ui_components.document_editors item=component}
            <tr>

              <td>{$component.name}</td>
              <td>{$component.description}</td>
              <td>{if $component.is_ready}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
              <td>
                  {if $component.config_url}
                    <a class="button crm-popup small-popup" href="{$component.config_url}">{ts}configure{/ts}</a>
                  {else}
                      {ts}no configuration available{/ts}
                  {/if}
              </td>

            </tr>
          {/foreach}
      {else}
        <tr>
          <td colspan="4">{ts}No document editors available{/ts}</td>
        </tr>
      {/if}
      </tbody>
    </table>

  </div>

  <div class="crm-block crm-content-block">

    <h3>{ts}Live Snippets{/ts}</h3>

    <div id="help">{ts}Live Snippets allow you to edit sections of your document during its generation using CiviOffice Live Snippet tokens.{/ts}</div>

    <table class="row-highlight">
      <thead>
      <tr>
        <th>{ts}Name{/ts}</th>
        <th>{ts}Description{/ts}</th>
        <th>{ts}Token{/ts}</th>
        <th>
            {capture assign=current_value_label}{ts}Current Default Content{/ts}{/capture}
            {$current_value_label}
            {help id="id-live_snippets-current_content" title=$current_value_label}
        </th>
        <th>{ts}Operations{/ts}</th>
      </tr>
      </thead>
      <tbody>
      {if !empty($ui_components.live_snippets)}
          {foreach from=$ui_components.live_snippets item="live_snippet" key="live_snippet_id"}
            <tr>

              <td>{$live_snippet.label}</td>
              <td>{$live_snippet.description}</td>
              <td><code>{literal}{{/literal}civioffice.live_snippets.{$live_snippet.name}{literal}}{/literal}</code></td>
              <td>{$live_snippet.current_content}</td>
              <td>
                <a class="button crm-popup crm-small-popup"
                   href="{crmURL p='civicrm/admin/civioffice/settings/livesnippet' q="reset=1&id=`$live_snippet_id`&action=update"}"
                   title="{ts escape='htmlattribute'}Edit Live Snippet{/ts}">{ts}Edit{/ts}</a>
                <a class="button crm-popup crm-small-popup"
                   href="{crmURL p='civicrm/admin/civioffice/settings/livesnippet' q="reset=1&id=`$live_snippet_id`&action=delete"}"
                   title="{ts escape='htmlattribute'}Edit Live Snippet{/ts}">{ts}Delete{/ts}</a>
              </td>

            </tr>
          {/foreach}
      {else}
        <tr>
          <td colspan="5">{ts}No Live Snippets available{/ts}</td>
        </tr>
      {/if}
      </tbody>
    </table>

    <div class="action-link">
      <a class="button crm-popup small-popup"
         href="{crmURL p='civicrm/admin/civioffice/settings/livesnippet' q="reset=1&action=add"}">{ts}Add Live Snippet{/ts}</a>
    </div>

  </div>

{literal}
  <script>
    cj(document).ready(function () {
      // Make sure we reload after a setting popup was saved.
      cj(document).on('crmPopupFormSuccess', function () {
        location.reload();
        // TODO: Add loading indicator?
      });
    });
  </script>
{/literal}

{/crmScope}
