<?php

namespace Classes;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use flight;

// {
//     "username":"usuario1",
//     "password":"123456"
// }
class User {
    private $db;
    private $table = 'usuario';

    function __construct(){
        Flight::register('db','PDO', array($_ENV['DB_CONNECTION'].':host='.$_ENV['DB_HOST'].';dbname='.$_ENV['DB_NAME'],$_ENV['DB_USER'],$_ENV['DB_PASS']));
        $this->db = Flight::db();
    }

    private function encriptar($password)
    {
        return md5($password);
    }

    function auth(){
        $username = Flight::request()->data->username;
        $password = Flight::request()->data->password;
        $password = $this->encriptar($password);
        $query = $this->db->prepare("SELECT * FROM $this->table WHERE username = :username AND password = :password");
    
        $array = [
            "error" => "No se pudo autenticar, intenta mas tarde",
            "status" => "error"
        ];
    
        if ($query->execute([":username" => $username, ":password" => $password]) && $query->rowCount() > 0){
            $user = $query->fetch();
            $now = strtotime("now");
            $key = $_ENV['JWT_SECRET_KEY']; 
            $payload = [
                'exp' => $now + 3600,
                'data' => $user['id'] //username debe ser unico
            ];
    
            $jwt = JWT::encode($payload, $key, 'HS256');
            $array = ["token" => $jwt, "data" => $user['id']];
        }
        
        Flight::json($array);
    }

    function cuentas($id){
        if (!$this->validateToken()){
            Flight::halt(403, json_encode([
                "error" => "No tienes permisos para acceder a este recurso",
                "status" => "error" 
            ]));
        }
        $query = $this->db->prepare("SELECT cuenta.nro,  cuenta.saldo, tipo_cuenta.nombre AS tipo, banco.nombre AS banco FROM cuenta, $this->table, cliente, tipo_cuenta, banco WHERE $this->table.id = :id AND $this->table.cliente = cliente.ci AND cliente.ci = cuenta.cliente AND cuenta.tipo_cuenta = tipo_cuenta.id AND cuenta.banco = banco.id");
        $query->execute([":id" => $id]);
        $data = $query->fetchAll();
    
        $array = [];
        foreach ($data as $row){
            $array[] = [
                "nro" => $row['nro'],
                "saldo" => $row['saldo'],
                "tipo" => $row['tipo'],
                "banco" => $row['banco']
            ];
        }
    
        Flight::json([
            "total_rows" => $query->rowCount(),
            "rows" => $array
        ]);
    }

    function cuenta($id, $nro){
        if (!$this->validateToken()){
            Flight::halt(403, json_encode([
                "error" => "No tienes permisos para acceder a este recurso",
                "status" => "error" 
            ]));
        }
        $query = $this->db->prepare("SELECT cuenta.nro,  cuenta.saldo, tipo_cuenta.nombre AS tipo, banco.nombre AS banco FROM cuenta, $this->table, cliente, tipo_cuenta, banco WHERE $this->table.id = :id AND $this->table.cliente = cliente.ci AND cliente.ci = cuenta.cliente AND cuenta.nro = :nro AND cuenta.tipo_cuenta = tipo_cuenta.id AND cuenta.banco = banco.id");
        $query->execute([":id" => $id, ":nro" => $nro]);
        $data = $query->fetch();
        $array = [
            "nro" => $data['nro'],
            "saldo" => $data['saldo'],
            "tipo" => $data['tipo'],
            "banco" => $data['banco']
        ];
    
        Flight::json($array);
    }

    function saldo($id, $nro){
        if (!$this->validateToken()){
            Flight::halt(403, json_encode([
                "error" => "No tienes permisos para acceder a este recurso",
                "status" => "error" 
            ]));
        }
        $query = $this->db->prepare("SELECT cuenta.saldo FROM cuenta, $this->table, cliente WHERE $this->table.id = :id AND $this->table.cliente = cliente.ci AND cliente.ci = cuenta.cliente AND cuenta.nro = :nro");
        $query->execute([":id" => $id, ":nro" => $nro]);
        $data = $query->fetch(); 
        $array = [
            "saldo" => $data['saldo'],
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
        $query = $db->prepare("SELECT * FROM $this->table WHERE id = :id");
        $query->execute([":id" => $info->data]);
        $rows = $query->fetchcolumn();
        return $rows;
    }
}

?>