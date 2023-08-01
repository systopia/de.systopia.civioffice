# Integrating With Donation Receipts

If you are using the extension
[de.systopia.donrec](https://github.com/systopia/de.systopia.donrec) to create
donation receipts, it can be integrated with CiviOffice to create cover letters
with the receipts. This comes in handy as you will be provided a zip archive
with all documents, letters and receipts, ordered by CiviCRM IDs. Otherwise it
can be tedious and error-prone to create the letters separately and assign them
manually to the receipts for mailing, especially with larger quantities.

The minimum version required for this is
de.systopia.donrec 2.1.

In the general settings for the donation receipts
extension (`/civicrm/admin/setting/donrec?reset=1`), determine the document
you want to use as the template for the cover letters. A new output format
option will be available in the donation receipts dialogue
then (`Individual PDFs with cover letter (CiviOffice)`). As stated above, your
cover letters will be created separately from the receipts, but sorted by
CiviCRM ID so they can be printed
or merged together easily.

## Known issues
~~The "prepare docx documents" options to mitigate problems with MS-Word-generated
files is not yet available in this workflow.~~ 
This is fixed in CiviOffice 0.10.
