<?php
// Error Handling
error_reporting(-1);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/config/config.php';

use Model\Services\DataAccess;
use Model\Services\Manager;
use Model\Utilities\Log;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;


// Instantiate App
$app = AppFactory::create();

// Add error middleware
$errorMiddleware = function ($request, $exception, $displayErrorDetails) use ($app)
{
    $statusCode = 500;
    $errorMessage = $exception->getMessage();
    $response = $app->getResponseFactory()->createResponse($statusCode);
    $response->getBody()->write(json_encode(['error' => $errorMessage]));
  
    return $response->withHeader('Content-Type', 'application/json');
};

// Add error middleware
$app->addErrorMiddleware(true, true, true)->setDefaultErrorHandler($errorMiddleware);;

// Add parse body
$app->addBodyParsingMiddleware();

/////////////////////////////////////////////////////////////
#region - - - SERVER - - -
// Instantiate App
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add parse body
$app->addBodyParsingMiddleware();
#endregion

/////////////////////////////////////////////////////////////
#region - - - TEST ROUTES - - -
$app->get('[/]', function (Request $request, Response $response)
{
    $payload = json_encode(array('method' => 'GET', 'msg' => "GET base funcionando."));
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/test', function (Request $request, Response $response)
{    
    $payload = json_encode(array('method' => 'GET', 'msg' => "GET /test funcionando (archivo .htacces presente)."));
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');    
});
#endregion

/////////////////////////////////////////////////////////////
#region - - - ABM ROUTES - - -
$app->get('/obtener_rol', function (Request $request, Response $response)
{
    $data = Manager::GetUsersByRole();
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json'); 
});

$app->get('/obtener', function (Request $request, Response $response)
{
    $data = Manager::GetAllEntities($_GET['entidad']);
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/alta', function (Request $request, Response $response)
{
    $data = Manager::CreateEntity();
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();