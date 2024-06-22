<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Faker\Factory;

class SeederDB extends Seeder
{
    public function run()
    {

        $faker = Factory::create();
        

        $clientesToInsert = [];
        $produtosToInsert = [];
        $pedidosToInsert = [];
        $pedidosProdutosToInsert = [];


        for ($i = 1; $i <= 100; $i++)
        {
            array_push($clientesToInsert, $this->getCliente($faker));
            array_push($produtosToInsert, $this->getProduto($faker));
            array_push($pedidosToInsert, $this->getPedido($faker));

            for ($j = 1; $j <= 3; $j++)
            {
                array_push($pedidosProdutosToInsert, [
                    'pedido_id' => $i,
                    'produto_id' => $faker->numberBetween(1,100)
                ]);
            }
        }

        $this->db->transBegin();

        try
        {
            $this->db->table("clientes")->insertBatch($clientesToInsert);
            $this->db->table("produtos")->insertBatch($produtosToInsert);
            $this->db->table("pedidos")->insertBatch($pedidosToInsert);
            $this->db->table("pedido_produto")->insertBatch($pedidosProdutosToInsert);
            $this->db->transCommit();
        }
        catch (\Exception $e)
        {
            $this->db->transRollback();
            throw $e;
        }

    }

    private function getCliente(\Faker\Generator $faker)
    {
        return [
            'nome' => $faker->name(),
            'cnpj' => $faker->numerify("##.###.###/0001-##")
        ];
    }

    private function getProduto(\Faker\Generator $faker)
    {
        return [
            'nome' => $faker->word(),
            'valor' => $faker->numerify("#####.##"),
            'stock' => $faker->numberBetween(0,1000),
            'categoria' => $faker->word(),
            'dias_entrega' => $faker->numberBetween(1,30)
        ];
    }

    private function getPedido(\Faker\Generator $faker)
    {
        return [
            'codigo_pedido' => $faker->md5(),
            'cliente_id' => $faker->numberBetween(1,100),
            'data_entrega' => $faker->dateTimeThisMonth()->format('Y-m-d H:i:s'),
            'status' => $faker->randomElement([1,2,3])
        ];
    }
}
