<?php

namespace DruidFamiliar\QueryExecutor;

use DruidFamiliar\Exception;
use DruidFamiliar\Interfaces\IDruidQueryExecutor;
use DruidFamiliar\Interfaces\IDruidQueryGenerator;
use DruidFamiliar\Interfaces\IDruidQueryParameters;
use DruidFamiliar\Interfaces\IDruidQueryResponseHandler;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\CurlException;

/**
 * Class JSONDruidNodeDruidQueryExecutor
 * @package DruidFamiliar\QueryExecutor
 */
class JSONDruidNodeDruidQueryExecutor implements IDruidQueryExecutor
{
    private $ip;
    private $port;
    private $endpoint;
    private $protocol;

    public function __construct($ip, $port, $endpoint = '/druid/v2/', $protocol = 'http')
    {
        $this->ip       = $ip;
        $this->port     = $port;
        $this->endpoint = $endpoint;
        $this->protocol = $protocol;
    }

    public function getBaseUrl()
    {
        $baseUrl = $this->protocol . '://' . $this->ip . ':' . $this->port;
        $url     = $baseUrl . $this->endpoint;
        return $url;
    }

    public function createRequest($query)
    {
        $client = new Client();

        $request = $client->post($this->getBaseUrl(), array("content-type" => "application/json"), json_encode($query));

        return $request;
    }


    public function executeQuery(IDruidQueryGenerator $queryGenerator, IDruidQueryParameters $params, IDruidQueryResponseHandler $responseHandler)
    {
        $params->validate();

        $generatedQuery = $queryGenerator->generateQuery($params);

        // Create a POST request
        $request = $this->createRequest($generatedQuery);

        // Send the request and parse the JSON response into an array
        try
        {
            $response = $request->send();
        }
        catch(CurlException $curlException)
        {
            throw new $curlException;
        }

        $data = $this->parseResponse($response);

        $formattedResponse = $responseHandler->handleResponse($data);

        return $formattedResponse;
    }

    /**
     * @param Response $rawResponse
     *
     * @return mixed
     */
    protected function parseResponse($rawResponse)
    {
        $formattedResponse = $rawResponse->json();

        return $formattedResponse;
    }
}