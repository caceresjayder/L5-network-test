<?php

namespace App\Helpers;

use CodeIgniter\HTTP\Response;

class MapResponse {
    public static function getJsonResponse(int $status, array $data = []) {

        $message = "";
        if($status === Response::HTTP_OK) $message = "Dados retornados com sucesso";
        if($status === Response::HTTP_UNPROCESSABLE_ENTITY) $message = "Verifique os dados e tente de novo";
        if($status === Response::HTTP_BAD_REQUEST) $message = "Algo salio mal com a sua requisicao";
        if($status === Response::HTTP_UNAUTHORIZED) $message = "Acesso nao autorizado";
        if($status === Response::HTTP_NOT_FOUND) $message = "Nao se encontrou o que estava procurando";
        if($status === Response::HTTP_CREATED) $message = "Creado com sucesso";

        return json_encode([
            "cabecalho" => [
                "status" => $status,
                "message" => $message,
            ],
            "retorno"=> $data, 
        ]);
    }
}