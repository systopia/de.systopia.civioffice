<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2022 SYSTOPIA                            |
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

return [
    'civioffice_renderers' => [
        'name' => 'civioffice_renderers',
        'type' => 'String',
        'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
        'is_domain' => 1,
        'description' => E::ts('CiviOffice renderers.'),
        'default' => [],
        'title' => E::ts('CiviOffice Renderers'),
        'help_text' => '',
        'html_type' => 'select',
        'html_attributes' => [
            'class' => 'crm-select2',
            'multiple' => 1,
        ],
    ],
];
