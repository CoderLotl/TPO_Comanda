<?php
namespace Model\Classes;
use Model\Classes\GenericEntity;

class Empleado extends GenericEntity
{
    public $id;
    public $tipo;
    public $user;
    public $password;
    public $alta;
    public $baja;
}