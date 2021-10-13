# Working With Documents
## Managing documents
The documents used in CiviOffice can be managed via
``/civicrm/civioffice/document_upload``

At the moment the menu can only be opened via this link. But permanently a button will be added to the CiviCRM GUI.
### Available Documents
With the blue icon behind the headline, you can switch between the stores (*Shared Documents* / *Private Documents*), if configured.
The table shows the available documents of the selected store. These can be deleted or downloaded under *Actions*.
### Upload More
If at least one store has been configured, a .docx file can be selected, uploaded and made available using the common button.
## Create a letter
### test if your set-up is fully working
1. Go to
   ``[YOUR_DOMAIN]/civicrm/civioffice/document_upload``
2. Upload the simple Office-template **vorlage_kontakte_stokens.docx**.
   This template is located at
   ``civicrm-demo.systopia.de/civicrm/civioffice/document_upload``
   under *Shared Uploaded Documents*.
   This template uses tokens to transfer various details about the user & a selected contact and is therefore well suited for testing.
3. Select any contact, tick *Find contacts* in the list and select the *Action* **Create Documents (CiviOffice)**. Multiple contacts can also be selected.
   Furthermore, the *Action* **Create Documents** can also be selected from a single contact overview.
4. The page *CiviOffice - Generate Documents* appears and shows the uploaded document and the configured renderer.
5. *Target document type* allows a choice between pdf and docx as target format.
6. To limit the load on the server, the maximum size of the batch management should be selected under *batch size for processing*.
7. By selecting *GENERATE 1 FILES* the selected document is created and can be downloaded as a .zip file in the following dialogue.
8. Please click the *download* button only once. Clicking the button will trigger the generation of your zip file, which may take a long time depending on the size and complexity of your documents.
9. Afterwards please always use the "back to previous page" button to finish this process by delete the created document from the main memory and thus free up the memory space. This is particularly important for larger processing runs.
10. In the finished document, basic information about the contact/user/current date should be displayed in the appropriate plain text, resolved in tables.
11. This has successfully tested that CiviOffice has been set up correctly.

example of "Generate Documents" page:
![CiviOffice generate documents](img/civioffice-generate-documents.png "CiviOffice generate documents")