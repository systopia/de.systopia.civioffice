# Inserting dynamic content with "live snippets"

As of version 0.5, CiviOffice provides a means to insert dynamic content when creating your document (although it still lacks a full-fledget text editor). The so-called "live snippets" offer a token-like approach to insert content that is neither static nor comes from CiviCRM data. This might be useful e.g. to insert a personal sentence into your standard letter.

## Defining live snippets

Before you can use live snippets, you have to configure them. Go to your global CiviOffice settings at <my-domain>/civicrm/admin/civioffice/settings?reset=1 and add one or more snippets. The format is `{civioffice.live_snippets.<SNIPPET-NAME>}`. 

## Using live snippets

When creating documents, CiviOffice offers you one form field per live snippet to enter your dynamic content. **Make sure the expected live snippet placeholders are actually in your document, as CiviOffice will not check this for you.**  
