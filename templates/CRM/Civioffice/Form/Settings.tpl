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
      {if !empty($ui_components.document_renderers)}
          {foreach from=$ui_components.document_renderers item=component}
            <tr>

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
