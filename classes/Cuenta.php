<?php

namespace Classes;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use flight;

class Cuenta{
    private $table = 'cuenta';
    private $db;

    function __construct(){
        Flight::register('db','PDO', array($_ENV['DB_CONNECTION'].':host='.$_ENV['DB_HOST'].';dbname='.$_ENV['DB_NAME'],$_ENV['DB_USER'],$_ENV['DB_PASS']));
        $this->db = Flight::db();
    }

    function movimientos($nro){
        if (!$this->validateToken()){
            Flight::halt(403, json_encode([
                "error" => "No tienes permisos para acceder a este recurso",
                "status" => "error" 
            ]));
        }
        $query = $this->db->prepare("SELECT * FROM movimiento WHERE origen = :nro OR destino = :nro ORDER BY fecha DESC");
        $query->execute([":nro" => $nro]);
        $data = $query->fetchAll();
    
        $array = [];
        foreach ($data as $row){
            if ($row['origen'] == $nro){
                $array[] = [
                    "tipo" => "pago",
                    "monto" => $row['monto'],
                    "destino" => $row['destino'],
                    "fecha" => $row['fecha']
                ];
            } else {
                $array[] = [
                    "tipo" => "cobro",
                    "monto" => $row['monto'],
                    "origen" => $row['origen'],
                    "fecha" => $row['fecha']
                ];
            }
        }
    
        Flight::json([
            "total_rows" => $query->rowCount(),
            "rows" => $array
        ]);
    }

    function user($nro){
        if (!$this->validateToken()){
            Flight::halt(403, json_encode([
                "error" => "No tienes permisos para acceder a este recurso",
                "status" => "error" 
            ]));
        }
        $query = $this->db->prepare("SELECT usuario.id, usuario.username, cliente.nombre as cliente FROM usuario, cliente, $this->table WHERE usuario.cliente = cliente.ci AND cliente.ci = $this->table.cliente AND $this->table.nro = :nro");
        $query->execute([":nro" => $nro]);
        $data = $query->fetch(); 
        $array = [
            "id" => $data['id'],
            "username" => $data['username'],
            "cliente" => $data['cliente']
        ];
    
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