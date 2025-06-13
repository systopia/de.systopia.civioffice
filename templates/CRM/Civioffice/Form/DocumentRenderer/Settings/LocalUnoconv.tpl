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
    <div class="help">{ts}We strongly recommend creating a system-wide lock file on this server to synchronise access. (It may only run one unoconv process at the same time.) Such a file is created automatically at the location shown below. Please note: This path needs to be equal in every civicrm environment on this server. Otherwise, locking is only active for this very civicrm instance!{/ts}</div>
    <div class="label">{$form.unoconv_lock_file_path.label}</div>
    <div class="content">{$form.unoconv_lock_file_path.html}</div>
    <div class="clear"></div>
  </div>

{/crmScope}
