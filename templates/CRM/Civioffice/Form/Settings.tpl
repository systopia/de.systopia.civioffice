{*-------------------------------------------------------+
| SYSTOPIA OnlyOffice Integration                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
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

<div class="crm-section">
  <div class="label">{$form.active_backend.label}</div>
  <div class="content">{$form.active_backend.html}</div>
  <div class="clear"></div>
</div>


<table>
  <thead>
    <tr>
      <th>{ts}Name{/ts}</th>
      <th>{ts}Ready to use{/ts}</th>
      <th>{ts}Config{/ts}</th>
    </tr>
  </thead>
  <tbody>
{foreach from=$backends item=backend}
    <tr>
      <td>{$backend.name}</td>
      <td>{if $backend.is_ready}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
      <td>{if $backend.config_url}
          <a class="button crm-popup" href="{$backend.config_url}">{ts}configure{/ts}</a>
          {else}
          {ts}no configuration available{/ts}
          {/if}
      </td>
    </tr>
{/foreach}
  </tbody>
</table>


{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{/crmScope}