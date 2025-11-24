CREATE TABLE IF NOT EXISTS `civicrm_civioffice_document_editor` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique CiviofficeDocumentEditor ID',
  `name` varchar(255) NOT NULL COMMENT 'The name of the editor.',
  `is_active` boolean NOT NULL DEFAULT FALSE COMMENT 'Is the editor enabled?',
  `file_extensions` text NOT NULL COMMENT 'File extensions that are handled by this editor.',
  `type` varchar(255) NOT NULL COMMENT 'The type of the editor.',
  `type_config` text NOT NULL COMMENT 'The configuration for the specified editor type.',
  PRIMARY KEY (`id`)
)
  ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
