<?php

namespace App\Controllers\Api\V1\Produtos;

use App\Controllers\BaseController;
use App\Helpers\MapResponse;
use App\Helpers\Paginator;
use CodeIgniter\Database\MySQLi\Builder;
use CodeIgniter\HTTP\Response;
use Exception;

class ProdutosController extends BaseController
{
    public function index()
    {
        $rules = [
            "parametros.filter_valor" => 'if_exist|string',
            "parametros.filter_nome" => 'if_exist|string',
            "parametros.filter_categoria" => 'if_exist|string',
            "parametros.filter_stock" => 'if_exist|integer',
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


            /** Gets validated params */
            $params = $this->validator->getValidated();

            /**  */
            $filter_valor = $params["parametros"]["filter_valor"] ?? null;
            $filter_nome = $params["parametros"]["filter_nome"] ?? null;
            $filter_stock = $params["parametros"]["filter_stock"] ?? null;
            $filter_categoria = $params["parametros"]["filter_categoria"] ?? null;
            $page = $params["page"] ?? 0;
            $limit = 15;

            /** Init produto model */
            $produtoModel = new \App\Models\Produto();

            /** Get rows from produtos */
            $produtos = $produtoModel
            ->when($filter_valor, fn(Builder $builder) => $builder->like("valor", $filter_valor))
            ->when($filter_nome, fn(Builder $builder) => $builder->like("nome", $filter_nome))
            ->when($filter_stock, fn(Builder $builder) => $builder->where("stock >=", $filter_stock))
            ->when($filter_categoria, fn(Builder $builder) => $builder->like("categoria", $filter_categoria))
            ->orderBy("nome")
            ->asArray()
            ->findAll($limit, $page);

            /** Paginate */
            $results = Paginator::paginate("produtos", $produtos, $page, $limit, site_url("/api/v1/produtos"));

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

            /** Init produto model */
            $produtoModel = new \App\Models\Produto();

            /** retrieve produto by id */
            $produto = $produtoModel->asArray()->find($id);

            /** Produto exists */
            if (!$produto) {
                $response = MapResponse::getJsonResponse(Response::HTTP_NOT_FOUND);
                return $this->response->setStatusCode(Response::HTTP_NOT_FOUND)->setJSON($response);
            }

            $response = MapResponse::getJsonResponse(Response::HTTP_OK, $produto);

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
                'parametros.nome' => 'required|string',
                'parametros.valor' => 'required|string',
                'parametros.stock' => 'required|integer',
                'parametros.categoria' => 'required|string'
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
            $data = $params['parametros'];

            /** Inits produto model and entity */
            $produtoModel = new \App\Models\Produto();
            $produto = new \App\Entities\Produto($data);

            /** Saves produto entity */
            $produtoModel->save($produto);
            $id = $produtoModel->getInsertID();

            /** Gets created produto by id */
            $produto = $produtoModel->asArray()->find($id);

            $response = MapResponse::getJsonResponse(Response::HTTP_OK, $produto);
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
                'parametros.nome' => 'if_exist|string',
                'parametros.valor' => 'if_exist|string',
                'parametros.stock' => 'if_exist|integer',
                'parametros.categoria' => 'if_exist|string'
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
            $data = $params['parametros'];

            /** Init produto model */
            $produtoModel = new \App\Models\Produto();

            /** Updates produto */
            $produtoModel->where('id', $id)->set($data)->update();

            /** Retrieves produtos by id */
            $produto = $produtoModel->asArray()->find($id);

            $response = MapResponse::getJsonResponse(Response::HTTP_OK, $produto);
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

            /** Inits produto model */
            $produtoModel = new \App\Models\Produto();

            /** Deletes produto */
            $produtoModel->delete($id);

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
}
