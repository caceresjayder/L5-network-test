<?php 
namespace App\Helpers;

class Paginator {
    public static function paginate(string $key, array $models, int $page, int $limit, string $url) 
    {
        $count = count($models);
        $prevPage = $page > 0 ? $page - 1 : 0;
        $nextPage = $count >= $limit ? $page + 1 : null;
        return [
            "$key" => $models,
            "links" => [
                "prev" => $page === 0 ? null :"$url?page=$prevPage",
                "next" => $nextPage !== null ? "$url?page=$nextPage" : null,
            ]
        ];
    }
}