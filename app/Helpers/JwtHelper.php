<?php 
namespace App\Helpers;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
helper('date');

class JwtHelper {
    public static function generateToken(array $payload): string {    
        $SECRET = getenv("JWT_SECRET") ?? throw new \Exception("Not jwt secret setting in environment");
        $EXPIRATION = getenv("JWT_EXPIRATION") ?? throw new \Exception("Not expiration setting in environment");

        $payload['iat'] = now();
        $payload['nbf'] = strtotime("+$EXPIRATION");



        try
        {
            $token =  JWT::encode($payload, $SECRET, 'HS256');
            return $token;
            
        }
        catch (Exception $e)
        {
            throw $e;
        }

        
    }

    public static function verifyToken(string $token){
        $SECRET = getenv("JWT_SECRET") ?? throw new \Exception("Not jwt secret setting in environment");

        try
        {
            $payload = (array) JWT::decode($token, new Key($SECRET, 'HS256'));
            return $payload;
        }
        catch(Exception $e)
        {
            return false;
        }
    }
}