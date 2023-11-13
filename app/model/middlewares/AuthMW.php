<?php
namespace Model\Middlewares;

use Model\Services\DataAccess;
use Exception;
use Model\Utilities\Log;

class AuthMW
{
    public static function ValidateUser($request, $handler)    
    {
        if($_SERVER['REQUEST_METHOD'] != 'PUT')
        {
            $action = $_REQUEST['accion'];
            $right = $_REQUEST['objeto'];
            $userName = $_REQUEST['user'];
        }
        else
        {
            $_PUT = file_get_contents("php://input");        
            $_PUT = json_decode($_PUT, true);
            $action = $_PUT['accion'];
            $right = $_PUT['objeto'];
            $userName = $_PUT['user'];
        }

        if($userName)
        {
            $userType = DataAccess::SelectWithJoin('tipo_usuario', 'usuarios', 'codigo', 'tipo', 'user', $userName, ['tipo_usuario.tipo'])[0]['tipo'];
            
            if( (USER_RIGHTS[$userType] == '*') || (in_array($action, USER_RIGHTS[$userType]) && in_array($right, USER_RIGHTS[$userType][$action])) )
            {
                return $handler->handle($request);
            }
            else
            {
                throw new Exception('Este usuario no tiene este derecho.');
            }
        }
        else
        {
            throw new Exception('No hay nombre de usuario.');
        }
    }
}