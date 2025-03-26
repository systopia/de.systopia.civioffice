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

use CRM_Civioffice_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Civioffice_Form_DocumentUpload extends CRM_Core_Form
{
    /** @var boolean  */
    public $common;

    /** @var CRM_Civioffice_DocumentStore_Upload */
    protected $document_store;

    const SHARED_ID = 1;
    const PRIVATE_ID = 0;

    public function preProcess()
    {
        $this->common = CRM_Utils_Request::retrieve('common', 'Boolean', $this);
        $this->assign('isTab', isset($this->common));
        $tabs = CRM_Civioffice_Form_DocumentUpload_TabHeader::build($this);
        $this->controller->_destination = CRM_Utils_System::url(
            'civicrm/civioffice/document_upload',
            'reset=1&selectedChild=' . \CRM_Civioffice_Form_DocumentUpload_TabHeader::getCurrentTab($tabs)
        );
    }

    public function buildQuickForm()
    {
        // Only build for individual tabs.
        if (isset($this->common)) {
            $this->document_store = new CRM_Civioffice_DocumentStore_Upload($this->common);

            // execute a download if requested
            if (!empty($_REQUEST['download'])) {
                $file = $this->getFilePath($_REQUEST['download']);
                if ($file) {
                    $file_content = file_get_contents($file);
                    CRM_Utils_System::download(basename($file), mime_content_type($file), $file_content);
                }
            }

            // execute a delete if requested
            if (!empty($_REQUEST['delete'])) {
                $file = $this->getFilePath($_REQUEST['delete']);
                if ($file) {
                    unlink($file);
                    CRM_Core_Session::setStatus(
                        E::ts('File "%1" has been deleted.', [1 => basename($file)]),
                        E::ts("File Deleted"),
                        'info'
                    );
                }
                CRM_Utils_System::redirect($this->controller->_destination);
            }

            $this->add(
                'File',
                'upload_file',
                E::ts('Upload Document'),
                null,
                true
            );

            $this->addButtons(
                [
                    [
                        'type' => 'submit',
                        'name' => E::ts('Upload'),
                        'isDefault' => true,
                    ],
                ]
            );

            // assign document list
            $this->assign('document_list', $this->fileList());
        }

        parent::buildQuickForm();
    }

    /**
     * Get a list of all files including attributes
     *
     */
    protected function fileList()
    {
        $list = [];
        $documents = $this->document_store->getDocuments();
        foreach ($documents as $document) {
            /** @var $document CRM_Civioffice_Document */
            $file_path = $this->document_store->getFolder() . DIRECTORY_SEPARATOR . $document->getName();
            $list[] = [
                'name'          => $document->getName(),
                'mime_type'     => $document->getMimeType(),
                'size'          => E::ts("%1 MB", [1   => number_format(filesize($file_path) / 1024.0 / 1024.0, 2)]),
                'upload_date'   => date('Y-m-d H:i:s', filectime($file_path)),
                'icon'          => CRM_Utils_File::getIconFromMimeType($document->getMimeType()),
                'delete_link'   => CRM_Utils_System::url("civicrm/civioffice/document_upload", "common={$this->common}&delete=" . base64_encode($document->getName())),
                'download_link' => CRM_Utils_System::url("civicrm/civioffice/document_upload", "common={$this->common}&download=" . base64_encode($document->getName())),
            ];
        }
        return $list;
    }

    public function postProcess()
    {
        $upload_file_infos = $this->_submitFiles['upload_file'];
        if (!empty($upload_file_infos['name'])) {
            $file_name = $upload_file_infos['name'];

            // TODO: check if file already exists?
            // check if file ends with docx
            // TODO: MIME type checks could be handled differently in the future: https://github.com/systopia/de.systopia.civioffice/issues/2
            if (!CRM_Civioffice_MimeType::hasSpecificFileNameExtension($file_name, CRM_Civioffice_MimeType::DOCX)) {
                CRM_Core_Session::setStatus(
                    E::ts("File type docx is expected."),
                    E::ts("Error"),
                    'info'
                );
            } else {
                // if file ending is correct: move file to new destination
                $destination = $this->document_store->getFolder() . DIRECTORY_SEPARATOR . $file_name;
                move_uploaded_file($upload_file_infos['tmp_name'], $destination);
                CRM_Core_Session::setStatus(
                    E::ts('File "%1" has been uploaded.', [1 => $upload_file_infos['name']]),
                    E::ts("Document Stored"),
                    'info'
                );

                // update document list
                $this->assign('document_list', $this->fileList());
            }
        }

        // TODO: Redirect for avoiding reloads with the same action (e.g. delete).

        parent::postProcess();
    }

    /**
     * Extract a full path from the base64 encoded file name
     *
     * @param string $base64_file_name
     *
     * @return string|null
     *   full file path or null if file not exists (or illegal)
     */
    protected function getFilePath($base64_file_name)
    {
        $file_name = base64_decode($base64_file_name);
        if ($file_name) {
            // make sure nobody wants to do anything outside our folder
            $file_name = basename($file_name);

            // return full path
            $full_path = $this->document_store->getFolder() . DIRECTORY_SEPARATOR . $file_name;
            if (file_exists($full_path)) {
                return $full_path;
            }
        }
        // something's wrong
        return null;
    }
}
