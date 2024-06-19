<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Produto extends Entity
{
    protected $attributes = [
        "id" => null,
        "nome" => null,
        "valor" => null,
        "stock" => null,
        "categoria" => null,
        "created_at" => null,
        "updated_at"=> null,
        "deleted_at" => null,
    ];
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [];
}
