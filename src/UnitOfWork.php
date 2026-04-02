<?php

declare(strict_types=1);

namespace Thesis\ORM;

use Thesis\ORM\Exception\ConcurrentModification;
use Thesis\ORM\Exception\DuplicateEntity;
use Thesis\ORM\Exception\EntityNotManaged;
use Thesis\ORM\Exception\UnitOfWorkClosed;
use Thesis\ORM\Internal\Existing;
use Thesis\ORM\Internal\ManagedEntity;
use Thesis\ORM\Internal\ManagedPersister;
use Thesis\ORM\Internal\NonExisting;

/**
 * @api
 *
 * @template-covariant TTransaction of object
 */
final class UnitOfWork
{
    private bool $closed = false;

    /**
     * @var array<non-empty-string, ManagedEntity<*>>
     */
    private array $identityMap = [];

    /**
     * @var array<int, ManagedPersister<TTransaction, *>>
     */
    private array $persisters = [];

    /**
     * @param TTransaction $transaction
     */
    public function __construct(
        public readonly object $transaction,
    ) {}

    /**
     * @template TEntity of object
     * @template TCriteria
     * @param class-string<TEntity> $class
     * @param Persister<TTransaction, TEntity, TCriteria> $persister
     * @param \Closure(TEntity): ?non-empty-string $getId
     * @return Repository<TTransaction, TEntity, TCriteria>
     */
    public function repository(string $class, Persister $persister, \Closure $getId): Repository
    {
        $this->ensureNotClosed();

        return new Repository(
            unitOfWork: $this,
            class: $class,
            persister: $persister,
            getId: $getId,
        );
    }

    /**
     * @template TEntity of object
     * @template TCriteria
     * @param callable(TEntity): non-empty-string $keyFactory
     * @param Persister<TTransaction, TEntity, TCriteria> $persister
     * @param TCriteria $criteria
     * @return list<TEntity>
     */
    public function findBy(callable $keyFactory, Persister $persister, mixed $criteria): array
    {
        $this->ensureNotClosed();

        return array_map(
            function (object $entity) use ($keyFactory, $persister): object {
                $key = $keyFactory($entity);

                /** @var ?ManagedEntity<TEntity> */
                $managedEntity = $this->identityMap[$key] ?? null;

                if ($managedEntity !== null) {
                    return $managedEntity->resolveFound($entity);
                }

                $managedEntity = new ManagedEntity(new Existing($entity));
                $this->identityMap[$key] = $managedEntity;
                $this->managePersister($persister, $managedEntity);

                return $entity;
            },
            iterator_to_array(
                $persister->select($this->transaction, $criteria),
                preserve_keys: false,
            ),
        );
    }

    /**
     * @template TEntity of object
     * @param non-empty-string $key
     * @param Persister<TTransaction, TEntity, *> $persister
     * @param TEntity $entity
     * @throws DuplicateEntity
     */
    public function add(string $key, Persister $persister, object $entity): void
    {
        $this->ensureNotClosed();

        /** @var ?ManagedEntity<TEntity> */
        $managedEntity = $this->identityMap[$key] ?? null;

        if ($managedEntity !== null) {
            $managedEntity->add($entity);

            return;
        }

        $managedEntity = new ManagedEntity(new NonExisting($entity));
        $this->identityMap[$key] = $managedEntity;
        $this->managePersister($persister, $managedEntity);
    }

    /**
     * @template TEntity of object
     * @param non-empty-string $key
     * @param Persister<TTransaction, TEntity, *> $persister
     * @param TEntity $entity
     * @throws EntityNotManaged
     */
    public function remove(string $key, Persister $persister, object $entity): void
    {
        $this->ensureNotClosed();

        /** @var ?ManagedEntity<TEntity> */
        $managedEntity = $this->identityMap[$key] ?? null;

        if ($managedEntity !== null) {
            $managedEntity->remove($entity);

            return;
        }

        /** @var ManagedEntity<TEntity> */
        $managedEntity = new ManagedEntity(new NonExisting());
        $this->identityMap[$key] = $managedEntity;
        $this->managePersister($persister, $managedEntity);
    }

    /**
     * @template TEntity of object
     * @param Persister<TTransaction, TEntity, *> $persister
     * @param ManagedEntity<TEntity> $entity
     */
    private function managePersister(Persister $persister, ManagedEntity $entity): void
    {
        /** @var ManagedPersister<TTransaction, TEntity> */
        $managedPersister = $this->persisters[spl_object_id($persister)] ??= new ManagedPersister($persister);
        $managedPersister->addEntity($entity);
    }

    /**
     * @throws DuplicateEntity|ConcurrentModification
     */
    public function flush(): void
    {
        $this->ensureNotClosed();

        try {
            foreach ($this->persisters as $persister) {
                $persister->persist($this->transaction);
            }
        } finally {
            $this->close();
        }
    }

    public function close(): void
    {
        $this->identityMap = [];
        $this->persisters = [];
        $this->closed = true;
    }

    private function ensureNotClosed(): void
    {
        if ($this->closed) {
            throw new UnitOfWorkClosed();
        }
    }
}
