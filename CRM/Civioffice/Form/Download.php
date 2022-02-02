<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: J. Franz (franz@systopia.de)                 |
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

/**
 * Page to download files and go back to the result
 */
class CRM_Civioffice_Form_Download extends CRM_Core_Form {

    /** @var string the tmp folder holding the files */
    public $tmp_folder;

    /** @var string the URL to return to */
    public $return_url;

    /** @var bool start download instantly */
    public $instant_download;

    public function buildQuickForm()
    {
        $this->tmp_folder = CRM_Utils_Request::retrieve('tmp_folder', 'String', $this);
        $this->return_url = CRM_Utils_Request::retrieve('return_url', 'String', $this);
        $this->instant_download = CRM_Utils_Request::retrieve('instant_download', 'Boolean', $this);

        if ($this->instant_download) {
            $this->zipIfNeededAndDownload($this->tmp_folder);
            $this->removeFilesAndFolder($this->tmp_folder);
            CRM_Utils_System::civiExit();
        }

        $this->setTitle(E::ts("CiviOffice - Download"));
        $this->addButtons(
            [
                [
                    'type' => 'submit',
                    'name' => E::ts('Download'),
                    'icon' => 'fa-download',
                    'isDefault' => true,
                ],
                [
                    'type' => 'done',
                    'name' => E::ts('Back to previous page'),
                    'isDefault' => false,
                ],
            ]
        );
        Civi::log()->debug("CiviOffice: Zip page loaded");

        parent::buildQuickForm();
    }

    public function postProcess()
    {
        // this means somebody clicked download
        $vars = $this->exportValues();
        if (isset($vars['_qf_Download_submit'])) {
            $this->zipIfNeededAndDownload($this->tmp_folder);
        } else if (isset($vars['_qf_Download_done'])) {
            $this->removeFilesAndFolder($this->tmp_folder);
            // go back
            CRM_Utils_System::redirect(base64_decode($this->return_url));
        }

        parent::postProcess();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function zipIfNeededAndDownload(string $folder_path): void
    {
        // TODO: verify folder
        if (!preg_match('#civioffice_\w+$#', $folder_path)) {
            throw new Exception("Illegal path!");
        }

        // download files
        try {
            $glob = glob($folder_path . '/' . '*');
            $number_of_files = count($glob);

            if($number_of_files == 1) {
                // do not zip if single file
                $files = scandir($folder_path);
                $file_name = $files[2];
                $file_path_name = $folder_path . DIRECTORY_SEPARATOR . $file_name;
                $mime_type = mime_content_type($file_path_name);

            } else {
                // create ZIP file
                $file_path_name = $folder_path . DIRECTORY_SEPARATOR . 'all.zip';

                // first: try with command line tool to avoid memory issues
                $has_error = 0;
                try {
                    $output = null;
                    // todo save name identifier at a central place
                    $pattern = "Document-*.*"; // todo: Check if wildcard use is okay here
                    $command = "cd {$folder_path} && zip all.zip {$pattern}";
                    Civi::log()->debug("CiviOffice: Executing '{$command}' to zip generated files...");
                    $timestamp = microtime(true);
                    exec($command, $output, $has_error);
                    $runtime = microtime(true) - $timestamp;
                    Civi::log()->debug("CiviOffice: Zip command took {$runtime}s");
                } catch (Exception $ex) {
                    $has_error = 1;
                }

                // if this didn't work, use PHP (memory intensive)
                if ($has_error || !file_exists($file_path_name)) {
                    // this didn't work, use the
                    $zip = new ZipArchive();
                    $zip->open($file_path_name, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);

                    // add all Document-X.* files
                    foreach (scandir($folder_path) as $file) {
                        // todo save name identifier at a central place
                        $pattern = E::ts("Document-%1.*", [1 => '[0-9]+']); // todo: Check if wildcard use is okay here
                        if (preg_match("/{$pattern}/", $file)) {
                            $zip->addFile($folder_path . DIRECTORY_SEPARATOR . $file, $file);
                        }
                    }
                    $zip->close();
                }
                $mime_type = CRM_Civioffice_MimeType::ZIP;
                $file_name = E::ts('CiviOffice Documents.zip');
            }

            // trigger download
            if (file_exists($file_path_name)) {
                // set file metadata
                header("Content-Type: $mime_type");
                header("Content-Disposition: attachment; filename=" . $file_name);
                header('Content-Length: ' . filesize($file_path_name));

                // dump file contents in stream and exit
                // caution: big files need to be treated carefully, to not cause out of memory errors
                // based on: https://zinoui.com/blog/download-large-files-with-php

                // first: disable output buffering
                if (ob_get_level()) {
                    ob_end_clean();
                }

                // then read the file chunk by chunk and write to output (echo)
                $chunkSize = 16 * 1024 * 1024; // 16 MB chunks
                $handle = fopen($file_path_name, 'rb');
                while (!feof($handle)) {
                    $buffer = fread($handle, $chunkSize);
                    echo $buffer;
                    ob_flush();
                    flush();
                }
                fclose($handle);

                // delete file
                unlink($file_path_name);

                // we're done
                // CRM_Utils_System::civiExit(); // fixme: needed?
            } else {
                // this file really should exist...
                throw new Exception(E::ts("File couldn't be generated. Contact the author."));
            }
        } catch (Exception $ex) {
            CRM_Core_Session::setStatus(
                E::ts("Error downloading files: %1", [1 => $ex->getMessage()]),
                E::ts("Download Error"),
                'error'
            );
        }
    }

    private function removeFilesAndFolder(string $folder_path): void
    {
        // delete tmp folder
        foreach (scandir($folder_path) as $file) {
            if ($file != '.' && $file != '..') {
                unlink($folder_path . DIRECTORY_SEPARATOR . $file);
            }
        }
        rmdir($folder_path);
    }
}
