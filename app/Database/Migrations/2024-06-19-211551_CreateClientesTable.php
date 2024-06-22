<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateClientesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            "id"=> [
                "type" => "INT",
                "unsigned" => true,
                "auto_increment" => true,
            ],
            "user_id" => [
                "type" => "INT",
                "unsigned" => true,
                'null' => true,
            ],
            "nome" => [
                "type" => "VARCHAR",
                "constraint" => "100"
            ],
            "cnpj"=> [
                "type"=> "VARCHAR",
                "constraint" => "100"
            ],
            "created_at" => [
                "type" => "timestamp",
                "default" => new RawSql("CURRENT_TIMESTAMP")
            ],
            "updated_at" => [
                "type" => "timestamp",
                "default" => new RawSql("CURRENT_TIMESTAMP")
            ],
            "deleted_at" => [
                "type" => "timestamp",
                "null" => true
            ],
        ]);

        $this->forge->addKey("id", true);
        $this->forge->addForeignKey("user_id", "users", "id");
        $this->forge->createTable("clientes");
    }

    public function down()
    {
       $this->forge->dropTable("clientes");
    }
}
