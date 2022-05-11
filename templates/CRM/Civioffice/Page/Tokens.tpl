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
  <div class="crm-section">
    <div class="crm-content-block">
      <div class="help">
          {ts}This page lists all tokens CiviOffice is potentially able to replace in documents, however, this depends on the context. E.g. contribution tokens are only available in a context of generating documents for particular contributions, whereas general tokens, such as for domain or date, are always available.{/ts}
      </div>
        {if !empty($tokens)}
            {foreach from=$tokens item=group}
              <details class="panel">
                <summary class="panel-heading">{$group.text}</summary>
                <table class="panel-body">
                    {foreach from=$group.children item=token}
                      <tr>
                        <td>{$token.text}</td>
                        <td>
                          <pre>{$token.id}</pre>
                        </td>
                      </tr>
                    {/foreach}
                </table>
              </details>
            {/foreach}
        {/if}
    </div>
  </div>
{/crmScope}
