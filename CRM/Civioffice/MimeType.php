<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: J. Franz (franz@systopia.de)                   |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

abstract class CRM_Civioffice_MimeType
{
    public const DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingm';
    public const PDF = 'application/pdf';
    public const ZIP = 'application/zip';

    public const ALL = [self::DOCX, self::PDF];
    public const RENDERABLE = [self::DOCX];

    /**
     * Map te mime type to the file ending without a pre dot
     *
     * @param $mime_type
     *
     * @return string
     *   file ending like docx or pdf
     * @throws \Exception
     */
    public static function mapMimeTypeToFileExtension($mime_type): string
    {
        switch ($mime_type) {
            case self::PDF:
                return 'pdf';
            case self::DOCX:
                return 'docx';
            case self::ZIP:
                return 'zip';
            default:
                throw new Exception('Mime types other than pdf and docx yet need to be implemented');
        }
    }

    /**
     * Checks if the file ending/extension matches with the given fully qualified mime type
     *
     * Mimetype checks could be handled differently in the future: https://github.com/systopia/de.systopia.civioffice/issues/2
     *
     * @param $file_name
     * @param $mime_type
     *
     * @return bool Returns true if given mime type is equal to ending/extension
     * @throws \Exception
     */
    public static function hasSpecificFileNameExtension($file_name, $mime_type): bool
    {
        $extension = self::mapMimeTypeToFileExtension($mime_type);

        return (bool)preg_match("#\w+\.{$extension}$#", $file_name);
    }
}