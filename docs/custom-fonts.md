# Using custom fonts

If you want to work with certain individual fonts on a regular basis, it is
advisable to store them on the server itself, making it available to all
documents. The common formats for fonts are OTF (OpenType) and TTF (TrueType).
The common locations for these are:

```
/usr/share/fonts/opentype
/usr/share/fonts/truetype
```

Unless you are operating on a full root server, you likely have to ask your
hosting provider to install the fonts. If you have access to that part of the
server, you just have to create the respective directory, copy the font file
there and recreate the fonts cache with something like:

```bash
sudo fc-cache -f -v
```

As a shortcut, you might include fonts in your docx document. In Libre Office,
this can be done as follows:

+ File -> Properties -> Font -> Font embedding : enable "Embed fonts in the
  document" (You have to repeat this for any document, as this is not enabled
  globally!)
+ Save as -> .docx

**Please note:** The second approach will increase file-sizes drastically as you
are saving all fonts within the document. Further note that this may not include
all weights and styles of the fonts (Light, Bold, Italic etc.). While working
with .pdfs can be rather reliable, working with docx and different software
(LibreOffice, Excel, GoogleDocs, etc.) could prove complicated.

