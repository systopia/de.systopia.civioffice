# Introduction
Written communication with contacts is an essential aspect of the work of non-profit organisations, which usually have their own template letters for this purpose. However, transferring these templates for further use in CiviCRM is complicated. The templates, which are usually created in doc/docx format in programmes such as MS-Office or LibreOffice/OpenOffice, have to be transferred manually into html/css. Afterwards, this "webpage" is converted into printable pdfs by the internal htmltopdf converter. It was therefore important to us to fundamentally simplify this time-consuming and error-prone process with our own tool.

The CiviCRM token system was to be fully integrated so that individualised serial letters could be created automatically on the basis of the contact management. It should of course also be possible to use our own development "de.systopia.stoken", which supplements the existing token system with a variety of additional database keys. In order to enable the administration of template letters for all employees of the organisation centrally, but also individually, the configuration of the file system was also made possible.

We focused on Microsoft's .docx as the source format. This zip-compressed xml-format seems to be a sensible starting point, which is used as standard in the everyday life of our customers and can technically completely map the transmission of the token system. As a target format, the document can now either remain as it is or be converted into the "permanent" .pdf format. For the conversion, the command-line tool unoconv (https://github.com/unoconv/) is used, which must be installed on the server for this purpose.
## Features
+ organize templates organization-wide or individually
+ use CiviCRM tokens
+ convert to .pdf
+ integrated document-locking mechanism, to avoid documents beeing corrupted by multi-user usage
## Planned features
+ Further editors are to be configurable
## Requirements
TODO