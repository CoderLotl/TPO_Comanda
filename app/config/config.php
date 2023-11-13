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
    'Table' =>      ['id', 'codigoMesa'],
]);

define('USER_RIGHTS',
[
    'bartender' =>  [
                        'modificar' => ['pedidos']
                    ],
	'cervecero' =>  [
                        'modificar' => ['pedidos']
                    ],
	'cocinero' =>   [
                        'modificar' => ['pedidos']
                    ],
	'mozo' =>       [
                        'alta' => ['pedidos'],
                        'modificar' => ['pedidos'],
                        'baja' => ['pedidos']
                    ],
	'socio' => '*',
]);