# Introduction

Short version: This extension allows you to use *DOCX* files as document
templates in many CiviCRM workflows. It also features development interfaces that allow programmers to integrate CiviOffice with other extensions.

This documentation refers to version 1.0.0 of CiviOffice.

## Concept

Written communication with contacts is an essential aspect of the work of
non-profit organisations. Typically, they have their own templates for letters and other documents.
Utilizing tokens to insert contact and other data, personalized documents for many
contacts can be produced fast.

However, creating document templates for further use in CiviCRM with the core
functionality (as
documented [here](https://docs.civicrm.org/user/en/latest/email/message-templates/))
is complicated as layouts have to be implemented in HTML and CSS.
For non-technical users, it is hardly possible to create a custom layout for a
document template. Also, there are technical limitations that also tech-savvy
people can not overcome, e.g. creating multi-page templates. This extension aims
to solve these problems and to provide an open and modular framework to
integrate in multiple ways, e.g. with external document servers and other
extensions.

The approach is to facilitate the use of a common file format (.docx) for
templates that integrate with CiviCRM workflows, including CiviCRM tokens. .docx
files can be created and edited with any text processing software, so the format
should give easy access to all users, although other input formats might be
implemented in the future. As ouptut formats, .pdf and .docx have been
implemented so far.

## Features

- organize templates organization-wide or individually
- use CiviCRM tokens, with an overview page of all available tokens as user help
- insert "live snippets", i.e. text portions to be inserted into the document
  during the creation process (a full-fleged document editor might be introduced at a
  later stage)
- create documents and activities for single contacts as well as many contacts (
  search result action)
- create documents and activities for contributions, participants, memberships
  and cases (implemented as search result actions), enabling users to access
  data for contributions, events, participants, memberships, cases etc. via
  tokens
- convert to .pdf or export as processed .docx
- integrate
  with [de.systopia.donrec](https://github.com/systopia/de.systopia.donrec) to
  create cover letters for donation receipts
- integrate
  with [de.systopia.mailbatch](https://github.com/systopia/de.systopia.mailbatch)
  to send emails with personalized attachments (search result actions for
  contacts and contributions)
- APIs
- (X)HTML to OOXML conversion to allow for simple formatting within tokens (also
  a workaround for missing Smarty syntax)
- PHP 8 compatibility

## Intended future improvements

- add WebDAV compatibility to connect to external document stores such as
  Nextcloud, Sharepoint, or GoogleDrive
- integrate a full-fledged text editor to customize templates before
  processing
- improve abilities to process logic in CiviOffice documents, e.g. implement
  macro functionality

## Known issues

- .docx files use an XML structure internally to describe formatting and other
  properties of text elements. In some cases, this can lead to CiviCRM tokens
  being split up by XML tags, making them infunctional. CiviOffice tries to
  mitigate this problem by optimizing the XML structure. Currently this 'repair
  functionality' is enabled with the configuration option
  'prepare .docx documents'. However, there are some things that CiviOffice can
  not repair (yet, or ever).

**In order to avoid issues with infunctional tokens, you should:**

- make sure there are no mixed formatting properties in your tokens (e.g. a
  portion of the token is in bold font)
- disable spell checking in your documents, especially make sure you don't
  apply exceptions from spell checking to bits of your tokens
