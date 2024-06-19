<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Cliente extends Entity
{
    protected $attributes = [
        "id" => null,
        "user_id" => null,
        "nome" => null,
        "cpnj" => null,
        "created_at" => null,
        "updated_at" => null,
        "deleted_at" => null,
    ];
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [];
}
