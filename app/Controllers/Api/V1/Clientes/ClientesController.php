<?php

namespace App\Controllers\Api\V1\Clientes;

use App\Controllers\BaseController;
use App\Helpers\MapResponse;
use App\Helpers\Paginator;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class ClientesController extends BaseController
{
    public function index()
    {
        $rules = [
            "parametros.filter_cpnj" => 'if_exist|string',
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
            ;

            $params = $this->validator->getValidated();
            $filter_cpnj = $params["parametros"]["filter_cpnj"] ?? null;
            $filter_nome = $params["parametros"]["filter_nome"] ?? null;
            $page = $params["paramentros"]["page"] ?? 0;
            $limit = 15;

            $clienteModel = new \App\Models\Cliente();
            if ($filter_cpnj)
                $clienteModel->like("cpnj", $filter_cpnj);
            if ($filter_nome)
                $clienteModel->like("nome", $filter_nome);

            $clienteModel->orderBy("nome");
            $clientes = $clienteModel->paginate($limit);

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

            $clienteModel = new \App\Models\Cliente();
            $cliente = $clienteModel->asArray()->find($id);
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

            $params = $this->validator->getValidated();
            $data = $params['parametros'];

            $clienteModel = new \App\Models\Cliente();
            $cliente = new \App\Entities\Cliente($data);

            $clienteModel->save($cliente);
            $id = $clienteModel->getInsertID();
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

            $params = $this->validator->getValidated();
            $data = $params['parametros'];

            $clienteModel = new \App\Models\Cliente();
            $clienteModel->where('id', $id)->update($data);
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
            $clienteModel = new \App\Models\Cliente();
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
