{*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2025 SYSTOPIA                            |
| Author: S. Nowotnik (nowotnik@systopia.de)             |
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

{if $formtype eq "delete"}
{* Delete *}

<div class="crm-section">
  <div class="help">{ts}The autodetected path to the home folder of the user that runs this CiviCrm instance{/ts}</div>
  <div class="label">{$form.home_folder.label}</div>
  <div class="content">{$form.home_folder.html}</div>
  <div class="clear"></div>
</div>
 
{else}
{* Add or Update *}
 
<div class="crm-section">
  <div class="help">{ts}Path to home folder of the user that runs this CiviCrm instance{/ts}.<br>{ts}Usually this value must not be altered because CiviOffice detects the home-folder automatically.{/ts}<br>{ts}Only if you experience erros that are related to the access to the filesystem, then you might check if this folder is set correctly or ask a administrator to verify.{/ts}</div>
  <div class="label">{$form.home_folder.label}</div>
  <div class="content">{$form.home_folder.html}</div>
  <div class="clear"></div>
</div>

{/if}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
{/crmScope}
