<?php

namespace Model\Services;

use Model\Services\DataAccess;
use Model\Utilities\CodeGenerator;
use Model\Utilities\Log;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Manager
{
    /////////////////////////////////////////////////////////////
    #region - - - PUBLIC
    public static function GetAllEntities($request, $response)
    {
        $type = $_GET['entidad'];
        $data = DataAccess::Select($type);
        
        if($type == 'usuarios')
        {
            $aux = DataAccess::Select('tipo_usuario');
            foreach($data as &$bit)
            {
                foreach($aux as $au)
                {
                    if($au['codigo'] == $bit['tipo'])
                    {
                        $bit['tipo'] = $au['tipo'];
                    }
                }
            }
        }

        return self::ReturnResponse($request, $response, $data);
    }

    public static function GetUsersByRole($request, $response)
    {
        $data = DataAccess::SelectWithJoin(
            'usuarios', // tabla 1
            'tipo_usuario', // tabla 2
            'tipo', // col de tabla 1
            'codigo', // col de tabla 2
            'tipo', $_GET['buscar'], // donde col de tabla 2 = 'buscar'
            ['usuarios.user', 'tipo_usuario.tipo', 'usuarios.alta', 'usuarios.baja'] // datos a traer
        );
        return self::ReturnResponse($request, $response, $data ? $data : "No se encontraron usuarios de ese tipo");
    }

    public static function UpdateEntity($request, $response)
    {
        $_PUT = file_get_contents("php://input");        
        $_PUT = json_decode($_PUT, true);
        $table = $_PUT['objeto'];
        if(isset($_PUT['col']))
        {
            $columns = $_PUT['col'];
        }
        if(isset($_PUT['val']))
        {
            $values = $_PUT['val'];
        }

        $data = DataAccess::Update($table, $columns, $values, $_PUT['where'], $_PUT['value']);
        return self::ReturnResponse($request, $response, $data ? "Actualizacion exitosa." : "Error en la actualizacion.");
    }
    #endregion
    /////////////////////////////////////////////////////////////
    #region - - - PRIVATE
    private static function ReturnResponse($request, $response, $payload)
    {
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private static function GetID($table)
    {
        $id = 0;
        $lastID = DataAccess::SelectLast($table, 'id');
        
        if($lastID)
        {
            $id = $lastID;
        }
        return $id;
    }

    public static function CreateEmployee($request, $response)
    {
        $params = $request->getParsedBody();
        $table = 'usuarios';
        $columns = $params['col'];
        $values = $params['val'];
        $id = self::GetID($table);
        $id += 1;

        $date = date("Y-m-d");

        array_push($columns, 'id', 'alta');
        array_push($values, $id, $date);

        if(count(array_diff(ENTITIES['User'], $columns)) == 0) // Comparo si al menos los elementos obligatorios estan dentro de los parametros.
        {
            $data = DataAccess::Insert($table, $columns, $values);
            return self::ReturnResponse($request, $response, $data ? "Entidad creada con éxito." : "Error en la interacción con la base de datos");
        }
        else
        {            
            return self::ReturnResponse($request, $response, "Error interno.");
        }
    }

    public static function CreateTable($request, $response)
    {
        $table = 'mesas';
        $id = self::GetID($table);
        $id += 1;
        $columns = [];
        $values = [];

        $tableCodes = DataAccess::Select('mesas', 'codigo_mesa');
        $code = '';
        if($tableCodes)
        {
            $set = true;
            do
            {
                $code = CodeGenerator::RandomAlphaNumCode();
                foreach($tableCodes as $tc)
                {
                    if($tc == $code)
                    {
                        $set = false;
                        break;
                    }
                }
            }while($set == false);
        }
        else
        {
            $code = CodeGenerator::RandomAlphaNumCode();
        }

        array_push($columns, 'id', 'codigo_mesa');
        array_push($values, $id, $code);        

        if(count(array_diff(ENTITIES['Table'], $columns)) == 0)
        {
            $data = DataAccess::Insert($table, $columns, $values);
            return self::ReturnResponse($request, $response, $data ? "Entidad creada con éxito." : "Error en la interacción con la base de datos");
        }
        else
        {
            return self::ReturnResponse($request, $response, "Error interno.");
        }
    }

    public static function CreateProduct($request, $response)
    {
        $params = $request->getParsedBody();
        $table = 'productos';
        $columns = $params['col'];
        $values = $params['val'];
        $id = self::GetID($table);
        $id += 1;

        $date = date("Y-m-d");
        array_push($columns, 'id', 'fechaAlta');
        array_push($values, $id, $date);

        if(count(array_diff(ENTITIES['Product'], $columns)) == 0)
        {
            $data = DataAccess::Insert($table, $columns, $values);
            return self::ReturnResponse($request, $response, $data ? "Entidad creada con éxito." : "Error en la interacción con la base de datos");
        }
        else
        {
            return self::ReturnResponse($request, $response, "Error interno.");
        }
    }

    public static function CreateOrder($request, $response)
    {
        $params = $request->getParsedBody();
        $table = 'pedidos';
        $columns = $params['col'];
        $values = $params['val'];
        $id = self::GetID($table);
        $id += 1;

        $currentCode = DataAccess::SelectLast('pedidos', 'codigoPedido');
        if($currentCode)
        {
            $code = CodeGenerator::RandomSequentialAlphaNumCode($currentCode);
        }
        else
        {
            $code = 'AAAA1';
        }

        $date = date("Y-m-d H:i:s");

        array_push($columns, 'id', 'fecha','codigoPedido');
        array_push($values, $id, $date, $code);
        
        if(count(array_diff(ENTITIES['Order'], $columns)) == 0)
        {
            $data = DataAccess::Insert($table, $columns, $values);
            return self::ReturnResponse($request, $response, $data ? "Entidad creada con éxito." : "Error en la interacción con la base de datos");
        }
        else
        {
            return self::ReturnResponse($request, $response, "Error interno.");
        }
    }
    #endregion
}