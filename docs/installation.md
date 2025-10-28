# Installation

## Preparations

The following components must be installed on the server:

- [unoconv](https://github.com/unoconv/)
- [libreoffice](https://www.libreoffice.org/)

Note that CiviOffice requires at least CiviCRM 5.76.

### Verifying successful unoconv installation

If `unoconv --version` prints out the unoconv version, your installation was
successful.

## Installing the extension

This extension needs to be installed manually into CiviCRM. It is not (yet)
available from the built-in extensions catalog.

First, download an official release archive from the release page. Unpack the
archive and move the directory into your extensions directory (e.g.,
`.../civicrm/ext/`). You can find the exact location in your CiviCRM settings (
Administer > System Settings > Directories).

The extension has *Composer* dependencies. Official releases should include them
but if you install the extension from the repository, you will need to manually
run `composer install` or `composer update` within the extension directory.

Next, open the extensions page in the CiviCRM settings (Administer/System
Settings/Extensions). Find the extension CiviOffice in the *Extensions* tab and
click on *Install*. The extension will be set up, including new navigation menu
items.

## Basic configuration steps

- Assign the permission "Access CiviOffice" to user roles as needed.
- In the new CiviOffice navigation, go to CiviOffice
  Settings to set up your document
  stores, renderers and editors
- The documents used in CiviOffice can be managed
  via under "Upload Documents"
- Optional: Create an activity type such as "Created Document (CiviOffice)"

## Known issues

If the web server's user and the unoconv user are not identical, these
permissions must be set. Otherwise unoconv/libreoffice cannot be used as a
converter in CiviOffice.

## Dependencies and configurations for extended functionalities

- in order to integrate with the Donation Receipts extension, install
  de.systopia.donrec 2.1+ and select the template you want to use for cover
  letters in the extension settings
- to send CiviOffice documents as email attachments, install
  de.systopia.mailattachment 1.0+ and:
  - for contacts and contributions, install de.systopia.mailbatch 2.0+
  - for participants, install de.systopia.eventmessages 1.1+
