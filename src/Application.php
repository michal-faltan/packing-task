<?php

namespace App;

use Doctrine\ORM\EntityManager;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use App\Entity\Packaging;
use App\Entity\ItemsPackagingCache;
use App\Service\ProductsRequestService;
use App\Model\ApplicationResponseContent;
use App\Exception\InvalidRequestException;
use App\Exception\InvalidApiResponseException;
use App\Exception\PackagingImpossibleException;

class Application
{
    private EntityManager $entityManager;

    private ProductsRequestService $productsRequestService;

    public function __construct
    (
        EntityManager $entityManager, 
        ProductsRequestService $productsRequestService
    )
    {
        $this->entityManager = $entityManager;
        $this->productsRequestService = $productsRequestService;
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        try {
            $products = $this->productsRequestService->retrieveProducts($request);
            
            $items = $this->productsRequestService->transformProductsToItems($products);
            
            $packagingsRepository = $this->entityManager->getRepository(Packaging::class);
            $packagings = $packagingsRepository->getPackagingsApiData();

            $hash = $this->productsRequestService->calculateHashForItemsAndBoxes($items, $packagings);

            $cachedResult = $this->entityManager->getRepository(ItemsPackagingCache::class)->findOneBy(['itemsAndPackagingsHash' => $hash]);
            if ($cachedResult instanceof ItemsPackagingCache)
            {
                $boxSizes = $this->productsRequestService->getCachedPackagingSizes($cachedResult);
            }
            else
            {
                $payload = $this->productsRequestService->create3DBinPackingPayload($items, $packagings);

                //TODO retry for defined number of times if failed to get answer
                //TODO fallback method if retries do not help
                $packingResponse = $this->productsRequestService->askForSmallestPackaging($payload);

                $boxSizes = $this->productsRequestService->getPackagingSizes($packingResponse->getBody());

                $this->productsRequestService->saveResultToCache($hash, $boxSizes);
            }
            
            $responseContent = new ApplicationResponseContent();
            $responseContent->setBoxData($boxSizes);
        } catch(InvalidRequestException $ire) {
            $responseContent = new ApplicationResponseContent(405);
            $responseContent->addErrorMessage($ire->getMessage());
        } catch(InvalidArgumentException $e) {
            $responseContent = new ApplicationResponseContent(400);
            $responseContent->addErrorMessage($e->getMessage());
        } catch(PackagingImpossibleException | InvalidApiResponseException $e) {
            $responseContent = new ApplicationResponseContent();
            $responseContent->addErrorMessage($e->getMessage());
            $responseContent->setFailed();
        } catch(Exception $e) {
            $responseContent = new ApplicationResponseContent(500);
            $responseContent->addErrorMessage('Internal Server Error');
        }

        $response = new Response($responseContent->getStatusCode());
        $response->getBody()->write($responseContent->getJsonEncoded());
        $response = $response->withHeader('Content-Type', 'application/json');

        return $response;
    }
}
