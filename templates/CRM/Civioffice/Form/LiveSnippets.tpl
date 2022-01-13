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

{if !empty($live_snippet_elements)}
  <div class="crm-accordion-wrapper">
    <div class="crm-accordion-header">{ts}Live Snippets{/ts}</div>
    <div class="crm-accordion-body">
        {foreach from=$live_snippet_elements item="live_snippet_element" key="live_snippet_name"}
          <div class="crm-section">
            <div class="label">{$form.$live_snippet_element.label}</div>
            <div class="content">{$form.$live_snippet_element.html}</div>
            {if $live_snippet_descriptions.$live_snippet_name}
                <div class="description">
                    {$live_snippet_descriptions.$live_snippet_name}
                </div>
            {/if}
            <div class="clear"></div>
          </div>
        {/foreach}
    </div>
  </div>
{/if}