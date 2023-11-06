<?php
namespace Model\Classes;

class GenericEntity
{
    public function __get($propiedad)
    {
        if(property_exists($this, $propiedad))
        {
            return $this->$propiedad;
        }
        else
        {
            return false;
        }
    }

    public function __set($propiedad, $valor)
    {
        if(property_exists($this, $propiedad))
        {
            $this->$propiedad = $valor;
        }
        else
        {
            echo "No existe " . $propiedad;
        }
    }

    /**
     * Iterates for each of the attributes and returns a string.
     * @return string
    */
    public function __toString()
    {
        $attributes = get_object_vars($this);
        $string = "";

        end($attributes);
        $lastkey = key($attributes);

        foreach($attributes as $key => $value)
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