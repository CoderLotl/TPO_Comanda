<?php
namespace Model\Classes;

class GenericEntity
{
    public $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Iterates for each of the attributes and returns a string.
     * @return string
    */
    public function __toString()
    {
        $att = $this->attributes;
        $string = "";

        end($att);
        $lastkey = key($att);

        foreach($att as $key => $value)
        {
            $string .= "{$key}: {$value}";
            if($key !== $lastkey)
            {
                $string .= " - ";
            }
        }

        return $string;
    }
}