{*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2021 SYSTOPIA                            |
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
  <p>The current user ID is: <b>{$user_id}</b></p>

  <div class="crm-section">
    <div class="label">{$form.document_uri.label}</div>
    <div class="content">{$form.document_uri.html}</div>
    <div class="clear"></div>
  </div>


  <div class="crm-section">
    <div class="label">{$form.document_renderer_id.label}</div>
    <div class="content">{$form.document_renderer_id.html}</div>
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

  {* FOOTER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

{/crmScope}