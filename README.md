VuDLBatchEditor
===============

Introduction
------------
This is a simple framework for applying changes to a set of Fedora datastreams
using the results of a Solr query.


Installation
------------
The recommended method for incorporating this library into your project is to use
Composer (http://getcomposer.org).


Basic Usage
-----------
The VuDLBatchEditor\DataStreamUpdater performs a Solr query and runs a callback
function to modify Fedora datastreams in the objects corresponding with that
query.

The VuDLBatchEditor\DublinCoreEditor provides convenience methods for modifying
Dublin Core XML datastreams.


Example
-------
```php
<?php
require 'vendor/autoload.php';
use VuDLBatchEditor\DataStreamUpdater;
use VuDLBatchEditor\DublinCoreEditor;

$solr = 'http://my-server:my-solr-port/solr/my-core/select';
$fedora = 'http://my-username:my-password@my-server:my-fedora-port/fedora';

// Query to find all records with a missing format value:
$query = '-format:*';

// Callback function to add the missing format value:
$callback = function ($id, $record) {
    $editor = new DublinCoreEditor($record);
    $editor->addChild('format', 'MyMissingFormat');
    return $editor->getXml();
};

// Perform the update:
$updater = new DataStreamUpdater($solr, $fedora);
$updater->run($query, 'DC', $callback);
```