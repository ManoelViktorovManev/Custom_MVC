<?php

namespace App\Model;

use App\Core\BaseModel;

class Post extends BaseModel
{
    private int $id;
    private string $name;
    private int $age;
    private string $text;
    private string $lname;
    private string $country;

    public function __construct() {}

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
