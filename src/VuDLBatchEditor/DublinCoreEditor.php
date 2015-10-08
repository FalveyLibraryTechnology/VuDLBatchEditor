<?php
/**
 * Class for modifying Dublin Core data streams.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2014.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuDL
 * @package  Editor
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */
namespace VuDLBatchEditor;

/**
 * Class for modifying Dublin Core data streams.
 *
 * @category VuDL
 * @package  Editor
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */
class DublinCoreEditor
{
    /**
     * The Dublin Core XML namespace.
     *
     * @var string
     */
    protected $ns = 'http://purl.org/dc/elements/1.1/';

    /**
     * The XML being modified.
     *
     * @var \SimpleXMLElement
     */
    protected $xml;

    /**
     * Constructor
     *
     * @param string $xml Raw XML
     */
    public function __construct($xml)
    {
        $this->xml = simplexml_load_string($xml);
    }

    /**
     * Add a new element to the document.
     *
     * @param string $field Name of field to add
     * @param string $value Value to place in field
     *
     * @return void
     */
    public function addChild($field, $value)
    {
        $this->xml->addChild($field, $value, $this->ns);
    }

    /**
     * Retrieve an XML representation of the document.
     *
     * @return string
     */
    public function getXml()
    {
        return $this->xml->asXml();
    }
}
