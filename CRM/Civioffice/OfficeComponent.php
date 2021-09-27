<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviOffice Integration                        |
| Copyright (C) 2020 SYSTOPIA                            |
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
 * abstract CiviOffice component
 */
abstract class CRM_Civioffice_OfficeComponent
{
    /** @var string component uri */
    protected $uri;

    /** @var string component name */
    protected $name;

    protected function __construct($uri, $name)
    {
        $this->uri = $uri;
        $this->name = $name;
    }

    /**
     * Get a list of document mime types supported by this component
     *
     * @return array
     *   list of mime types as strings
     */
    abstract public function getSupportedMimeTypes() : array;


    /**
     * Get the URL to configure this component
     *
     * @return string
     *   URL
     */
    abstract public function getConfigPageURL() : string;

    /**
     * Is this component ready, i.e. properly
     *   configured and connected
     *
     * @return boolean
     *   URL
     */
    abstract public function isReady() : bool;

    /**
     * Get the generic component ID
     *
     * @return string
     *   ID
     */
    public function getURI(): string
    {
        return $this->uri;
    }

    /**
     * Get the (localised) component name
     *
     * @return string
     *   name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the (localised) component description
     *
     * @return string
     *   name
     */
    public function getDescription(): string
    {
        return '- empty -';
    }

}
