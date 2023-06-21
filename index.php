<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$users = new Classes\User;
$cuentas = new Classes\Cuenta;
$movimientos = new Classes\Movimiento;

Flight::route('POST /auth', [$users, 'auth']);
Flight::route('GET /users/@id/cuentas', [$users, 'cuentas']);
Flight::route('GET /users/@id/cuentas/@nro', [$users, 'cuenta']);
Flight::route('GET /users/@id/cuentas/@nro/saldo', [$users, 'saldo']);

Flight::route('GET /cuentas/@nro/movimientos', [$cuentas, 'movimientos']);
Flight::route('GET /cuentas/@nro/user', [$cuentas, 'user']);

Flight::route('POST /movimiento', [$movimientos, 'movimiento']);

Flight::start();

?>