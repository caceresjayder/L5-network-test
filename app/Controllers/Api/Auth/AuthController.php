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
use App\Helpers\JwtHelper;
use Throwable;

class AuthController extends BaseController
{
    protected $helpers = ['form'];

    public function register()
    {
        /** Validation Rules */
        $rules = [
            "parametros.email"=> "required|string|max_length[100]|valid_email|is_unique[users.email]",
            "parametros.password" => "required|string|max_length[100]|min_length[8]",
            "parametros.nome" => "required|string",
            "parametros.cnpj" => "required|string"
        ];

        /** Begin transaction */
        $db = db_connect();
        $db->transBegin();
        try
        {
            /** Validate form data */
            if(!$this->validate($rules))
            {
                return $this->response
                    ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->setJSON(
                        MapResponse::getJsonResponse(
                            Response::HTTP_UNPROCESSABLE_ENTITY, 
                            $this->validator->getErrors()
                        ));
            };

            /** Gets only validated data */
            $validated = $this->validator->getValidated();
            $data = $validated['parametros'];


            /** Init models and Create entities */
            $userModel = new Models\User();
            $clienteModel = new Models\Cliente();
            $user = new Entities\User($data);
            $cliente = new Entities\Cliente($data);

            /** Saves user Entity and gets the id stored */
            $userModel->save($user);
            $cliente->user_id = $userModel->getInsertID();

            /** saves cliente model */
            $clienteModel->save($cliente);

            /** Gets stored data */
            $registry = $userModel->select(
                "users.id, 
                users.email, 
                c.nome, 
                c.cnpj, 
                c.id as cliente_id, 
                users.created_at as cad")
                ->where('users.id', $cliente->user_id)
                ->join(
                    "clientes as c", 
                    "c.user_id = users.id", 
                    "inner")
                ->first();

            /** Maps response */
            $response = MapResponse::getJsonResponse(Response::HTTP_OK, 
            $registry->toArray());

            /** Finish transaction */
            $db->transCommit();

            /** Json Response */
            return $this->response->setJSON($response);

        }
        catch(Exception $e)
        {

            /** Rollback Transaction if error */
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

    public function login()
    {
        $rules = [
            "parametros.email" => "required|string|valid_email",
            "parametros.password" => "required|string|min_length[8]"
        ];

        try
        {
            /** Validate form data */
            if(!$this->validate($rules))
            {
                return $this->response
                    ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->setJSON(
                        MapResponse::getJsonResponse(
                            Response::HTTP_UNPROCESSABLE_ENTITY, 
                            $this->validator->getErrors()
                        ));
            };

            $userModel = new Models\User();
            $data = $this->validator->getValidated();
            $params = $data['parametros'];

            /** @var \App\Entities\User $user */
            $user = $userModel->select("id, password")->where("email", $params['email'])->first();

            if(!$user || !password_verify($params['password'], $user->password))
            {
                $response = MapResponse::getJsonResponse(Response::HTTP_UNAUTHORIZED);
                return $this->response->setStatusCode(Response::HTTP_UNAUTHORIZED)->setJSON($response);
            }

            $payload = [
                'sub' => $user->id
            ];

            $token = JwtHelper::generateToken($payload);

            $response = MapResponse::getJsonResponse(Response::HTTP_OK, ["token" => $token]);
        
            return $this->response->setStatusCode(Response::HTTP_OK)->setJSON($response);
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

    public function logout(){}

    public function userInfo(){

        try
        {
            $user = $_SESSION['user'];

            $userModel = new Models\User();
            /** Gets stored data */
            $registry = $userModel->select(
                "users.id, 
                users.email, 
                c.nome, 
                c.cnpj, 
                c.id as cliente_id, 
                users.created_at as cad")
                ->where('users.id', $user['id'])
                ->join(
                    "clientes as c", 
                    "c.user_id = users.id", 
                    "inner")
                ->first();

            if(!$registry)
            {
                $response = MapResponse::getJsonResponse(Response::HTTP_UNAUTHORIZED);
                return $this->response->setStatusCode(Response::HTTP_UNAUTHORIZED)->setJSON($response);
            }

            $response = MapResponse::getJsonResponse(Response::HTTP_OK, $registry->toArray());
            return $this->response->setStatusCode(Response::HTTP_OK)->setJSON($response);
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
