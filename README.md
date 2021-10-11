# CiviOffice
## de.systopia.civioffice

### Focus & Features

Written communication with contacts is an essential aspect of the work of non-profit organisations, which usually have their own template letters for this purpose. However, transferring these templates for further use in CiviCRM is complicated. The templates, which are usually created in doc/docx format in programmes such as MS-Office or LibreOffice/OpenOffice, have to be transferred manually into html/css. Afterwards, this "webpage" is converted into printable pdfs by the internal htmltopdf converter. It was therefore important to us to fundamentally simplify this time-consuming and error-prone process with our own tool.

The CiviCRM token system was to be fully integrated so that individualised serial letters could be created automatically on the basis of the contact management. It should of course also be possible to use our own development "de.systopia.stoken", which supplements the existing token system with a variety of additional database keys. In order to enable the administration of template letters for all employees of the organisation centrally, but also individually, the configuration of the file system was also made possible.

We focused on Microsoft's .docx as the source format. This zip-compressed xml-format seems to be a sensible starting point, which is used as standard in the everyday life of our customers and can technically completely map the transmission of the token system. As a target format, the document can now either remain as it is or be converted into the "permanent" .pdf format. For the conversion, the command-line tool unoconv (https://github.com/unoconv/) is used, which must be installed on the server for this purpose.
+ organize templates organization-wide or individually
+ use CiviCRM tokens
+ convert to .pdf
+ integrated document-locking mechanism, to avoid documents beeing corrupted by multi-user usage
#### Planned features
+ Further editors are to be configurable
### Installation instructions
#### Preparations
The following components must be installed on the server:
+ unoconv (https://github.com/unoconv/)
+ libreoffice (https://www.libreoffice.org/)
  the successful installation can be verified:
  `unoconv --version`
```
mkdir /var/www/.cache
sudo chown www-data:www-data /var/www/.cache
mkdir /var/www/.config
sudo chown www-data:www-data /var/www/.config
```
#### installing
This extension needs to be installed manually into CiviCRM. It is not (yet) available from the built-in extensions catalog.

First, download an official release archive from the release page. Unpack the archive and move the directory into your extensions directory (e.g., .../civicrm/ext/; you can find the exact location in your CiviCRM settings (Administer/System Settings/Directories)).

Next, open the extensions page in the CiviCRM settings (Administer/System Settings/Extensions). Find the extension CiviOffice in the *Extensions* tab and click on *Install*. The extension will be set up.
#### possible problems
If the web server's user and the unoconv user are not identical, these permissions must be set. Otherwise unoconv/libreoffice cannot be used as a converter in CiviOffice.
### Configuration
Administration -> Administration Console -> Communication -> CiviOffice Settings
or ``/civicrm/admin/civioffice/settings``

Via the *Configure* button, the respective options can be activated either by placing a tick, or by specifying a path. The column *Ready To Use* gives clear feedback on the activation and whether the component can be used without errors. Furthermore, the respective entry is no longer greyed out and the background colour of the description field changes from red to green.
#### Document Stores
All locations for storing documents are listed here:
1. Local Folder
   A local folder is required if documents are stored on the server and managed independently by the server. This can be an existing document system or a Samba share.  CiviOffice uses it for read-only access. This folder could be an existing shared folder of the organisation. A local folder is not used for uploaded documents and should not be confused with the Temp folder of the renderer.
2. Shared Uploads
   Documents can be uploaded here for shared use in the organisation.
3. My Uploads
   Documents for the personal use of the CiviCRM user are stored here. At least this store should be activated for using CiviOffice.
#### Document Renderers
Two paths are entered here that are essential for using CiviOffice:
1. The path to the binary of unoconv.
   Usually this is
   ``/usr/bin/unoconv``
   The path can otherwise be determined with the following command from the server console:
   ``which unoconv``
2. The path to the temp folder.
   As a rule, this should look like this
```
/var/www/vhosts/[YOUR_DOMAIN]/httpdocs/dev/public/sites/default/files/civicrm/templates_c/civioffice
```
The directory *civioffice* must be created manually in the described path, the name can be freely chosen. We recommend creating this directory inside *templates_c*
In case of uncertainty, the domain name can be determined as follows:
from within the home directory of the server:
``pwd``
The path entry is verified dynamically, so saving is only possible when an existing path is entered. The warning information provides valuable information about possibly missing access authorisations.
#### Document Editors
In future, it should be possible to determine an editor oneself in order to enable more specific configurations.
### Usage
#### Managing documents
The documents used in CiviOffice can be managed via
``/civicrm/civioffice/document_upload``

At the moment the menu can only be opened via this link. But permanently a button will be added to the CiviCRM GUI.
##### Available Documents
With the blue icon behind the headline, you can switch between the stores (*Shared Documents* / *Private Documents*), if configured.
The table shows the available documents of the selected store. These can be deleted or downloaded under *Actions*.
##### Upload More
If at least one store has been configured, a .docx file can be selected, uploaded and made available using the common button.
#### Create letter / general function test
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
#### Using custom fonts
If you want to save certain fonts in your docx document, please note the following procedure in Libre Office (MSword might be likewise):
```
File -> Properties -> Font -> Font embedding : set tick at "Embed fonts in the document"
(You have to repeat this for any document, as this is not enabled globally!)
Save as -> .docx
```
The file-size will increase drastically, as you are saving all fonts in the document.
Further note that this may not include all weights and styles of the fonts (Light, Bold, Italic etc.). While working with .pdfs can be rather reliable, working with docx and different software (LibreOffice, Excel, GoogleDocs, etc.) could prove complicated.

If you want to work with certain individual fonts on a regular basis, it is advisable to store them on the server itself, making it available to all documents. The common formats for fonts are OTF (OpenType) and TTF (TrueType).
The common places for these are:
``/usr/share/fonts/opentype``
``/usr/share/fonts/truetype``
As a rule, you have to ask your hoster to install the fonts, but generally you just have to create the respective directory, copy the font-file there and recreate the fonts cache with something like:
``sudo fc-cache -f -v``