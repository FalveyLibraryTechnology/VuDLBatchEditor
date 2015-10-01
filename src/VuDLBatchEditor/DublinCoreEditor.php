<?php
namespace VuDLBatchEditor;

class DublinCoreEditor
{
	protected $ns = 'http://purl.org/dc/elements/1.1/';
	protected $xml;

	public function __construct($xml)
	{
		$this->xml = simplexml_load_string($xml);
	}

	public function addChild($field, $value)
	{
		$this->xml->addChild($field, $value, $this->ns);
	}

    public function getXml()
	{
		return $this->xml->asXml();
	}
}
