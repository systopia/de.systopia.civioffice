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
        <th>{ts}Type{/ts}</th>
        <th>{ts}Size{/ts}</th>
        <th>{ts}Upload{/ts}</th>
        <th>{ts}Actions{/ts}</th>
      </tr>
    </thead>
    <tbody>
    {foreach from=$document_list item=document}
      <tr>
        <td>{$document.uri}</td>
        <td>{$document.name}</td>
        <td>{$document.mime_type}</td>
        <td>DELETE</td>
      </tr>
    {/foreach}
    </tbody>
  </table>

  <h3>{ts}Upload More{/ts}</h3>

  <div class="crm-section">
    <div class="label">{$form.upload_file.label}</div>
    <div class="content">{$form.upload_file.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>
{/crmScope}