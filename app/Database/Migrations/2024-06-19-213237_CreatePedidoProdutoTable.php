<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePedidoProdutoTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            "produto_id"=> [
                "type" => "INT",
                "unsigned" => true,
            ],
            "pedido_id"=> [
                "type" => "INT",
                "unsigned" => true,
            ],
        ]);

        $this->forge->addKey(["produto_id","pedido_id"], true);
        $this->forge->addForeignKey("produto_id","produtos","id");
        $this->forge->addForeignKey("pedido_id","pedidos","id");
        $this->forge->createTable("pedido_produto");
    }

    public function down()
    {
        $this->forge->dropTable("pedido_produto");

    }
}
