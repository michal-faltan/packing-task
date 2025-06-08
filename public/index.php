<?php

use App\Application;
use App\Service\ProductsRequestService;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;

/** @var EntityManager $entityManager */
$entityManager = require __DIR__ . '/../src/bootstrap.php';

$productsRequestService = new ProductsRequestService($entityManager);

$request = ServerRequest::fromGlobals();

// Run app
$app = new Application($entityManager, $productsRequestService);
$response = $app->run($request);

// Emit response
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header("$name: $value", false);
    }
}
echo $response->getBody();