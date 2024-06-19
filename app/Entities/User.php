<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $attributes = [
        "id"=> null,
        "email" => null,
        "password"=> null,
        "created_at" => null,
        "updated_at"=> null,
        "deleted_at" => null
    ];
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [];
}
