<?php
declare(strict_types=1);
namespace Creditea\Magento2\Service;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use GuzzleHttp\Exception\GuzzleException;
use Creditea\Magento2\Helper\Log;
use Creditea\Magento2\Helper\Data;
use Magento\Framework\Webapi\Rest\Request;

class WebApi
{
    private $responseFactory;
    private $clientFactory;

    public function __construct(
        Data $helper,
        Log $helperLog,
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory
    ) {
        $this->helper = $helper;
        $this->helperLog = $helperLog;
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
    }

    public function doRequest(string $uriEndpoint, array $params = [], string $requestMethod = Request::HTTP_METHOD_GET): Response 
    { 
        $client = $this->clientFactory->create([
            'config' => [
                'verify' => false,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type'  => 'application/json',
                    'apikey' => $this->helper->getApiKey()
                ],
                'json' => $params
            ]
        ]);

        try {

            $response = $client->request($requestMethod, $uriEndpoint, $params);

        } catch (GuzzleException $exception) {
            $response = $this->responseFactory->create([
                'status' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ]);
        }

        return $response;
    }
}