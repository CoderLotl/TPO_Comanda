<?php

namespace Model\Services;

use Model\Services\DataAccess;
use Model\Services\AuthJWT;
use Model\Utilities\Log;
use DateTime;

class LoginManager
{
    public static function LogIn($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $user = $params['user'];
        $pass = $params['pass'];

        $user = DataAccess::SelectWhere('usuarios', ['id', 'user', 'password'], ['user', 'password'], [$user, $pass]);        

        if($user)
        {
            $userID = $user[0]['id'];
            $userType = DataAccess::SelectWithJoin('tipo_usuario', 'usuarios', 'codigo', 'tipo', 'id', $userID, ['tipo_usuario.tipo'])[0]['tipo'];

            $data = ['user' => $user[0]['user'], 'id' => $userID, 'rol' => $userType];
            $token = AuthJWT::NewToken($data);
            $payload = ["mensaje" => "Usuario logeado.", "token" => $token['jwt']];


            $fecha = new DateTime();
            $fecha = $fecha->format('Y-m-d H:i:s');       
            
            // - - - - - LOG
            
            DataAccess::Insert('accesos', ['userID', 'fecha', 'recurso'], [$userID, $fecha, 'Login']);            
            
            return self::ReturnResponse($request, $response, $payload);
        }
        else
        {
            return self::ReturnResponse($request, $response, 'Login incorrecto.');
        }
    }

    private static function ReturnResponse($request, $response, $payload)
    {
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
}