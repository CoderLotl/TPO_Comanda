<?php

namespace Model\Services;
use Firebase\JWT\JWT;
use Exception;

class AuthJWT
{
    private static $secretWord = '$SPL4B0$';
    private static $encrypt = ['HS256'];

    public static function NewToken($datos)
    {
        $now = time();
        $payload = array(
            'iat' => $now,
            'exp' => $now + (60000),
            'aud' => self::Aud(),
            'data' => $datos,
            'app' => "TP Comanda"
        );

        return ['token' => $payload, 'jwt' => JWT::encode($payload, self::$secretWord)];
    }

    public static function VerifyToken($token)
    {
        if (empty($token))
        {
            throw new Exception("El token esta vacio.");
        }
        try
        {
            $decodificado = JWT::decode(
                $token,
                self::$secretWord,
                self::$encrypt
            );
        }
        catch (Exception $e)
        {
            throw $e;
        }
        if ($decodificado->aud !== self::Aud())
        {
            throw new Exception("Usuario no valido.");
        }
    }


    public static function GetPayload($token)
    {
        if (empty($token)) {
            throw new Exception("El token esta vacio.");
        }
        return JWT::decode(
            $token,
            self::$secretWord,
            self::$encrypt
        );
    }

    public static function GetData($token)
    {
        return JWT::decode(
            $token,
            self::$secretWord,
            self::$encrypt
        )->data;
    }

    private static function Aud()
    {
        $aud = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $aud = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $aud = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $aud = $_SERVER['REMOTE_ADDR'];
        }

        $aud .= @$_SERVER['HTTP_USER_AGENT'];
        $aud .= gethostname();

        return sha1($aud);
    }
}