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
        else if( in_array($action, USER_RIGHTS[$userType]) && in_array($object, USER_RIGHTS[$userType][$action]) ) // ... o si tiene el derecho, y tiene el derecho sobre ese tipo de objetos ...
        {
            if( $action == 'modificar' )
            {
                $state = $_req['val'][array_search('estado', $_req['col'])];
                if( !in_array($state, USER_RIGHTS[$userType][$action][$object]) )
                {
                    return false;
                }                
            }
            return true;
        }
        
        return false;
    }
}