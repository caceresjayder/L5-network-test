<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Createdb extends Migration
{
    public function up()
    {
            $this->forge->createDatabase('db_l5network', true);
    }

    public function down()
    {
            $this->forge->dropDatabase('db_l5network');
    }
}
