<?php
namespace Model\Utilities;

// Si alguien se pregunta el por que del nombre de esta clase, solo vean el codigo que contiene.
class Blasphemy
{
    public static function AssocArrayInsertAt(array $assocArray, $newKey, $newValue, int $position)
    {     
        $keys = array_keys($assocArray);
        
        $firstPart = array_slice($assocArray, 0, $position, true);
        $secondPart = array_slice($assocArray, $position, null, true);        
        $newArray = $firstPart + [$newKey => $newValue] + $secondPart;

        return $newArray;
    }
}