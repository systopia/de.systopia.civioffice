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

<div class="crm-section">
  <div class="label">{$form.civioffice_store_upload_public.label}</div>
  <div class="content">{$form.civioffice_store_upload_public.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.civioffice_store_upload_private.label}</div>
  <div class="content">{$form.civioffice_store_upload_private.html}</div>
  <div class="clear"></div>
</div>

  {* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
{/crmScope}