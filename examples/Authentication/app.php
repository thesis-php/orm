<?php

declare(strict_types=1);

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Authentication\ChangePasswordHandler;
use Authentication\Identity\Repository;
use Authentication\RegisterHandler;
use Ramsey\Uuid\Uuid;
use Thesis\ORM\EntityManager;
use Thesis\ORM\Transaction\Amp;
use Thesis\ORM\UnitOfWork;

require_once __DIR__ . '/../../vendor/autoload.php';

$postgres = new PostgresConnectionPool(
    new PostgresConfig(
        host: 'localhost',
        user: 'thesis',
        password: 'thesis',
        database: 'thesis',
    ),
);
$em = new EntityManager(static fn() => new Amp($postgres->beginTransaction()));

$id = Uuid::uuid7();
$password1 = bin2hex(random_bytes(16));
$password2 = bin2hex(random_bytes(16));

$em->inTransaction(static function (UnitOfWork $unitOfWork) use ($id, $password1): void {
    $handler = new RegisterHandler(new Repository($unitOfWork));
    $handler($id, $password1);
});

$em->inTransaction(static function (UnitOfWork $unitOfWork) use ($id, $password1, $password2): void {
    $handler = new ChangePasswordHandler(new Repository($unitOfWork));
    $handler($id, $password1, $password2);
});
