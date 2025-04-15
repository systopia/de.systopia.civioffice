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

declare(strict_types = 1);

use CRM_Civioffice_ExtensionUtil as E;

return [
  'civioffice_general_home_folder' => [
    'name' => 'civioffice_general_home_folder',
    'type' => 'String',
    'default' => NULL,
    'description' => E::ts('Path to home folder of the user that runs this CiviCrm instance'),
    'title' => E::ts('Home Folder.'),
    'html_type' => 'text',
  ],
  'civioffice_renderers' => [
    'name' => 'civioffice_renderers',
    'type' => 'Array',
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
