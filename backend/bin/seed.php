<?php

declare(strict_types=1);

/**
 * Crea los usuarios de demostración (uno por rol + un segundo agente y solicitante).
 * Es idempotente: no duplica usuarios que ya existen.
 * Uso: php bin/seed.php
 */

use Kodex\Config;
use Kodex\Database\Connection;
use Kodex\Domain\Role;
use Kodex\Repositories\UserRepository;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = Config::load(dirname(__DIR__) . '/.env');
$users = new UserRepository(Connection::fromConfig($config));

$demo = [
    ['Ana Administradora', 'admin@kodex.pe',     'Admin123!',   Role::Admin],
    ['Carlos Agente',      'agente@kodex.pe',    'Agente123!',  Role::Agent],
    ['Diana Agente',       'agente2@kodex.pe',   'Agente123!',  Role::Agent],
    ['Luis Solicitante',   'usuario@kodex.pe',   'Usuario123!', Role::Requester],
    ['María Solicitante',  'usuario2@kodex.pe',  'Usuario123!', Role::Requester],
];

foreach ($demo as [$name, $email, $password, $role]) {
    if ($users->emailExists($email)) {
        fwrite(STDOUT, "  = {$email} ya existe\n");
        continue;
    }
    $users->create($name, $email, password_hash($password, PASSWORD_DEFAULT), $role);
    fwrite(STDOUT, "  + {$email} ({$role->value})\n");
}

fwrite(STDOUT, "Usuarios de demostración listos.\n");
