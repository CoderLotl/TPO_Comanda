<?php
namespace Model\Utilities;

class CodeGenerator
{
    public static function RandomSequentialAlphaNumCode($currentCode)
    {
        $code = str_split($currentCode);
        $chars = range('A', 'Z');
        
        if ($code[4] < 9)
        {
            $code[4] = $code[4] + 1;
        }
        else
        {
            $code[3] = $chars[array_search($code[3], $chars) + 1];
            $code[4] = 1;
        }
        
        return implode('', $code);
    }

    public static function RandomAlphaNumCode()
    {
        $length = 5;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomCode = '';
        for ($i = 0; $i < $length; $i++)
        {
            $randomCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomCode;
    }
}