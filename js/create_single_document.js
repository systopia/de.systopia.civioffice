/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

cj(document).ready(function() {
  /** keep track of whether the click handler has been registered */
  let click_handler_registered = false;

  /**
   * Register a new click handler on the 'preview' button,
   *  disabling the default behaviour, because we want
   *  just redirect to the PDF download
   */
  function register_click_handler()
  {
    if (!click_handler_registered) {
      let preview_button = cj("button[data-identifier=_qf_DocumentFromSingleContact_preview]");
      if (preview_button.length) {
        // we have found the button: let's manipulate
        click_handler_registered = true;
        preview_button
          .removeAttr('data-identifier')
          .off()
          .click(function(event) {
            event.stopPropagation();
            trigger_download(true);
          });
      } else {
        // probably too early...we'll try again later
      }

      // do the same
      let generate_button = cj("button[data-identifier=_qf_DocumentFromSingleContact_upload]");
      if (generate_button.length) {
        // we have found the button: let's manipulate
        click_handler_registered = true;
        generate_button
          .removeAttr('data-identifier')
          .off()
          .click(function(event) {
            event.stopPropagation();
            trigger_download(false);
          });
      } else {
        // probably too early...we'll try again later
      }
    }
  }

  /** Trigger the download of the configured file */
  function trigger_download(is_preview = true) {
    // generate a href object an click on it :)
    let render_config = CRM.vars.civioffice_single_document;
    let download_link = document.createElement("a");
    download_link.setAttribute('download',
      render_config.document_name
        + render_config.mime2suffix[cj("#target_mime_type").val()]);
    download_link.href = render_config.renderer_url + "?";
    download_link.href += "document_uri=" + btoa(cj("#document_uri").val());
    download_link.href += "&renderer_uri=" + btoa(cj("#document_renderer_uri").val());
    download_link.href += "&target_mime_type=" + btoa(cj("#target_mime_type").val());
    download_link.href += "&contact_ids=" + render_config.contact_id;
    if (!is_preview) {
      download_link.href += "&activity_type_id=" + cj("#activity_type_id").val();
      download_link.href += "&activity_attach_file=" + cj("#activity_attach_doc").val();
    }
    document.body.appendChild(download_link);
    download_link.click();
    download_link.remove();

    // inform user
    if (is_preview) {
      CRM.status(ts('Rendering Preview', {domain: ['de.systopia.civioffice', null]}));
    } else {
      if (cj("#activity_type_id").val()) {
        CRM.status(ts('Rendering Document and Activity', {domain: ['de.systopia.civioffice', null]}));
      } else {
        CRM.status(ts('Rendering Document', {domain: ['de.systopia.civioffice', null]}));
      }
    }

    // close window with the download button?
    if (!is_preview) {
      //cj("button[data-identifier=_qf_DocumentFromSingleContact_close]").click();
    }
  }


  // finally: trigger the click handler override
  // 1. directly
  register_click_handler();

  // 2. after popup load completed
  cj(document).ajaxComplete(function() {
    register_click_handler();
  })
});