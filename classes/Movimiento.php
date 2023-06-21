<?php

namespace Classes;


use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use flight;

class Movimiento{
    private $table = 'movimiento';
    private $db;

    function __construct(){
        Flight::register('db','PDO', array($_ENV['DB_CONNECTION'].':host='.$_ENV['DB_HOST'].';dbname='.$_ENV['DB_NAME'],$_ENV['DB_USER'],$_ENV['DB_PASS']));
        $this->db = Flight::db();
    }

    function movimiento(){
        if (!$this->validateToken()){
            Flight::halt(403, json_encode([
                "error" => "No tienes permisos para acceder a este recurso",
                "status" => "error" 
            ]));
        }

        $origen = Flight::request()->data->origen;
        $monto = Flight::request()->data->monto;
        $destino = Flight::request()->data->destino;
        
        $query = $this->db->prepare('CALL actualizar_saldos(:origen, :monto, :destino)');
        $array = [
            "error" => "Hubo un problema al insertar datos, intenta mas tarde",
            "status" => "error"
        ];
        if ($query->execute([":origen" => $origen, ":monto" => $monto, ":destino" => $destino])){
            $array = [
                "data" => [
                    "origen" => $origen,
                    "monto" => $monto,
                    "destino" => $destino,
                ],
                "status" => "success"
            ];
        }
        
        Flight::json($array);
    }

    function getToken(){
        $headers = apache_request_headers();
        if (!isset($headers['authorization'])){
            Flight::halt(403, json_encode([
                "error" => "Unauthenticated request",
                "status" => "error" 
            ]));
        }
        $authorization = $headers['authorization'];
        $authorizationArray = explode(" ", $authorization);
        $token = $authorizationArray[1];
        $key = $_ENV['JWT_SECRET_KEY'];
        try{
            return JWT::decode($token, new Key($key, 'HS256'));
        } catch (\Throwable $th){
            Flight::halt(403, json_encode([
                "error" => $th->getMessage(),
                "status" => "error" 
            ]));
        }
    }

    function validateToken(){
        $info = $this->getToken();
        $db = Flight::db();
        $query = $db->prepare("SELECT * FROM usuario WHERE id = :id");
        $query->execute([":id" => $info->data]);
        $rows = $query->fetchcolumn();
        return $rows;
    }
}

?>