<?php

declare(strict_types=1);

namespace Authentication;

use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Authentication\Identity\Repository;
use Ramsey\Uuid\Uuid;
use Thesis\ORM\AmpPostgres\ConnectionHandle;
use Thesis\ORM\EntityManager;
use Thesis\ORM\Session;
use function Amp\async;

require_once __DIR__ . '/../../vendor/autoload.php';

$postgres = new PostgresConnectionPool(
    new PostgresConfig(
        host: 'localhost',
        user: 'postgres',
        password: 'postgres',
        database: 'postgres',
    ),
);

$postgres->query(
    <<<'SQL'
        create table if not exists identity (
            id uuid primary key,
            password_hash text not null,
            version smallint default 1 not null
        )
        SQL,
);

$entityManager = new EntityManager(new ConnectionHandle($postgres));

async(static function () use ($entityManager): void {
    $id = Uuid::uuid7();
    $password = bin2hex(random_bytes(16));
    $newPassword = bin2hex(random_bytes(16));

    $entityManager->session(static function (Session $session) use ($id, $password): void {
        $repository = new Repository($session);

        $identity = Identity::register($id, $password);

        $repository->add($identity);
    });

    $entityManager->session(static function (Session $session) use ($id, $password, $newPassword): void {
        $repository = new Repository($session);

        $identity = $repository->find($id) ?? throw new \Exception('Not registered');

        $identity->changePassword($password, $newPassword);
    });

    $identities = $entityManager->session(
        static fn(Session $session) => new Repository($session)->findAll(),
    );

    dump($identities);
})->await();
