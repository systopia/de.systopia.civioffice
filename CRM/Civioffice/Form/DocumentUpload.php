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
    protected $common;

    /** @var CRM_Civioffice_DocumentStore_Upload */
    protected $document_store;

    const SHARED_ID = 1;
    const PRIVATE_ID = 0;


    public function buildQuickForm()
    {
        $this->common = CRM_Utils_Request::retrieve('common', 'Boolean', $this) ?? self::PRIVATE_ID;

        $this->document_store = new CRM_Civioffice_DocumentStore_Upload($this->common);
        


        switch ($this->common) {
            case self::PRIVATE_ID:
                $this->setTitle(E::ts("Your Uploaded CiviOffice Documents"));
                break;
            case self::SHARED_ID:
                $this->setTitle(E::ts("Shared Uploaded CiviOffice Documents"));
                break;
        }


        $this->add(
            'File',
            'upload_file',
            E::ts('Upload Document'),
            null,
            false
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

        $this->assign('document_list', $this->fileList());
        $switched_number = $this->common ^ 1; // XOR the value with 1 switches it both ways 1 <--> 0
        $switch_url = CRM_Utils_System::url('civicrm/civioffice/document_upload?common=') . $switched_number;
        $this->assign('switch_contexts_url', $switch_url);

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
                'name'        => $document->getName(),
                'mime_type'   => $document->getMimeType(),
                'size'        => E::ts("%1 MB", [1 => number_format(filesize($file_path) / 1024.0 / 1024.0, 2)]),
                'upload_date' => date('Y-m-d', filectime($file_path)),
                'icon'        => CRM_Utils_File::getIconFromMimeType($document->getMimeType()),
            ];
        }
        return $list;
    }

    public function postProcess()
    {
        if (isset($this->_submitFiles['upload_file'])) {
            $upload_file = $this->_submitFiles['upload_file'];
            // move file to new destination
            $destination = $this->document_store->getFolder() . DIRECTORY_SEPARATOR . $upload_file['name'];
            move_uploaded_file($upload_file['tmp_name'], $destination);
            E::ts("Uploaded file '%1'", [1 => $upload_file['name']]);
        }
        parent::postProcess();
    }

}
