<?php

use App\Application;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'nok',
        'error' => 'Only POST requests are allowed.'
    ]);
    exit;
}

/** @var EntityManager $entityManager */
$entityManager = require __DIR__ . '/../src/bootstrap.php';

/*
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$headers = getallheaders();
$body = file_get_contents('php://input');

// Build Guzzle ServerRequest
$request = new ServerRequest($method, $uri, $headers, $body);
*/

$request = ServerRequest::fromGlobals();

// Run app
$app = new Application($entityManager);
$response = $app->run($request);

// Emit response
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header("$name: $value", false);
    }
}
echo $response->getBody();