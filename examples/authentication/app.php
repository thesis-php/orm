<?php

declare(strict_types=1);

namespace Authentication;

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Authentication\Identity\Repository;
use Ramsey\Uuid\Uuid;
use Thesis\ORM\EntityManager;
use Thesis\ORM\UnitOfWork;
use Thesis\Transaction;

require_once __DIR__ . '/../../vendor/autoload.php';

$postgres = new PostgresConnectionPool(
    new PostgresConfig(
        host: 'postgres',
        user: 'thesis',
        password: 'thesis',
        database: 'thesis',
    ),
);
$entityManager = new EntityManager(static fn() => Transaction\delegate($postgres->beginTransaction()));

$id = Uuid::uuid7();
$password1 = bin2hex(random_bytes(16));
$password2 = bin2hex(random_bytes(16));

$entityManager->inTransaction(static function (UnitOfWork $unitOfWork) use ($id, $password1): void {
    $repository = new Repository($unitOfWork);

    $identity = Identity::register(id: $id, password: $password1);

    $repository->add($identity);
});

$entityManager->inTransaction(static function (UnitOfWork $unitOfWork) use ($id, $password1, $password2): void {
    $repository = new Repository($unitOfWork);

    $identity = $repository->find($id) ?? throw new \Exception('Not registered');

    $identity->changePassword($password1, $password2);
});

$entityManager->inTransaction(static function (UnitOfWork $unitOfWork): void {
    dump(new Repository($unitOfWork)->findAll());
});
