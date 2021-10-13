# Configuration
Administration -> Administration Console -> Communication -> CiviOffice Settings
or ``/civicrm/admin/civioffice/settings``

Via the *Configure* button, the respective options can be activated either by placing a tick, or by specifying a path. The column *Ready To Use* gives clear feedback on the activation and whether the component can be used without errors. Furthermore, the respective entry is no longer greyed out and the background colour of the description field changes from red to green.
## Document Stores
All locations for storing documents are listed here:
1. Local Folder
   A local folder is required if documents are stored on the server and managed independently by the server. This can be an existing document system or a Samba share.  CiviOffice uses it for read-only access. This folder could be an existing shared folder of the organisation. A local folder is not used for uploaded documents and should not be confused with the Temp folder of the renderer.
2. Shared Uploads
   Documents can be uploaded here for shared use in the organisation.
3. My Uploads
   Documents for the personal use of the CiviCRM user are stored here. At least this store should be activated for using CiviOffice.
## Document Renderers
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
## Document Editors
In future, it should be possible to determine an editor oneself in order to enable more specific configurations.

example of a working configuration menu:
![CiviOffice configuration menu](img/civioffice-configuration.png "CiviOffice configuration menu")