<?php

declare(strict_types=1);

namespace Authentication\Identity;

use Amp\Postgres\PostgresLink;
use Authentication\Identity;
use Ramsey\Uuid\UuidInterface as Uuid;
use Thesis\ORM\Repository as ORMRepository;
use Thesis\ORM\UnitOfWork;

final readonly class Repository
{
    /**
     * @var ORMRepository<PostgresLink, Identity, Uuid>
     */
    private ORMRepository $repository;

    /**
     * @param UnitOfWork<PostgresLink> $unitOfWork
     */
    public function __construct(UnitOfWork $unitOfWork)
    {
        $this->repository = new ORMRepository(
            unitOfWork: $unitOfWork,
            persister: new Persister(),
            class: Identity::class,
            getId: static fn(Identity $identity) => $identity->id,
            stringifyId: static fn(Uuid $id) => $id->toString(),
        );
    }

    public function find(Uuid $id): ?Identity
    {
        return $this->repository->find($id);
    }

    public function add(Identity $identity): void
    {
        $this->repository->add($identity);
    }
}
