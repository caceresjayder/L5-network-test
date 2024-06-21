<?php

namespace App\Filters;

use App\Helpers\JwtHelper;
use App\Helpers\MapResponse;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;

class AuthTokenFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!$request->hasHeader('authorization')) {
            $response = MapResponse::getJsonResponse(Response::HTTP_UNAUTHORIZED, "Bearer token not present");
            return response()->setJSON($response);
        };

        try {

            $header = $request->getHeaderLine("authorization");
            $token = explode(" ", $header)[1];
            $decode = JwtHelper::verifyToken($token);

            if (!$decode) 
            {
                throw new \Exception("");
            }

            $_SESSION['user'] = [ 'id' => $decode['sub'] ];

            return $request;

        } catch (\Exception $e) {
            $response = MapResponse::getJsonResponse(Response::HTTP_UNAUTHORIZED, "Invalid bearer token");
            return response()->setStatusCode(Response::HTTP_UNAUTHORIZED)->setJSON($response);
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
