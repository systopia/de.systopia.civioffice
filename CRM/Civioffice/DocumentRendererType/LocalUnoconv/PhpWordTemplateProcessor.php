<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

use CRM_Civioffice_ExtensionUtil as E;
use PhpOffice\PhpWord;

class CRM_Civioffice_DocumentRendererType_LocalUnoconv_PhpWordTemplateProcessor extends PhpWord\TemplateProcessor
{
    /**
     *
     */
    public function liveSnippetTokensToMacros()
    {
        $this->tempDocumentHeaders = preg_replace(
            '/({civioffice\.live_snippets\..*?})/',
            '\$$1',
            $this->tempDocumentHeaders
        );
        $this->tempDocumentMainPart = preg_replace(
            '/(\{civioffice\.live_snippets\..*?\})/',
            '\$$1',
            $this->tempDocumentMainPart
        );
        $this->tempDocumentFooters = preg_replace(
            '/({civioffice\.live_snippets\..*?})/',
            '\$$1',
            $this->tempDocumentFooters
        );
    }
}
