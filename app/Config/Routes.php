<?php

use CodeIgniter\Router\RouteCollection;
use \App\Controllers\Api;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

/** API ROUTES */
$routes->group('api', function (RouteCollection $routes) {
    /** Auth Routes */
    /** Public Routes */
    $routes->post('auth/register', [Api\Auth\AuthController::class, 'register']);
    $routes->post('auth/login', [Api\Auth\AuthController::class, 'login']);
    /** Authenticated routes */
    $routes->get('auth/user-info', [Api\Auth\AuthController::class, 'userInfo']);
    $routes->delete('auth/logout', [Api\Auth\AuthController::class, 'logout']);


    /** V1 Routes */
    $routes->group('v1', function (RouteCollection $routes) {

        /** clientes */
        $routes->get('clientes', [Api\V1\Clientes\ClientesController::class, 'index']);
        $routes->get('clientes/(:num)', [Api\V1\Clientes\ClientesController::class, 'show']);
        $routes->post('clientes', [Api\V1\Clientes\ClientesController::class, 'create']);
        $routes->put('clientes/(:num)', [Api\V1\Clientes\ClientesController::class, 'update']);
        $routes->delete('clientes/(:num)', [Api\V1\Clientes\ClientesController::class, 'delete']);

        /** Produtos */
        $routes->get('produtos', [Api\V1\Produtos\ProdutosController::class, 'index']);
        $routes->get('produtos/(:num)', [Api\V1\Produtos\ProdutosController::class, 'show']);
        $routes->post('produtos', [Api\V1\Produtos\ProdutosController::class, 'create']);
        $routes->put('produtos/(:num)', [Api\V1\Produtos\ProdutosController::class, 'update']);
        $routes->delete('produtos/(:num)', [Api\V1\Produtos\ProdutosController::class, 'delete']);


        /** Pedidos */
        $routes->get('pedidos', [Api\V1\Pedidos\PedidosController::class, 'index']);
        $routes->get('pedidos/(:num)', [Api\V1\Pedidos\PedidosController::class, 'show']);
        $routes->post('pedidos', [Api\V1\Pedidos\PedidosController::class, 'create']);
        $routes->put('pedidos/(:num)', [Api\V1\Pedidos\PedidosController::class, 'update']);
        $routes->delete('pedidos/(:num)', [Api\V1\Pedidos\PedidosController::class, 'delete']);
    });
});

