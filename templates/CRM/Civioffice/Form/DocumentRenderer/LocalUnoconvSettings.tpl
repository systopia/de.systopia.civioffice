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
    <div class="help">{ts}Please provide the path of your Unoconv binary.{/ts}</div>
    <div class="label">{$form.unoconv_binary_path.label}</div>
    <div class="content">{$form.unoconv_binary_path.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="help">{ts}We strongly recommend creating a system wide lock file on this server to synchronise access. You can create such a file doing this in the server console:<br><code>touch /some/accessible/path/unoconv.lock && chmod 777 /some/accessible/path/unoconv.lock</code><br>Please note: This path needs to be equal in every civicrm environment on this server. Otherwise locking is only active for this very civicrm instance!{/ts}</div>
    <div class="label">{$form.unoconv_lock_file.label}</div>
    <div class="content">{$form.unoconv_lock_file.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="help">{ts}Working / temp path where files are stored until a download happens.<br>For example: <code>.../civicrm/templates_c/civioffice/temp</code>{/ts}</div>
    <div class="label">{$form.temp_folder_path.label}</div>
    <div class="content">{$form.temp_folder_path.html}</div>
    <div class="clear"></div>
  </div>

  {* FOOTER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
{/crmScope}