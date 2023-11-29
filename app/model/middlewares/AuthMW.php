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
        $token = $request->getHeaderLine('Authorization');
        $params = null;

        if($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'DELETE')
        {
            $params = $request->getQueryParams();
        }
        else
        {
            $params = $request->getParsedBody();
        }      

        // --------------------------------

        if(isset($params['accion']) && isset($params['objeto']))
        {            
            $action = $params['accion'];
            $object = $params['objeto'];                        
        }
        else
        {
            $e = 'Request incompleta: ';
            if (!isset($_REQUEST['accion'])) {
                $e .= 'accion no definida; ';
            }
            if (!isset($_REQUEST['objeto'])) {
                $e .= 'objeto no definido; ';
            }
            throw new Exception($e);
        }

        // --------------------------------
        
        $userType = AuthJWT::GetData($token)->rol;            
        
        if( self::ValidateAction($action, $object, $userType, $params) )
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
    private static function ValidateAction($action, $object, $userType, $_req)
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
        $token = $request->getHeaderLine('Authorization');
        $role = self::GetRole($token);
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
        $token = $request->getHeaderLine('Authorization');
        if($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'DELETE')
        {
            $params = $request->getQueryParams();
        }
        else
        {
            $params = $request->getParsedBody();
        }

        if( in_array(self::GetRole($token), ['socio', 'cervecero', 'mozo', 'cocinero', 'bartender']) )
        {
            return $handler->handle($request);
        }
        throw new Exception('Este usuario no tiene este derecho.'); 
    }

    public static function WardSocio($request, $handler)
    {        
        $token = $request->getHeaderLine('Authorization');

        if(isset($token) && self::GetRole($token) == 'socio')
        {            
            return $handler->handle($request);
        }
        throw new Exception('Este usuario no tiene este derecho.'); 
    }

    public static function WardMozo($request, $handler)
    {
        $token = $request->getHeaderLine('Authorization');

        if(isset($token) && (self::GetRole($token) == 'socio' || self::GetRole($token) == 'mozo'))
        {
            return $handler->handle($request);
        }
        throw new Exception('Este usuario no tiene este derecho.'); 
    }
}