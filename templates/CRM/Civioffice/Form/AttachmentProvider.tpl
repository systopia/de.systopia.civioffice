{*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2021 SYSTOPIA                            |
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
{foreach from=$attachment.elements key="attachment_element" item="attachment_element_type"}
    <div class="crm-section">
      <div class="label">
          {$form.$attachment_element.label}
          {capture assign="help_id"}id-{$attachment_element_type}{/capture}
          {help id=$help_id title=$form.$attachment_element.label}
      </div>
      <div class="content">{$form.$attachment_element.html}</div>
      <div class="clear"></div>
    </div>
{/foreach}
{/crmScope}
