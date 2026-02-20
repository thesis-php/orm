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
     * @var ORM\Repository<PostgresLink, Identity, ?UuidInterface>
     */
    private ORM\Repository $repository;

    /**
     * @param ORM\UnitOfWork<PostgresLink> $unitOfWork
     * @param ORM\Persister<PostgresLink, Identity, ?UuidInterface> $persister
     */
    public function __construct(ORM\UnitOfWork $unitOfWork, ORM\Persister $persister = new Persister())
    {
        $this->repository = $unitOfWork->repository(
            persister: $persister,
            class: Identity::class,
            getId: static fn(Identity $identity) => $identity->id->toString(),
        );
    }

    public function find(UuidInterface $id): ?Identity
    {
        return $this->repository->findBy($id)[0] ?? null;
    }

    /**
     * @return list<Identity>
     */
    public function findAll(): array
    {
        return $this->repository->findBy(null);
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
