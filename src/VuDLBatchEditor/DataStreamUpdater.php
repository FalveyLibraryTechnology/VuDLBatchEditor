<?php
/**
 * Class for updating Fedora data streams within a Solr result set.
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
 * Class for updating Fedora data streams within a Solr result set.
 *
 * @category VuDL
 * @package  Editor
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */
class DataStreamUpdater
{
    /**
     * Base URL for Solr.
     *
     * @var string
     */
    protected $solrUrl;

    /**
     * Number of IDs to retrieve from Solr in a single request.
     * @var int
     */
    protected $solrRowLimit = 100000;

    /**
     * Base URL for Fedora.
     *
     * @var string
     */
    protected $fedoraUrl;

    /**
     * Constructor
     *
     * @param string $solrUrl   Base URL for Solr.
     * @param string $fedoraUrl Base URL for Fedora.
     */
    public function __construct($solrUrl, $fedoraUrl)
    {
        $this->solrUrl = $solrUrl;
        $this->fedoraUrl = $fedoraUrl;
    }

    /**
     * Retrieve IDs from Solr in response to a query.
     *
     * @param string $query Solr query to execute.
     *
     * @return array
     * @throws \Exception
     */
    protected function getIdsFromSolr($query)
    {
        $url = $this->solrUrl . '?q=' . urlencode($query) . '&fl=id&rows='
            . $this->solrRowLimit . '&wt=json';
        $json = file_get_contents($url);
        $data = json_decode($json);
        if (!isset($data->response->docs)) {
            throw new \Exception('Problem with Solr results.');
        }
        if (count($data->response->docs) == $this->solrRowLimit) {
            throw new \Exception(
                'Too many records; restrict query or raise row limit'
            );
        }
        return array_map(function ($i) { return $i->id; }, $data->response->docs);
    }

    /**
     * Construct the URL to a Fedora datastream.
     *
     * @param string $id         Object identifier
     * @param string $dataStream Stream name
     *
     * @return string
     */
    protected function getStreamUrl($id, $dataStream)
    {
        return $this->fedoraUrl . '/objects/' . $id . '/datastreams/' . $dataStream;
    }

    /**
     * Retrieve the contents of a Fedora datastream.
     *
     * @param string $id         Object identifier
     * @param string $dataStream Stream name
     *
     * @return string
     * @throws \Exception
     */
    protected function getStream($id, $dataStream)
    {
        $contents = file_get_contents($this->getStreamUrl($id, $dataStream) . '/content');
        if (!$contents) {
            throw new \Exception('No ' . $dataStream . ' stream on ' . $id);
        }
        return $contents;
    }

    /**
     * Set the contents of a Fedora datastream.
     *
     * @param string $id         Object identifier
     * @param string $dataStream Stream name
     * @param string $contents   Contents to set
     *
     * @return void
     * @throws \Exception
     */
    protected function setStream($id, $dataStream, $contents)
    {
        $client = new \Zend\Http\Client();
        $url = $this->getStreamUrl($id, $dataStream) . '?mimeType=application/xml';
        $client->setUri($url);
        $client->setHeaders(['Content-Type' => 'application/xml']);
        $client->setMethod(\Zend\Http\Request::METHOD_PUT);
        $client->setRawBody($contents);
        $response = $client->send();
        if (!$response || !$response->isSuccess()) {
            throw new \Exception(
                'Error ' . $response->getStatusCode()
                . ' PUT-ing ' . $dataStream . ' to ' . $url
            );
        }
    }

    /**
     * Apply $editCallback (a callback function accepting an object ID and
     * the contents of a Fedora datastream identified by $dataStream, returning
     * a modified version of that data) to all of the objects identified in the
     * $ids array.
     *
     * @param array    $ids          Object identifier
     * @param string   $dataStream   Stream name
     * @param Callable $editCallback Data modification callback
     *
     * @return void
     */
    protected function processIds($ids, $dataStream, $editCallback)
    {
        foreach ($ids as $id) {
            $streamContents = $this->getStream($id, $dataStream);
            $transformedContents = $editCallback($id, $streamContents);
            $this->setStream($id, $dataStream, $transformedContents);
        }
    }

    /**
     * Apply $editCallback to all Fedora datastreams matchin $dataStream within
     * the results of Solr query $query. See processIds() above for details on
     * the way $editCallback is used.
     *
     * @param string   $query        Solr query
     * @param string   $dataStream   Stream name
     * @param Callable $editCallback Data modification callback
     */
    public function run($query, $dataStream, $editCallback)
    {
        $ids = $this->getIdsFromSolr($query);
        $this->processIds($ids, $dataStream, $editCallback);
    }
}
