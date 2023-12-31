<?php
use Model\Services\DataAccess;
use Model\Utilities\ConfigLoader;

define('APP_ROOT', dirname(dirname(__FILE__)));

// Loading DB Connection config params
$file = file(APP_ROOT . '/config/db.txt');
$host = trim(explode(':', $file[0])[1]);
$db = trim(explode(':', $file[1])[1]);
$username = trim(explode(':', $file[2])[1]);
$password = trim(explode(':', $file[3])[1]);

DataAccess::$pdo = new PDO("mysql:host=$host;dbname=$db", $username, $password);
DataAccess::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Current Region Time
date_default_timezone_set('America/Argentina/Buenos_Aires');

define('ENTITIES',
[
    'User' =>       ['id', 'tipo', 'user', 'password'],
    'Product' =>    ['id', 'descripcion', 'tipo', 'precio', 'fechaAlta'],
    'Order' =>      ['id', 'codigoPedido', 'idMesa', 'idProducto', 'cantidadProducto', 'nombreCliente', 'estado', 'fecha'],
    'Table' =>      ['id', 'codigo_mesa'],
]);

define('USER_RIGHTS',
[
    'bartender' =>  [
                        'modificar' => ['pedidos' => [2, 3]]
                    ],
	'cervecero' =>  [
                        'modificar' => ['pedidos' => [2, 3]]
                    ],
	'cocinero' =>   [
                        'modificar' => ['pedidos' => [2, 3]]
                    ],
	'mozo' =>       [
                        'alta' => ['pedidos', 'foto_pedido'],
                        'modificar' => ['pedidos' => [1, 4], 'mesas' => [1, 2, 3, 4, 5]],
                        'baja' => ['pedidos']
                    ],
	'socio' => '*',
]);