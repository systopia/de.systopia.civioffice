# Introduction

Written communication with contacts is an essential aspect of the work of
non-profit organisations. Typically, they have their own letter templates etc.
Utilizing tokens to insert contact and other data, pdf documents for many
contacts can be produced fast.

However, creating document templates for further use in CiviCRM with the core
functionality is complicated as layouts have to be implemented in HTML and CSS.
For non-technical users, it is hardly possible to create a proper layout for a
document template. This extension aims to solve this problem.

The approach is to facilitate the use of a common file format (.docx) for
templates that integrate with CiviCRM workflows, including CiviCRM tokens. .docx
files can be created and edited with any text processing software, so the format
should give easy access to all users, although other input formats might be
implemented later.

For the conversion from .docx to .pdf, the command-line
tool [unoconv](https://github.com/unoconv/) is used, which must be installed on
the server for this purpose.

## Features

+ organize templates organization-wide or individually
+ use CiviCRM tokens
+ create documents and activities for single contacts
+ create documents for many contacts (search result action)
+ convert to .pdf or export as processed .docx
+ integrate
  with [de.systopia.donrec](https://github.com/systopia/de.systopia.donrec) to
  create cover letters for donation receipts (de.systopia.donrec 2.1+ required)
+ integrate with [de.systopia.mailbatch](https://github.com/systopia/de.systopia.mailbatch) to send emails with personalized attachments
+ API

## Planned features

+ insert "live snippets", i.e. text portions to be inserted in the document in
  the creation process (a full-fleged document editor might be introduced at a
  later stage)
+ search result actions for contributions, participants, and memberships
+ add WebDAV compatibility to connect to external document stores such as
  Nextcloud, Sharepoint, or GoogleDrive

