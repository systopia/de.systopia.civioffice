<div class="crm-block crm-form-block">

  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

    {if $action eq 8}
        {* Delete *}
      <div class="messages status no-popup">
        <p>{icon icon="fa-info-circle"}{/icon}
            {ts}Are you sure you want to delete this Document Editor?{/ts} {ts}This action cannot be undone.{/ts}</p>
      </div>
    {else}
        {* Add or Update *}
      <div class="crm-section">
        <div class="label">{$form.name.label}</div>
        <div class="content">{$form.name.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-section">
        <div class="label">{$form.active.label}</div>
        <div class="content">{$form.active.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-section">
        <div class="label">{$form.file_extensions.label}</div>
        <div class="content">{$form.file_extensions.html}</div>
        <div class="clear"></div>
        <div class="help">{ts}Space separated list of file extensions to handle. If empty, all files supported by the editor type are handled. If an extension is already set in another editor, it will be removed from it. An editor without file extensions will be used only if there's no editor with a matching extension.{/ts}</div>
      </div>
        {include file=$editorTypeSettingsTemplate}
    {/if}

  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

</div>
