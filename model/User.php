<?php

namespace App\Model;

use App\Core\BaseModel;

class User extends BaseModel
{
    private int $id;
    private string $name;
    private int $age;

    public function __construct()
    {
        $this->id = 0;
        $this->name = '';
        $this->age = 0;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getAge()
    {
        return $this->age;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
    public function setAge($age)
    {
        $this->age = $age;
    }
};
