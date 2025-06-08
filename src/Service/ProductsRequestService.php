<?php

namespace App\Service;

use App\Exception\InvalidApiResponseException;
use App\Exception\InvalidRequestException;
use App\Exception\PackagingImpossibleException;
use App\Entity\ItemsPackagingCache;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Client;
use Doctrine\ORM\EntityManager;

class ProductsRequestService
{
    const HASH_METHOD = 'md5';

    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws InvalidRequestException
     */
    public function retrieveProducts(RequestInterface $request): array
    {
        if ($request->getMethod() !== 'POST')
        {
            throw new InvalidRequestException('Only POST requests are allowed');
        }
        
        $content = $request->getBody()->getContents();
        $data = json_decode($content, true);
        
        if (!isset($data['products']) || !is_array($data['products']))
        {
            throw new InvalidRequestException('Json payload should contain array of products under key \'products\'');
        }

        return $data['products'];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function transformProductsToItems(array $products): array 
    {
        $requiredKeys = ['id', 'width', 'height', 'length', 'weight'];
        $items = [];
        foreach ($products as $index => $product)
        {
            foreach ($requiredKeys as $key) {
                if (!isset($product[$key])) {
                    throw new InvalidArgumentException("Missing key '$key' in product at index $index.");
                }
            }

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

        return $items;
    }

    public function calculateHashForItemsAndBoxes(array $items, array $packagings)
    {
        $data = '';
        $pieces = [];

        foreach ($items as $item) 
        {
            $pieces[] = strval($item['h']);
            $pieces[] = strval($item['d']);
            $pieces[] = strval($item['w']);
            sort($pieces);
            $data .= implode($pieces);
            $pieces = [];
        }

        foreach ($packagings as $packaging) 
        {
            $pieces[] = strval($packaging['id']);
            $pieces[] = strval($packaging['h']);
            $pieces[] = strval($packaging['d']);
            $pieces[] = strval($packaging['w']);
            sort($pieces);
            $data .= implode($pieces);
            $pieces = [];
        }

        return hash(self::HASH_METHOD, $data);
    }

    public function create3DBinPackingPayload(array $items, array $packagings) : string
    {
        $username = getenv('BIN_PACKING_USERNAME');
        $api_key = getenv('BIN_PACKING_APIKEY');

        $data = [
            'username' => $username,
            'api_key' => $api_key,
            'items' => $items,
            'bins' => $packagings,
            'params' => [
                'optimization_mode' => 'bins_number'
            ]
        ];

        return json_encode($data);
    }

    public function askForSmallestPackaging(string $payload): Response
    {
        //TODO reuse client
        //TODO timeout as constant
        
        $url = getenv('BIN_PACKING_URL');

        $client = new Client([
            'base_uri' => $url,
            'timeout'  => 2.0,
        ]);

        $packingRequest = new Request(
            'POST', 
            new Uri($url), 
            ['Content-Type' => 'application/json'], 
            $payload
        );

        $packingResponse = $client->send($packingRequest);

        return $packingResponse;
    }

    /**
     * @throws PackagingImpossibleException
     * @throws InvalidApiResponseException
     */
    public function getPackagingSizes(string $json): array 
    {
        $decoded = json_decode($json, true);
    
        if (!isset($decoded['response'])) 
        {
            throw new InvalidApiResponseException('Problem calculating package size');
        }
    
        $response = $decoded['response'];
    
        if (!empty($response['errors'])) 
        {
            //TODO log 'API errors: ' . json_encode($response['errors'])
            throw new PackagingImpossibleException('Packaging not possible');
        }
    
        if (!isset($response['bins_packed'])) 
        {
            throw new InvalidApiResponseException('Problem calculating package size');
        }

        if (count($response['bins_packed']) !== 1) 
        {
            throw new PackagingImpossibleException('Packaging possible only using multiple packages');
        }
    
        $binData = $response['bins_packed'][0]['bin_data'] ?? null;
    
        if (!$binData) 
        {
            throw new InvalidApiResponseException('Problem calculating package size');
        }

        //TODO transform data, so it only contains properties from Packaging entity
    
        return $binData;
    }

    public function getCachedPackagingSizes(ItemsPackagingCache $cachedResult) : array
    {   
        return $cachedResult->getBoxDetailsAsArray();
    }

    public function saveResultToCache(string $hash, array $boxDimensions) : void
    {
        $entry = new ItemsPackagingCache(
            $hash,
            $boxDimensions['w'],
            $boxDimensions['h'],
            $boxDimensions['d'],
            $boxDimensions['id'],
        );

        $this->entityManager->persist($entry);

        $this->entityManager->flush();
    }
}