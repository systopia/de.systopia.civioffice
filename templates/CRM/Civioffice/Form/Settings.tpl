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


<h3>{ts}CiviOffice Document Stores{/ts}</h3>
<div id="help">{ts}Document Stores are used to store files{/ts}</div>

<table>
  <thead>
    <tr>
      <th>{ts}Name{/ts}</th>
      <th>{ts}Description{/ts}</th>
      <th>{ts}Ready to use{/ts}</th>
      <th>{ts}Config{/ts}</th>
    </tr>
  </thead>
  <tbody>
{foreach from=$ui_components.document_stores item=component}
    <tr class="{if not $component.is_ready}disabled{/if}">
      <td>{$component.name}</td>
      <td>{$component.description}</td>
      <td>{if $component.is_ready}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
      <td>{if $component.config_url}
            <a class="button crm-popup" href="{$component.config_url}">{ts}configure{/ts}</a>
          {else}
            {ts}no configuration available{/ts}
          {/if}
      </td>
    </tr>
{/foreach}
  </tbody>
</table>

<h3>{ts}CiviOffice Document Renderers{/ts}</h3>
<div id="help">{ts}Renders documents{/ts}</div>

<table>
  <thead>
  <tr>
    <th>{ts}Name{/ts}</th>
    <th>{ts}Description{/ts}</th>
    <th>{ts}Ready to use{/ts}</th>
    <th>{ts}Config{/ts}</th>
  </tr>
  </thead>
  <tbody>
  {foreach from=$ui_components.document_renderers item=component}
    <tr>
      <td>{$component.name}</td>
      <td>{$component.description}</td>
      <td>{if $component.is_ready}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
      <td>{if $component.config_url}
          <a class="button crm-popup" href="{$component.config_url}">{ts}configure{/ts}</a>
        {else}
          {ts}no configuration available{/ts}
        {/if}
      </td>
    </tr>
  {/foreach}
  </tbody>
</table>


<h3>{ts}CiviOffice Document Editors{/ts}</h3>
<div id="help">{ts}Editors are used to edit text files{/ts}</div>

  <table>
  <thead>
  <tr>
    <th>{ts}Name{/ts}</th>
    <th>{ts}Description{/ts}</th>
    <th>{ts}Ready to use{/ts}</th>
    <th>{ts}Config{/ts}</th>
  </tr>
  </thead>
  <tbody>
  {foreach from=$ui_components.document_editors item=component}
    <tr>
      <td>{$component.name}</td>
      <td>{$component.description}</td>
      <td>{if $component.is_ready}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
      <td>{if $component.config_url}
          <a class="button crm-popup small-popup" href="{$component.config_url}">{ts}configure{/ts}</a>
        {else}
          {ts}no configuration available{/ts}
        {/if}
      </td>
    </tr>
  {/foreach}
  </tbody>
</table>

{literal}
<script>
  cj(document).ready(function() {
    // make sure we reload after a setting popup was saved
    cj(document).on('crmPopupFormSuccess', function () {
      location.reload();
      // todo: add loading indicator?
    });
  });
</script>
{/literal}
{/crmScope}