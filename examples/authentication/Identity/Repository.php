<?php

declare(strict_types=1);

namespace Authentication\Identity;

use Amp\Postgres\PostgresLink;
use Authentication\Identity;
use Ramsey\Uuid\UuidInterface;
use Thesis\ORM;

final readonly class Repository
{
    /**
     * @var ORM\Repository<PostgresLink, Identity, ?UuidInterface, Identity>
     */
    private ORM\Repository $repository;

    /**
     * @param ORM\Session<PostgresLink> $session
     * @param ORM\Persister<PostgresLink, Identity, ?UuidInterface, Identity> $persister
     */
    public function __construct(
        ORM\Session $session,
        ORM\Persister $persister = new Persister(),
    ) {
        $this->repository = $session->repository(
            class: Identity::class,
            persister: $persister,
            getId: static fn(Identity $identity) => $identity->id->toString(),
            calculateChangeSet: static function (Identity $entity, Identity $snapshot): ?Identity {
                if ($entity->passwordHash === $snapshot->passwordHash) {
                    return null;
                }

                return $entity;
            },
        );
    }

    public function find(UuidInterface $id): ?Identity
    {
        return $this->repository->find($id)[0] ?? null;
    }

    /**
     * @return list<Identity>
     */
    public function findAll(): array
    {
        return $this->repository->find(null);
    }

    public function add(Identity $identity): void
    {
        $this->repository->add($identity);
    }

    public function remove(Identity $identity): void
    {
        $this->repository->remove($identity);
    }
}
