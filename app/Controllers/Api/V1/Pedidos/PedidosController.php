<?php

namespace App\Controllers\Api\V1\Pedidos;

use App\Controllers\BaseController;
use App\Models;
use App\Entities;
use App\Helpers\MapResponse;
use App\Helpers\Paginator;
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
            "parametros.filter_cliente_cpnj" => 'if_exist|string',
            "parametros.filter_produto_nome" => 'if_exist|string',
            "parametros.filter_produto_valor" => 'if_exist|string',
            "parametros.filter_produto_stock" => 'if_exist|integer',
            "parametros.filter_produto_categoria" => 'if_exist|string',
            "parametros.filter_pedido_status" => 'if_exist|integer',
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
            ;

            $params = $this->validator->getValidated();
            $filter_cliente_cpnj = $params["parametros"]["filter_cliente_cpnj"] ?? null;
            $filter_cliente_nome = $params["parametros"]["filter_cliente_nome"] ?? null;
            $filter_produto_nome = $params["parametros"]["filter_produto_nome"] ?? null;
            $filter_produto_valor = $params["parametros"]["filter_produto_valor"] ?? null;
            $filter_produto_stock = $params["parametros"]["filter_produto_stock"] ?? null;
            $filter_produto_categoria = $params["parametros"]["filter_produto_categoria"] ?? null;
            $filter_pedido_status = $params["parametros"]["filter_pedido_status"] ?? null;
            $page = $params["page"] ?? 0;
            $limit = 15;

            $pedidoModel = new Models\Pedido();

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
                "JSON_ARRAYAGG(JSON_OBJECT('id', produtos.id, 'nome', produtos.nome)) as produtos"
            ])
            ->selectCount("produtos.id", "qtd_produtos")
            ->join("clientes", "pedidos.cliente_id = clientes.id and clientes.deleted_at is null")
            ->join("pedido_produto as pp", "pp.pedido_id = pedidos.id", "left")
            ->join("produtos", "produtos.id = pp.produto_id", "left")
            ->groupBy("pedidos.id")
            ->asArray()
            ->orderBy('pedidos.created_at','desc')
            ->paginate($limit);


            $pedidos = array_map(fn($pedido) => $this->mapPedidoDto($pedido), $results);

            $results = Paginator::paginate("pedidos", $pedidos, $page, $limit, site_url("/api/v1/pedidos"));

            $response = MapResponse::getJsonResponse(Response::HTTP_OK, $results);

            return $this->response->setJSON($response);
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

            $pedidoModel = new Models\Pedido();

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

            $params = $this->validator->getValidated();
            $produtos = $params['parametros']['produtos'];
            $cliente_id = $params['parametros']['cliente_id'];

            $clienteModel = new Models\Cliente();
            $produtoModel = new Models\Produto();
            $pedidosModel = new Models\Pedido();
            $pedidoProdutoModel = new Models\PedidoProduto();

            if ($err = $this->clienteExists($cliente_id, $clienteModel)) {
                return $err;
            }

            if ($err = $this->produtosExists($produtos, $produtoModel)) {
                return $err;
            }

            $data_entrega = $this->getDataEntrega($produtos, $produtoModel);

            $pedido = new Entities\Pedido([
                'cliente_id' => $cliente_id,
                'data_entrega' => $data_entrega,
                'status' => Models\Pedido::STATUS_EM_ANDAMENTO,
                'codigo_pedido' => md5(now())
            ]);

            $pedidosModel->save($pedido);
            $pedidoId = $pedidosModel->getInsertID();

            $ppInserts = [];
            foreach ($produtos as $produto) {
                $ppInserts[] = [
                    'produto_id' => $produto,
                    'pedido_id' => $pedidoId,
                ];
            }

            $pedidoProdutoModel->insertBatch($ppInserts, 1000);
            if(!$pedido = $this->getPedidoInfo($pedidoId, $pedidosModel)){
                throw new Exception("Resource not created");
            };
            
            
            $response = MapResponse::getJsonResponse(Response::HTTP_OK, $pedido);
            
            $db->transCommit();
            return $this->response->setJSON($response);
        } catch (Exception $e) {
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
                'parametros.produtos.*' => 'if_exist|max_length[10]',
                'parametros.cliente_id' => 'if_exist|integer'
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

            $params = $this->validator->getValidated();
            $produtos = $params['parametros']['produtos'] ?? null;
            $cliente_id = $params['parametros']['cliente_id'] ?? null;

            $clienteModel = new Models\Cliente();
            $produtoModel = new Models\Produto();
            $pedidosModel = new Models\Pedido();
            $pedidoProdutoModel = new Models\PedidoProduto();

            $up = [];

            if ($cliente_id) {
                $this->clienteExists($cliente_id, $clienteModel);
                $up['cliente_id'] = $cliente_id;
            }

            if ($produtos) {
                $this->produtosExists($produtos, $produtoModel);
                $data_entrega = $this->getDataEntrega($produtos, $produtoModel);
                $up['data_entrega'] = $data_entrega;
                $ppInserts = [];
                foreach ($produtos as $produto) {
                    $ppInserts[] = [
                        'produto_id' => $produto,
                        'pedido_id' => $id
                    ];
                }

                $pedidoProdutoModel->where('pedido_id', $id)->delete();
                $pedidoProdutoModel->insertBatch($ppInserts, 1000);

            }


            $pedidosModel->where('id', $id)->update($up);

            $pedido = $this->getPedidoInfo($id, $pedidosModel);

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

    public function delete(int $id)
    {
        try {

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

    private function getPedidoInfo(int $id, Models\Pedido $model, array $params = [])
    {

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
        if (!$cliente = $model->select('id')->find($id)) {
            $response = MapResponse::getJsonResponse(Response::HTTP_NOT_FOUND);
            return $this->response->setStatusCode(Response::HTTP_NOT_FOUND)->setJSON($response);
        }

        return false;
    }

    private function produtosExists(array $produtos, Models\Produto $model)
    {
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
