<?php

namespace App\Helpers;

class MapResponse {
    public static function getJsonResponse(int $status, string $message, array $data = []) {
        return [
            "cabecalho" => [
                "status" => $status,
                "message" => $message,
            ],
            "retorno"=> $data, 
        ];
    }
}