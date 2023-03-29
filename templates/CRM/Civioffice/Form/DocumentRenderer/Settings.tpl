{*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2022 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
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
  <div class="crm-block crm-form-block">

    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="top"}
    </div>

      {if $action eq 8}
          {* Delete *}
        <div class="messages status no-popup">
          <p>{icon icon="fa-info-circle"}{/icon}
              {ts}Are you sure you want to delete this Document Renderer?{/ts} {ts}This action cannot be undone.{/ts}</p>
        </div>
      {else}
          {* Add or Update *}
        <div class="crm-section">
          <div class="label">{$form.name.label}</div>
          <div class="content">{$form.name.html}</div>
          <div class="clear"></div>
        </div>
          {include file=$rendererTypeSettingsTemplate}
      {/if}

    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>

  </div>
{/crmScope}
