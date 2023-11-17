<?php
namespace Model\Middlewares;

use Model\Services\DataAccess;
use Model\Services\AuthJWT;
use Exception;
use Model\Utilities\Blasphemy;
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

    /**
     * Este middleware revisa si el usuario, en base a su rol, puede realizar la accion general (alta, baja, modificacion) que intenta hacer.
     * Se tiene en cuenta, en el caso de los pedidos y las mesas, los estados habilitados para cada rol.
     */
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
                if(isset($_req['estado']))
                {
                    $state = $_req['estado']; // Obtengo el valor del estado que el usuario quiere setear
                    if( !in_array($state, USER_RIGHTS[$userType][$action][$object]) )// Chequeo si el usuario puede hacer el cambio de estado con el valor que quiere pasar
                    {
                        return false;
                    }
                    return true;
                }
            }
            return true;
        }
        
        return false;
    }

    /**
     * Este middleware particular revisa que, en el caso de una modificacion sobre un pedido, un sub-rol no pueda afectar un pedido de un area ajena.
     */
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

    public static function WardGrupo($request, $handler)
    {
        if($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'DELETE')
        {
            $params = $request->getQueryParams();
        }
        else
        {
            $params = $request->getParsedBody();
        }

        if( isset($params['token']) && in_array(self::GetRole($params['token']), ['socio', 'cervecero', 'mozo', 'cocinero', 'bartender']) )
        {
            return $handler->handle($request);
        }
        throw new Exception('Este usuario no tiene este derecho.'); 
    }

    public static function WardSocio($request, $handler)
    {
        $params = Blasphemy::GetRequest($request);

        if(isset($params['token']) && self::GetRole($params['token']) == 'socio')
        {
            return $handler->handle($request);
        }
        throw new Exception('Este usuario no tiene este derecho.'); 
    }
}