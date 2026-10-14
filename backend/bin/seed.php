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

// Mismos nombres y áreas que el Figma, para que la demo se vea igual que el diseño.
$demo = [
    ['Lucía Ramos',   'admin@kodex.pe',    'Admin123!',   Role::Admin,     'TI'],
    ['Jorge Paredes', 'agente@kodex.pe',   'Agente123!',  Role::Agent,     'Soporte'],
    ['María Vega',    'agente2@kodex.pe',  'Agente123!',  Role::Agent,     'Soporte'],
    ['Kevin Castro',  'agente3@kodex.pe',  'Agente123!',  Role::Agent,     'Infraestructura'],
    ['Carlos Medina', 'usuario@kodex.pe',  'Usuario123!', Role::Requester, 'Finanzas'],
    ['Ana Torres',    'usuario2@kodex.pe', 'Usuario123!', Role::Requester, 'Recursos Humanos'],
];

foreach ($demo as [$name, $email, $password, $role, $area]) {
    if ($users->emailExists($email)) {
        fwrite(STDOUT, "  = {$email} ya existe\n");
        continue;
    }
    $users->create($name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $area);
    fwrite(STDOUT, "  + {$email} ({$role->value})\n");
}

fwrite(STDOUT, "Usuarios de demostración listos.\n");
