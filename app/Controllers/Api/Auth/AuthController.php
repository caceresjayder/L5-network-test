<?php

namespace App\Controllers\Api\Auth;

use App\Controllers\BaseController;
use App\Helpers\MapResponse;
use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

use \App\Models;
use \App\Entities;

class AuthController extends BaseController
{
    protected $helpers = ['form'];

    public function register()
    {
        $rules = [
            "email"=> "required|string|max_length[100]|valid_email",
            "password" => "required|string|max_length[100]|min_length[8]",
            "nome" => "required|string",
            "cnpj" => "required|string"
        ];
        try
        {
            $data = $this->request->getPost("parametros");
            if(!$this->validate($data, $rules))
            {
                return $this->response
                    ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->setJSON(
                        MapResponse::getJsonResponse(
                            Response::HTTP_UNPROCESSABLE_ENTITY, 
                            "error validando os dados", 
                            $this->validator->getErrors()
                        ));
            };

            $validated = $this->validator->getValidated();

            $userModel = new Models\User();
            $clienteModel = new Models\Cliente();
            $newUser = new Entities\User();
            $newCliente = new Entities\Cliente();
            $newUser->fill($validated);
            $newCliente->fill($validated);
            $userModel->save($newUser);
            $newCliente->user_id = $newUser->id;
            $clienteModel->save($newCliente);

            return $this->response->setJSON(
                    MapResponse::getJsonResponse(Response::HTTP_OK, 
                    "Dados retornados com sucesso", 
                    [...$newUser->toArray(), 'cliente' => $newCliente])
                );

        }
        catch(Exception $e)
        {
            return $this->response
                    ->setStatusCode(Response::HTTP_BAD_REQUEST)
                    ->setJSON(
                        MapResponse::getJsonResponse(
                            Response::HTTP_BAD_REQUEST, 
                            "Invalid request", 
                            $this->validator->getErrors()
                        ));
        }
    }
}
