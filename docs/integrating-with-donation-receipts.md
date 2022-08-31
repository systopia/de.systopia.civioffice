# Integrating With Donation Receipts

If you are using
[de.systopia.donrec](https://github.com/systopia/de.systopia.donrec) to create
donation receipts, it can be integrated with CiviOffice to create cover letters
for the receipts. The minimum version required for this is
de.systopia.donrec 2.1-alpha4.

In the general settings for the donation receipts
extension (`/civicrm/admin/setting/donrec?reset=1`), determine the document
you want to use as the template for the cover letters. A new output format
option will be available in the donation receipts dialogue
then (`Individual PDFs with cover letter (CiviOffice)`). Your cover letters
will be created separately from the receipts, but sorted so they can be printed
or merged together easily. 

## Known issues
The "prepare docx documents" options to mitigate problems with MS-Word-generated
files is not yet available in this workflow.
