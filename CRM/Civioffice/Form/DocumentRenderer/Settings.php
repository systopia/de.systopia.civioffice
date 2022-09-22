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
class CRM_Civioffice_Form_DocumentRenderer_Settings extends CRM_Core_Form {

    protected CRM_Civioffice_DocumentRenderer $documentRenderer;

    protected CRM_Civioffice_DocumentRendererType $documentRendererType;

    /**
     * @return \CRM_Civioffice_DocumentRenderer
     */
    public function getDocumentRenderer(): ?CRM_Civioffice_DocumentRenderer
    {
        return $this->documentRenderer ?? null;
    }

    /**
     * @return \CRM_Civioffice_DocumentRendererType
     */
    public function getDocumentRendererType(): CRM_Civioffice_DocumentRendererType
    {
        return $this->documentRendererType;
    }

    public function preProcess()
    {
        if ($uri = CRM_Utils_Request::retrieve('id', 'Alphanumeric')) {
            $this->documentRenderer = CRM_Civioffice_DocumentRenderer::load($uri);
            $this->documentRendererType = $this->documentRenderer->getType();
        }
        else {
            $this->documentRendererType = CRM_Civioffice_DocumentRendererType::create(
                CRM_Utils_Request::retrieve('type', 'Alphanumeric', $this)
            );
        }
    }

    public function buildQuickForm()
    {
        $this->add(
            'text',
            'name',
            E::ts('Name'),
            ['class' => 'huge'],
            true
        );
        if (isset($this->documentRenderer)) {
            $this->setDefaults(
                [
                    'name' => $this->documentRenderer->getName(),
                ]
            );
        }

        $this->documentRendererType->buildsettingsForm($this);
        $this->assign('rendererTypeSettingsTemplate', $this->documentRendererType::getSettingsFormTemplate());

        $this->addButtons(
            [
                [
                    'type' => 'submit',
                    'name' => E::ts('Save'),
                    'isDefault' => true,
                ],
            ]
        );

        parent::buildQuickForm();
    }

    /**
     * Validate input data
     * This method is executed before postProcess()
     * @return bool
     */
    public function validate(): bool
    {
        parent::validate();

        $this->documentRendererType->validateSettingsForm($this);

        return (count($this->_errors) == 0);
    }

  public function postProcess() {
      $values = $this->exportValues();

      if (!isset($this->documentRenderer)) {
          $this->documentRenderer = new CRM_Civioffice_DocumentRenderer(
              $this->documentRendererType::getNextUri(),
              $values['name'],
              [
                  'type' => $this->documentRendererType->getURI(),
              ]
          );
      }

      $this->documentRendererType->postProcessSettingsForm($this);

      $this->documentRenderer->save();
  }
}
