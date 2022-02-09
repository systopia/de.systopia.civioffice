# Installation

## Preparations

The following components must be installed on the server:

+ [unoconv](https://github.com/unoconv/)
+ [libreoffice](https://www.libreoffice.org/)

Note that CiviOffice requires at least CiviCRM Core 5.44.

### Verifying successful unoconv installation

If `unoconv --version` prints out the unoconv version, your installation was successful.

## Installing the extension

This extension needs to be installed manually into CiviCRM. It is not (yet)
available from the built-in extensions catalog.

First, download an official release archive from the release page. Unpack the
archive and move the directory into your extensions directory (e.g.,
`.../civicrm/ext/`; you can find the exact location in your CiviCRM settings (
Administer/System Settings/Directories)).

Next, open the extensions page in the CiviCRM settings (Administer/System
Settings/Extensions). Find the extension CiviOffice in the *Extensions* tab and
click on *Install*. The extension will be set up.

## Basic configuration steps

+ Go to Administration -> Administration Console -> Communication -> CiviOffice
  Settings or ``/civicrm/admin/civioffice/settings`` to set up your document
  stores, renderers and editors
+ The documents used in CiviOffice can be managed
  via ``/civicrm/civioffice/document_upload``. Currently you need to add that
  link to the navigation menu manually.
+ Optional: Create an activity type such as "Created Document (CiviOffice)"

## Known issues

If the web server's user and the unoconv user are not identical, these
permissions must be set. Otherwise unoconv/libreoffice cannot be used as a
converter in CiviOffice.