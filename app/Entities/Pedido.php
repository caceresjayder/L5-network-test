<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Pedido extends Entity
{
    protected $attributes = [
        "id" => null,
        "codigo_pedido" => null,
        "cliente_id" => null,
        "data_entrega" => null,
        "status" => 1,
        "created_at" => null,
        "updated_at"=> null,
        "deleted_at" => null,
    ];
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [];
}
