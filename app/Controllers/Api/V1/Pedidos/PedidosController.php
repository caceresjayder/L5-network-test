<?php

namespace App\Controllers\Api\V1\Pedidos;

use App\Controllers\BaseController;
use App\Models;
use App\Entities;
use App\Helpers\MapResponse;
use App\Helpers\Paginator;
use CodeIgniter\Database\MySQLi\Builder;
use CodeIgniter\HTTP\Response;
use CodeIgniter\I18n\Time;
use Exception;

helper("date");

class PedidosController extends BaseController
{
    public function index()
    {
        $rules = [
            "parametros.filter_cliente_nome" => 'if_exist|string',
            "parametros.filter_cliente_cnpj" => 'if_exist|string',
            "parametros.filter_produto_nome" => 'if_exist|string',
            "parametros.filter_produto_valor" => 'if_exist|string',
            "parametros.filter_produto_stock" => 'if_exist|integer',
            "parametros.filter_produto_categoria" => 'if_exist|string',
            "parametros.filter_pedido_status.*" => 'if_exist|max_length[3]|integer|in_list[1,2,3]',
            "page" => "if_exist|integer"
        ];

        try {
            /** Validate form data */
            if (!$this->validate($rules)) {
                return $this->response
                    ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->setJSON(
                        MapResponse::getJsonResponse(
                            Response::HTTP_UNPROCESSABLE_ENTITY,
                            $this->validator->getErrors()
                        )
                    );
            }

            /** Gets validated data */
            $params = $this->validator->getValidated();
            $filter_cliente_cnpj = $params["parametros"]["filter_cliente_cnpj"] ?? null;
            $filter_cliente_nome = $params["parametros"]["filter_cliente_nome"] ?? null;
            $filter_produto_nome = $params["parametros"]["filter_produto_nome"] ?? null;
            $filter_produto_valor = $params["parametros"]["filter_produto_valor"] ?? null;
            $filter_produto_stock = $params["parametros"]["filter_produto_stock"] ?? null;
            $filter_produto_categoria = $params["parametros"]["filter_produto_categoria"] ?? null;
            $filter_pedido_status = $params["parametros"]["filter_pedido_status"] ?? null;
            $page = $params["page"] ?? 0;
            $limit = 15;

            /** Inits pedido model */
            $pedidoModel = new Models\Pedido();


            /** Gets rows from pedidos and related data */
            $results = $pedidoModel
            ->select([
                "pedidos.id", 
                "pedidos.codigo_pedido",
                "clientes.nome", 
                "clientes.cnpj", 
                "pedidos.status",
                "pedidos.data_entrega",
                "(case 
                    when pedidos.status = 1
                    then 'Em Aberto'
                    when pedidos.status = 2
                    then 'Pago'
                    when pedidos.status = 3
                    then 'Cancelado'
                    else null
                    end) as status_nome",
                /** JSON APPROACH MYSQL ^8 */
                "JSON_ARRAYAGG(JSON_OBJECT('id', produtos.id, 'nome', produtos.nome)) as produtos"
            ])
            ->selectCount("produtos.id", "qtd_produtos")
            ->join("clientes", "pedidos.cliente_id = clientes.id and clientes.deleted_at is null")
            ->join("pedido_produto as pp", "pp.pedido_id = pedidos.id", "left")
            ->join("produtos", "produtos.id = pp.produto_id", "left")
            
            /** Filters */
            
            ->when($filter_cliente_nome, fn(Builder $builder) => $builder->like('clientes.nome', $filter_cliente_nome))
            ->when($filter_cliente_cnpj, fn(Builder $builder) => $builder->like('clientes.cnpj', $filter_cliente_cnpj))
            ->when($filter_produto_nome, fn(Builder $builder) => $builder->like('produtos.nome', $filter_produto_nome))
            ->when($filter_produto_valor, fn(Builder $builder) => $builder->like('produtos.valor', $filter_produto_valor))
            ->when($filter_produto_stock, fn(Builder $builder) => $builder->where('produtos.stock >=', $filter_produto_stock))
            ->when($filter_produto_categoria, fn(Builder $builder) => $builder->like('produtos.categoria', $filter_produto_categoria))
            ->when($filter_pedido_status, fn(Builder $builder) => $builder->whereIn('pedidos.status', $filter_pedido_status))

            ->groupBy("pedidos.id")
            ->asArray()
            ->orderBy('pedidos.created_at','desc')
            ->findAll($limit, $page);


            /** Maps data */
            $pedidos = array_map(fn($pedido) => $this->mapPedidoDto($pedido), $results);

            /** Paginate */
            $results = Paginator::paginate("pedidos", $pedidos, $page, $limit, site_url("/api/v1/pedidos"));

            /** Response */
            $response = MapResponse::getJsonResponse(Response::HTTP_OK, $results);

            return $this->response->setJSON($response, true);
        } catch (\Exception $e) {
            /** Maps Error */
            $response = MapResponse::getJsonResponse(
                Response::HTTP_BAD_REQUEST,
                ['message' => $e->getMessage()]
            );

            /** Json Response */
            return $this->response
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJSON($response);
        }
    }


    public function show(int $id)
    {
        try {

            /** Inits pedido model */
            $pedidoModel = new Models\Pedido();

            /** Get Pedido by id */
            $pedido = $this->getPedidoInfo($id, $pedidoModel);

            $response = MapResponse::getJsonResponse(Response::HTTP_OK, $pedido);

            return $this->response->setJSON($response);

        } catch (Exception $e) {
            /** Maps Error */
            $response = MapResponse::getJsonResponse(
                Response::HTTP_BAD_REQUEST,
                ['message' => $e->getMessage()]
            );

            /** Json Response */
            return $this->response
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJSON($response);
        }
    }

    public function create()
    {
        $rules =
            [
                'parametros.produtos.*' => 'required|max_length[10]|integer',
                'parametros.cliente_id' => 'required|integer'
            ];

        $db = db_connect();
        $db->transBegin();
        try {
            /** Validate form data */
            if (!$this->validate($rules)) {
                return $this->response
                    ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->setJSON(
                        MapResponse::getJsonResponse(
                            Response::HTTP_UNPROCESSABLE_ENTITY,
                            $this->validator->getErrors()
                        )
                    );
            }

            /** Gets validated data */
            $params = $this->validator->getValidated();
            $produtos = $params['parametros']['produtos'];
            $cliente_id = $params['parametros']['cliente_id'];

            /** Inits models and entities */
            $clienteModel = new Models\Cliente();
            $produtoModel = new Models\Produto();
            $pedidosModel = new Models\Pedido();
            $pedidoProdutoModel = new Models\PedidoProduto();

            /** Verifies if cliente exists */
            if ($err = $this->clienteExists($cliente_id, $clienteModel)) {
                return $err;
            }

            /** Verifies if all produtos exists */
            if ($err = $this->produtosExists($produtos, $produtoModel)) {
                return $err;
            }

            /** Gets data entrega */
            $data_entrega = $this->getDataEntrega($produtos, $produtoModel);

            $pedido = new Entities\Pedido([
                'cliente_id' => $cliente_id,
                'data_entrega' => $data_entrega,
                'status' => Models\Pedido::STATUS_EM_ANDAMENTO,
                'codigo_pedido' => md5(now())
            ]);

            /** Saves pedido */
            $pedidosModel->save($pedido);
            $pedidoId = $pedidosModel->getInsertID();

            /** Maps producto - pedido relationships and saves it */
            $ppInserts = [];
            foreach ($produtos as $produto) {
                $ppInserts[] = [
                    'produto_id' => $produto,
                    'pedido_id' => $pedidoId,
                ];
            }

            $pedidoProdutoModel->insertBatch($ppInserts, 1000);

            /** Retrieve pedido */
            if(!$pedido = $this->getPedidoInfo($pedidoId, $pedidosModel)){
                throw new Exception("Resource not created");
            };
            
            
            $response = MapResponse::getJsonResponse(Response::HTTP_OK, $pedido);
            
            /** All good, commits transaction */
            $db->transCommit();
            return $this->response->setJSON($response);
        } catch (Exception $e) {

            /** Wrong! rollback db changes */
            $db->transRollback();
            /** Maps Error */
            $response = MapResponse::getJsonResponse(
                Response::HTTP_BAD_REQUEST,
                ['message' => $e->getMessage()]
            );

            /** Json Response */
            return $this->response
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJSON($response);
        }
    }

    public function update(int $id)
    {
        $rules =
            [
                'parametros.produtos.*' => 'if_exist|max_length[10]|integer',
                'parametros.cliente_id' => 'if_exist|integer'
            ];

        $db = db_connect();
        $db->transBegin();
        try {
            /** Validate form data */
            if (!$this->validate($rules)) {
                return $this->response
                    ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->setJSON(
                        MapResponse::getJsonResponse(
                            Response::HTTP_UNPROCESSABLE_ENTITY,
                            $this->validator->getErrors()
                        )
                    );
            }

            /** Gets validated data */
            $params = $this->validator->getValidated();
            $produtos = $params['parametros']['produtos'] ?? null;
            $cliente_id = $params['parametros']['cliente_id'] ?? null;

            /** Init models and entities */
            $clienteModel = new Models\Cliente();
            $produtoModel = new Models\Produto();
            $pedidosModel = new Models\Pedido();
            $pedidoProdutoModel = new Models\PedidoProduto();

            $up = [];

            /** If changes cliente then veirify if exist and prepare for update */
            if ($cliente_id) {
                if ($err = $this->clienteExists($cliente_id, $clienteModel)) {
                    return $err;
                }
                $up['cliente_id'] = $cliente_id;
            }

            /** If changes produtos then verify if exists all products and prepare for update */
            if ($produtos) {
                $this->produtosExists($produtos, $produtoModel);
                if ($err = $this->produtosExists($produtos, $produtoModel)) {
                    return $err;
                }
                $data_entrega = $this->getDataEntrega($produtos, $produtoModel);
                $up['data_entrega'] = $data_entrega;
                $ppInserts = [];
                foreach ($produtos as $produto) {
                    $ppInserts[] = [
                        'produto_id' => $produto,
                        'pedido_id' => $id
                    ];
                }

                /** Sync relations */
                $pedidoProdutoModel->where('pedido_id', $id)->delete();
                $pedidoProdutoModel->insertBatch($ppInserts, 1000);
            }

            /** Updates pedido */
            $pedidosModel->where('id', $id)->set($up)->update();

            /** Retrieves pedido */
            $pedido = $this->getPedidoInfo($id, $pedidosModel);

            $response = MapResponse::getJsonResponse(Response::HTTP_OK, $pedido);
            
            /** All good, commits changes */
            $db->transCommit();
            return $this->response->setJSON($response);
        } catch (Exception $e) {

            /** Wrong! rollback db changes */
            $db->transRollback();
            /** Maps Error */
            $response = MapResponse::getJsonResponse(
                Response::HTTP_BAD_REQUEST,
                ['message' => $e->getMessage()]
            );

            /** Json Response */
            return $this->response
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJSON($response);
        }
    }

    public function delete(int $id)
    {
        try {

            /** Deletes registry */
            $pedidoModel = new Models\Pedido();
            $pedidoModel->where('id', $id)->delete();

            $response = MapResponse::getJsonResponse(Response::HTTP_OK);
            return $this->response->setJSON($response);
        } catch (Exception $e) {
            /** Maps Error */
            $response = MapResponse::getJsonResponse(
                Response::HTTP_BAD_REQUEST,
                ['message' => $e->getMessage()]
            );

            /** Json Response */
            return $this->response
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setJSON($response);
        }
    }

    private function getPedidoInfo(int $id, Models\Pedido $model)
    {

        /** Get row from pedidos and it's relations */
        $results = $model
        ->select([
            "pedidos.id", 
            "pedidos.codigo_pedido",
            "clientes.nome", 
            "clientes.cnpj", 
            "pedidos.status",
            "pedidos.data_entrega",
            "(case 
                when pedidos.status = 1
                then 'Em Aberto'
                when pedidos.status = 2
                then 'Pago'
                when pedidos.status = 3
                then 'Cancelado'
                else null
                end) as status_nome",
            "JSON_ARRAYAGG(JSON_OBJECT('id', produtos.id, 'nome', produtos.nome)) as produtos"
        ])
        ->selectCount("produtos.id", "qtd_produtos")
        ->join("clientes", "pedidos.cliente_id = clientes.id and clientes.deleted_at is null")
        ->join("pedido_produto as pp", "pp.pedido_id = pedidos.id", "left")
        ->join("produtos", "produtos.id = pp.produto_id", "left")
        ->where('pedidos.id', $id)
        ->groupBy("pedidos.id")
        ->asArray()
        ->first();

        return $this->mapPedidoDto($results);
    }

    private function getDataEntrega(array $produtos, Models\Produto $model)
    {
        /** Retrieves higher day from product and returns a date for delivery reference */
        $res = $model
            ->select('dias_entrega')
            ->whereIn('id', $produtos)
            ->orderBy('dias_entrega', 'desc')
            ->asArray()
            ->first()['dias_entrega'];

        return Time::now()->addDays($res)->format('Y-m-d H:i:s');
    }

    private function clienteExists(int $id, Models\Cliente $model)
    {
        /** Verifies if cliente exist */
        if (!$cliente = $model->select('id')->find($id)) {
            $response = MapResponse::getJsonResponse(Response::HTTP_NOT_FOUND);
            return $this->response->setStatusCode(Response::HTTP_NOT_FOUND)->setJSON($response);
        }

        return false;
    }

    private function produtosExists(array $produtos, Models\Produto $model)
    {
        /** Verifies if all products exist */
        if (
            !($model->select('id')
                ->where('stock >', 0)
                ->whereIn('id', $produtos)
                ->countAllResults() === count($produtos))
        ) {
            $response = MapResponse::getJsonResponse(Response::HTTP_BAD_REQUEST, [], "Um o mais produtos nao estao disponiveis");
            return $this->response->setStatusCode(Response::HTTP_BAD_REQUEST)->setJSON($response);
        }

        return false;
    }

    private function mapPedidoDto(array $pedido)
    {
        /** Maps pedido */
        return [
            'id' => $pedido['id'],
            'codigo_pedido' => $pedido['codigo_pedido'],
            'cliente_nome' => $pedido['nome'],
            'cliente_cnpj' => $pedido['cnpj'],
            'pedido_status' => $pedido['status'],
            'pedido_status_nome' => $pedido['status_nome'],
            'qtd_produtos' => $pedido['qtd_produtos'],
            'previsao_entrega' => $pedido['data_entrega'],
            'produtos' => json_decode($pedido['produtos'])
        ];
    }

}
