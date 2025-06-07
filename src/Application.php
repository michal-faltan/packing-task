<?php

namespace App;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use App\Entity\Packaging;
use GuzzleHttp\Client;

class Application
{

    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    private function parsePackingApiResponse(string $json): array 
    {
        $decoded = json_decode($json, true);
    
        if (!isset($decoded['response'])) {
            throw new \RuntimeException('Missing `response` key.');
        }
    
        $response = $decoded['response'];
    
        if (!empty($response['errors'])) {
            throw new \RuntimeException('API errors: ' . json_encode($response['errors']));
        }
    
        if (!isset($response['bins_packed']) || count($response['bins_packed']) !== 1) {
            throw new \RuntimeException('Expected exactly 1 packed bin.');
        }
    
        $binData = $response['bins_packed'][0]['bin_data'] ?? null;
    
        if (!$binData) {
            throw new \RuntimeException('Missing `bin_data`.');
        }
    
        return $binData;
    }
    

    public function run(RequestInterface $request): ResponseInterface
    {
        // your implementation entrypoint
        $username = getenv('BIN_PACKING_USERNAME');
        $api_key = getenv('BIN_PACKING_APIKEY');
        $url = getenv('BIN_PACKING_URL');

        $packagingsRepository = $this->entityManager->getRepository(Packaging::class);
        $packagings = $packagingsRepository->getPackagingsApiData();

        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);
        $items = [];
        foreach ($data['products'] as $product)
        {
            $item = [];
            $item['id'] = $product['id'];
            $item['w'] = $product['width'];
            $item['h'] = $product['height'];
            $item['d'] = $product['length'];
            $item['wg'] = $product['weight'];
            $item['q'] = 1;
            $item['vr'] = true;
            $items[] = $item;
        }

        $data = [
            'username' => $username,
            'api_key' => $api_key,
            'items' => $items,
            'bins' => $packagings,
            'params' => [
                'optimization_mode' => 'bins_number'
            ]
        ];

        $content = json_encode($data, JSON_PRETTY_PRINT);

        $client = new Client([
            'base_uri' => $url,
            'timeout'  => 2.0,
        ]);

        $packing_request = new Request(
            'POST', 
            new Uri($url), 
            ['Content-Type' => 'application/json'], 
            $content
        );

        $packing_response = $client->send($packing_request);

        $packing_response_data = $this->parsePackingApiResponse($packing_response->getBody());
        
        $response_data = [
            'status' => 'ok',
            'box' => $packing_response_data,
            'errors' => []
        ];

        $response = new Response();
        $response->getBody()->write(json_encode($response_data));
        $response = $response->withHeader('Content-Type', 'application/json');

        return $response;
    }

}
