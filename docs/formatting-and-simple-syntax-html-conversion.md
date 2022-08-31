# Formatting and simple syntax with HTML-to-OOXML-conversion

If you insert certain HTML elements into Live Snippets, CiviOffice will convert them into OOXML which allows users to do some basic formatting, e.g. a list or bold font. The HTML elements that can be rendered by CiviOffice are [these](https://github.com/PHPOffice/PHPWord/blob/be0190cd5d8f95b4be08d5853b107aa4e352759a/src/PhpWord/Shared/Html.php#L166-L198).

## A workaround to insert some Smarty-based logic into your CiviOffice documents

Smarty syntax can not be used directly in CiviOffice documents. However, there is a workaround for this which, admittedly, requires some technical affinity (as does Smarty syntax, too...). The basic recipe is this:

  + Install the [MoreGreetings extension](https://github.com/systopia/de.systopia.moregreetings)
  + This tool was initially developed to deal with many and complex greetings. It provides additional (and larger) fields which are filled with generated values based on Smarty-syntax configurations - similar to the core greetings fields. The usage of these fields is not restricted to greetings and could be extended to anything you require. E.g. you could create an address block containing ```<br>``` elements for line breaks. Using smarty conditions, you might also insert the address country only if it's not your home country. You can call the CiviCRM API using Smarty within MoreGreetings fields to do advanced stuff here. MoreGreetings will render all this into a regular custom field.
  + Then, you can insert the token for that MoreGreetings custom field into a Live Snippet while generating your document. The HTML elements will be converted to docx formatting - in our example line breaks, giving you a readily-formatted address block to use in a CiviOffice-genereated letter.  
