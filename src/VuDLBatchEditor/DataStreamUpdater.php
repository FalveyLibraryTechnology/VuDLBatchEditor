<?php
namespace VuDLBatchEditor;

class DataStreamUpdater
{
    protected $solrUrl;
    protected $solrRowLimit = 100000;    // how many rows to retrieve from Solr
    protected $fedoraUrl;

    public function __construct($solrUrl, $fedoraUrl)
    {
        $this->solrUrl = $solrUrl;
        $this->fedoraUrl = $fedoraUrl;
    }

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

    protected function getStreamUrl($id, $dataStream)
    {
        return $this->fedoraUrl . '/objects/' . $id . '/datastreams/' . $dataStream;
    }

    protected function getStream($id, $dataStream)
    {
        $contents = file_get_contents($this->getStreamUrl($id, $dataStream) . '/content');
        if (!$contents) {
            throw new \Exception('No ' . $dataStream . ' stream on ' . $id);
        }
        return $contents;
    }

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

    protected function processIds($ids, $dataStream, $editCallback)
    {
        foreach ($ids as $id) {
            $streamContents = $this->getStream($id, $dataStream);
            $transformedContents = $editCallback($id, $streamContents);
            $this->setStream($id, $dataStream, $transformedContents);
        }
    }

    public function run($query, $dataStream, $editCallback)
    {
        $ids = $this->getIdsFromSolr($query);
        $this->processIds($ids, $dataStream, $editCallback);
    }
}
