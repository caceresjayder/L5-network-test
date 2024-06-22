<?php

namespace App\Controllers\Api\V1\Pedidos;

use App\Controllers\BaseController;
use App\Helpers\MapResponse;
use App\Helpers\Paginator;
use CodeIgniter\HTTP\Response;
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

            $pedidoModel = new \App\Models\Pedido();

            $pedidoModel
                ->select(["pedidos.id", "clientes.nome", "clientes.cnpj", "pedidos.status"])
                ->selectCount("produtos.id", "qtd_produtos")
                ->selectMax("produtos.dias_entrega", "max_dias_entrega")
                ->join("clientes", "pedidos.cliente_id = clientes.id and clientes.deleted_at is null")
                ->join("pedido_produto as pp", "pp.pedido_id = pedidos.id", "left")
                ->join("produtos", "produtos.id = pp.produto_id", "left");


            /** Filters */
            if ($filter_cliente_cpnj)
                $pedidoModel->like("cpnj", $filter_cliente_cpnj);
            if ($filter_cliente_nome)
                $pedidoModel->like("nome", $filter_cliente_nome);

            $pedidoModel
                ->groupBy("pedidos.id")
                ->orderBy("nome");


            $clientes = $pedidoModel->paginate($limit);

            $results = Paginator::paginate("clientes", $clientes, $page, $limit, site_url("/api/v1/clientes"));

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

            $pedidoModel = new \App\Models\Pedido();

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
                'parametros.produtos.*' => 'required|integer',
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

            $clienteModel = new \App\Models\Cliente();
            $produtoModel = new \App\Models\Produto();
            $pedidosModel = new \App\Models\Pedido();
            $pedidoProdutoModel = new \App\Models\PedidoProduto();

            $this->clienteExists($cliente_id, $clienteModel);
            $this->produtosExists($produtos, $produtoModel);

            $data_entrega = $this->getDataEntrega($produtos, $produtoModel);

            $pedido = new \App\Entities\Pedido([
                'cliente_id' => $cliente_id,
                'data_entrega' => $data_entrega,
            ]);

            $pedidosModel->save($pedido);
            $pedidoId = $pedidosModel->getInsertID();

            $ppInserts = [];
            foreach ($produtos as $produto) {
                $ppInserts[] = [
                    'produto_id' => $produto,
                    'pedido_id' => $pedidoId
                ];
            }

            $pedidoProdutoModel->insertBatch($ppInserts, 1000);

            $pedido = $pedidosModel->find($pedidoId);

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

    public function update(int $id)
    {
        $rules =
            [
                'parametros.produtos.*' => 'if_exist|integer',
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

            $clienteModel = new \App\Models\Cliente();
            $produtoModel = new \App\Models\Produto();
            $pedidosModel = new \App\Models\Pedido();
            $pedidoProdutoModel = new \App\Models\PedidoProduto();

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
            
            $pedidoModel = new \App\Models\Pedido();
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

    private function getPedidoInfo(int $id, \App\Models\Pedido $model, array $params = [])
    {
        return $model
                ->select(["pedidos.id", "clientes.nome", "clientes.cnpj", "pedidos.status"])
                ->selectCount("produtos.id", "qtd_produtos")
                ->selectMax("produtos.dias_entrega", "max_dias_entrega")
                ->join("clientes", "pedidos.cliente_id = clientes.id and clientes.deleted_at is null")
                ->join("pedido_produto as pp", "pp.pedido_id = pedidos.id", "left")
                ->join("produtos", "produtos.id = pp.produto_id", "left")
                ->where('pedidos.id', $id)
                ->groupBy("pedidos.id")
                ->first();
    }

    private function getDataEntrega(array $produtos, \App\Models\Produto $model)
    {
        $max_dias_entrega = $model
            ->selectMax('dias_entrega', 'max_dias_entrega')
            ->whereIn('id', $produtos)
            ->first()['max_dias_entrega'];

        return strtotime("+{$max_dias_entrega}d");
    }

    private function clienteExists(int $id, \App\Models\Cliente $model)
    {
        if (!$model->select('id')->find($id)) {
            $response = MapResponse::getJsonResponse(Response::HTTP_NOT_FOUND);
            return $this->response->setStatusCode(Response::HTTP_NOT_FOUND, $response);
        }

        return true;
    }

    private function produtosExists(array $produtos, \App\Models\Produto $model)
    {
        if (!($model->select('id')->whereIn('id', $produtos)->countAllResults() === count($produtos))) {
            $response = MapResponse::getJsonResponse(Response::HTTP_BAD_REQUEST);
            return $this->response->setStatusCode(Response::HTTP_BAD_REQUEST, $response);
        }
    }

}
