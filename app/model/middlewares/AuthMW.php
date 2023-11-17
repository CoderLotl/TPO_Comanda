<?php
namespace Model\Middlewares;

use Model\Services\DataAccess;
use Model\Services\AuthJWT;
use Exception;
use Model\Utilities\Log;

class AuthMW
{
    public static function ValidateUser($request, $handler)    
    {
        if($_SERVER['REQUEST_METHOD'] != 'PUT')
        {
            $action = $_REQUEST['accion'];
            $object = $_REQUEST['objeto'];            
            $token = $_REQUEST['token'];
            $_req = $_REQUEST;
        }
        else
        {
            $_PUT = file_get_contents("php://input");        
            $_PUT = json_decode($_PUT, true);
            $_req = $_PUT;
            $action = $_PUT['accion'];
            $object = $_PUT['objeto'];            
            $token = $_PUT['token'];
        }
        
        $userType = AuthJWT::GetData($token)->rol;            
        
        if( self::ValidateAction($action, $object, $userType, $_req) )
        {
            return $handler->handle($request);
        }

        throw new Exception('Este usuario no tiene este derecho.');        
    }

    public static function GetRole($token)
    {
        $userType = AuthJWT::GetData($token)->rol;
        return $userType;
    }

    public static function ValidateAction($action, $object, $userType, $_req)
    {           
        
        if( USER_RIGHTS[$userType] == '*' ) // Si el usuario tiene todos los derechos ...
        {
            return true;
        }
        elseif( isset(USER_RIGHTS[$userType][$action]) && isset(USER_RIGHTS[$userType][$action][$object]) ) // ... o si tiene el derecho, y tiene el derecho sobre ese tipo de objetos ...
        {
            if( $action == 'modificar' )
            {
                $state = $_req['estado']; // Obtengo el valor del estado que el usuario quiere setear
                if( !in_array($state, USER_RIGHTS[$userType][$action][$object]) )// Chequeo si el usuario puede hacer el cambio de estado con el valor que quiere pasar
                {
                    return false;
                }                
            }
            return true;
        }
        
        return false;
    }

    public static function ValidateOrderModificationAction($request, $handler)
    {
        $params = $request->getParsedBody();        
        $id = $params['id'];
        $role = self::GetRole($params['token']);
        $allowedType = '*';
        $orderType = DataAccess::SelectWhere('pedidos', ['tipoProducto'], ['id'], [$id])[0]['tipoProducto'];
        
        switch($role)
        {
            case 'cervecero':
                $allowedType = 'cerveza';
                break;
            case 'bartender':
                $allowedType = 'bebida';
                break;
            case 'cocinero':
                $allowedType = 'comida';
                break;
        }        

        if($allowedType == '*' || ($allowedType == $orderType))
        {
            return $handler->handle($request);
        }

        throw new Exception('Este usuario no tiene este derecho.');
    }
}