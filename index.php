<?php
// Error Handling
error_reporting(-1);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/config/config.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Model\Services\DataAccess;
use Model\Utilities\Log;

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

$app->get('/obtener_rol', \Model\Services\Manager::class . '::GetUsersByRole');

$app->get('/obtener', \Model\Services\Manager::class . '::GetAllEntities');

$app->group('/alta', function (RouteCollectorProxy $group)
{
    $group->post('/mesas', \Model\Services\Manager::class . '::CreateTable')->add(\Model\Middlewares\AuthMW::class . '::ValidateUser');
    $group->post('/usuarios', \Model\Services\Manager::class . '::CreateEmployee')->add(\Model\Middlewares\AuthMW::class . '::ValidateUser');
    $group->post('/productos', \Model\Services\Manager::class . '::CreateProduct')->add(\Model\Middlewares\AuthMW::class . '::ValidateUser');
    $group->post('/pedidos', \Model\Services\Manager::class . '::CreateOrder')->add(\Model\Middlewares\AuthMW::class . '::ValidateUser');
});

$app->put('/modificar', \Model\Services\Manager::class . '::UpdateEntity')->add(\Model\Middlewares\AuthMW::class . '::ValidateUser');

$app->run();