<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreatePedidosTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            "id"=> [
                "type" => "INT",
                "unsigned" => true,
                "auto_increment" => true,
            ],
            "codigo_pedido" => [
                "type"=> "VARCHAR",
                "constraint" => "100"
            ],
            "cliente_id" => [
                "type" => "INT",
                "unsigned" => true
            ],
            "data_entrega"=> [
                "type"=> "timestamp",
            ],
            "status" => [
                "type" => "SMALLINT",
                "default" => "1",
                "comment" => "1:em aberto|2:pago|3:cancelado"
            ],
            "create_at" => [
                "type" => "timestamp",
                "default" => new RawSql("CURRENT_TIMESTAMP")
            ],
            "update_at" => [
                "type" => "timestamp",
                "default" => new RawSql("CURRENT_TIMESTAMP")
            ],
            "delete_at" => [
                "type" => "timestamp",
                "null" => true
            ],
        ]);

        $this->forge->addKey("id", true);
        $this->forge->addForeignKey("cliente_id","clientes","id");
        $this->forge->createTable("pedidos");
    }

    public function down()
    {
        $this->forge->dropTable("pedidos");
    }
}
