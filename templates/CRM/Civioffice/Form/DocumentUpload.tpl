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

  <h3>{ts}Documents{/ts}</h3>
  <table>
    <thead>
      <tr>
        <th>{ts}Name{/ts}</th>
{*        <th>{ts}Type{/ts}</th>*}
        <th>{ts}Size{/ts}</th>
        <th>{ts}Upload{/ts}</th>
        <th>{ts}Actions{/ts}</th>
      </tr>
    </thead>
    <tbody>
    {foreach from=$document_list item=document}
      <tr>
        <td><i title="{$document.mime_type}" class="crm-i {$document.icon}" aria-hidden="true"></i> {$document.name}</td>
        <td>{$document.size}</td>
        <td>{$document.upload_date}</td>
        <td>DELETE</td>
      </tr>
    {/foreach}
    </tbody>
  </table>

  <h3>{ts}Upload More{/ts}</h3>

  <a class="button" href="{$switch_contexts_url}">{ts}switch folder{/ts}</a>

  <div class="crm-section">
    <div class="label">{$form.upload_file.label}</div>
    <div class="content">{$form.upload_file.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>
{/crmScope}