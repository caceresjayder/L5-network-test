<?php

namespace App\Controllers\Api\V1\Clientes;

use App\Controllers\BaseController;
use App\Helpers\MapResponse;
use App\Helpers\Paginator;
use CodeIgniter\Database\MySQLi\Builder;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class ClientesController extends BaseController
{
    public function index()
    {
        $rules = [
            "parametros.filter_cnpj" => 'if_exist|string',
            "parametros.filter_nome" => 'if_exist|string',
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

            /** Takes filters */
            $filter_cnpj = $params["parametros"]["filter_cnpj"] ?? null;
            $filter_nome = $params["parametros"]["filter_nome"] ?? null;
            $page = $params["page"] ?? 0;
            $limit = 15;

            $clienteModel = new \App\Models\Cliente();
            
            /** Gets rows from clientes database */
            $clientes = $clienteModel
            ->when($filter_cnpj, fn($query) => $query->like("cnpj", $filter_cnpj))
            ->when($filter_nome, fn(Builder $query) => $query->like("nome", $filter_nome))
            ->orderBy("nome")
            ->asArray()
            ->findAll($limit, $page);

            /** Paginate results */
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

            /** Retrieves cliente */
            $clienteModel = new \App\Models\Cliente();
            $cliente = $clienteModel->asArray()->find($id);

            /** Cliente exists */
            if (!$cliente) {
                $response = MapResponse::getJsonResponse(Response::HTTP_NOT_FOUND);
                return $this->response->setStatusCode(Response::HTTP_NOT_FOUND)->setJSON($response);
            }

            $response = MapResponse::getJsonResponse(Response::HTTP_OK, $cliente);

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
            'parametros.nome'=> 'required|string',
            'parametros.cnpj' => 'required|string'
        ];

        try
        {
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

            /** Gets data */
            $params = $this->validator->getValidated();
            $data = $params['parametros'];

            /** Init models and cliente instance */
            $clienteModel = new \App\Models\Cliente();
            $cliente = new \App\Entities\Cliente($data);

            /** Saves entity */
            $clienteModel->save($cliente);
            $id = $clienteModel->getInsertID();

            /** Retrieves the cliente by id */
            $cliente = $clienteModel->asArray()->find($id);

            $response = MapResponse::getJsonResponse(Response::HTTP_OK, $cliente);
            return $this->response->setJSON($response);
        }
        catch(Exception $e)
        {
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
            'parametros.nome'=> 'if_exist|string',
            'parametros.cnpj' => 'if_exist|string'
        ];

        try
        {
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

            /** Gets data */
            $params = $this->validator->getValidated();
            $data = $params['parametros'];

            /** Init clientes model */
            $clienteModel = new \App\Models\Cliente();

            /** Updates cliente */
            $clienteModel->where('id', $id)->update($data);

            /** Retrieve clientes information */
            $cliente = $clienteModel->asArray()->find($id);

            $response = MapResponse::getJsonResponse(Response::HTTP_OK, $cliente);
            return $this->response->setJSON($response);
        }
        catch(Exception $e)
        {
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
        try
        {
            /** Inits clientes model */
            $clienteModel = new \App\Models\Cliente();

            /** Deletes cliente by id */
            $clienteModel->where('id', $id)->delete();

            $response = MapResponse::getJsonResponse(Response::HTTP_OK);
            return $this->response->setJSON($response);
        }
        catch(Exception $e)
        {
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
