<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateProdutosTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            "id"=> [
                "type" => "INT",
                "unsigned" => true,
                "auto_increment" => true,
            ],
            "nome" => [
                "type" => "VARCHAR",
                "constraint" => "100"
            ],
            "valor"=> [
                "type"=> "VARCHAR",
                "constraint" => "100"
            ],
            "stock" => [
                "type" => "INT",
                "default" => "0"
            ],
            "categoria" => [
                "type" => "VARCHAR",
                "constraint" => "255"
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
        $this->forge->createTable("produtos");
    }

    public function down()
    {
        $this->forge->dropTable("produtos");
    }
}
