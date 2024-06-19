<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            "id"=> [
                "type" => "INT",
                "unsigned" => true,
                "auto_increment" => true,
            ],
            "email" => [
                "type" => "VARCHAR",
                "constraint" => "100"
            ],
            "password"=> [
                "type"=> "VARCHAR",
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
        $this->forge->addKey("email", false,true);
        $this->forge->createTable("users");
    }

    public function down()
    {
        $this->forge->dropTable("users");
    }
}
