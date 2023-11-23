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

$app->post('/login', \Model\Services\LoginManager::class . '::LogIn');

$app->group('/obtener', function (RouteCollectorProxy $group)
{
    $group->get('/entidades', \Model\Services\Manager::class . '::GetAllEntities')->add(\Model\Middlewares\AuthMW::class . '::WardSocio');
    $group->get('/rol', \Model\Services\Manager::class . '::GetUsersByRole')->add(\Model\Middlewares\AuthMW::class . '::WardGrupo');; // Devuelve todos los usuarios por rol
    $group->get('/mesas', \Model\Services\Manager::class . '::GetTables')->add(\Model\Middlewares\AuthMW::class . '::WardGrupo');; // Devuelve todos los usuarios por rol
    $group->get('/productos', \Model\Services\Manager::class . '::GetProducts')->add(\Model\Middlewares\AuthMW::class . '::WardGrupo');; // Devuelve todos los usuarios por rol
    $group->get('/ordenes_codigo', \Model\Services\Manager::class . '::GetOrdersByCode'); // Devuelve todas las ordenes de un mismo codigo, tipo AAAA1
    $group->get('/ordenes_todas', \Model\Services\Manager::class . '::GetAllOrders')->add(\Model\Middlewares\AuthMW::class . '::WardMozo');; // Devuelve todas las ordenes visibles para el tipo de usuario.
});

$app->group('/alta', function (RouteCollectorProxy $group)
{
    $group->post('/mesas', \Model\Services\Manager::class . '::CreateTable')->add(\Model\Middlewares\AuthMW::class . '::ValidateUser');
    $group->post('/usuarios', \Model\Services\Manager::class . '::CreateEmployee')->add(\Model\Middlewares\AuthMW::class . '::ValidateUser');
    $group->post('/productos', \Model\Services\Manager::class . '::CreateProduct')->add(\Model\Middlewares\AuthMW::class . '::ValidateUser');
    $group->post('/pedidos', \Model\Services\Manager::class . '::CreateOrder')->add(\Model\Middlewares\AuthMW::class . '::ValidateUser');
});

$app->group('/modificar', function (RouteCollectorProxy $group)
{
    $group->put('/entidad', \Model\Services\Manager::class . '::UpdateEntity')->add(\Model\Middlewares\AuthMW::class . '::ValidateUser')->add(\Model\Middlewares\AuthMW::class . '::WardSocio');
    $group->put('/orden', \Model\Services\Manager::class . '::UpdateOrder')->add(\Model\Middlewares\AuthMW::class . '::ValidateUser')->add(\Model\Middlewares\AuthMW::class . '::ValidateOrderModificationAction');
    $group->put('/mesa', \Model\Services\Manager::class . '::UpdateTable')->add(\Model\Middlewares\AuthMW::class . '::ValidateUser');
    $group->put('/producto', \Model\Services\Manager::class . '::UpdateProduct')->add(\Model\Middlewares\AuthMW::class . '::ValidateUser');
});

$app->run();