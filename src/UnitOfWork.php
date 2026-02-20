<?php

declare(strict_types=1);

namespace Thesis\ORM;

use Thesis\ORM\Exception\ConcurrentModification;
use Thesis\ORM\Exception\DuplicateEntity;
use Thesis\ORM\Exception\EntityNotManaged;
use Thesis\ORM\Exception\UnitOfWorkClosed;
use Thesis\ORM\Internal\ExistingEntity;
use Thesis\ORM\Internal\ManagedEntity;
use Thesis\ORM\Internal\NonExistingEntity;

/**
 * @api
 *
 * @template-covariant TTransaction of object
 */
final class UnitOfWork
{
    private bool $closed = false;

    /**
     * @var array<non-empty-string, ManagedEntity<TTransaction, *>>
     */
    private array $managed = [];

    /**
     * @param TTransaction $transaction
     */
    public function __construct(
        public readonly object $transaction,
    ) {}

    /**
     * @template TEntity of object
     * @template TCriteria
     * @param Persister<TTransaction, TEntity, TCriteria> $persister
     * @param class-string<TEntity> $class
     * @param \Closure(TEntity): ?non-empty-string $getId
     * @return Repository<TTransaction, TEntity, TCriteria>
     */
    public function repository(Persister $persister, string $class, \Closure $getId): Repository
    {
        $this->ensureNotClosed();

        return new Repository(
            unitOfWork: $this,
            persister: $persister,
            class: $class,
            getId: $getId,
        );
    }

    /**
     * @template TEntity of object
     * @template TCriteria
     * @param callable(TEntity): non-empty-string $key
     * @param Persister<TTransaction, TEntity, TCriteria> $persister
     * @param TCriteria $criteria
     * @return list<TEntity>
     */
    public function findBy(callable $key, Persister $persister, mixed $criteria): array
    {
        $this->ensureNotClosed();

        return array_map(
            fn(object $entity) => $this->manage($key($entity), $persister, $entity),
            iterator_to_array($persister->select($this->transaction, $criteria), preserve_keys: false),
        );
    }

    /**
     * @template TEntity of object
     * @param non-empty-string $key
     * @param Persister<TTransaction, TEntity, *> $persister
     * @param TEntity $entity
     * @return TEntity
     */
    private function manage(string $key, Persister $persister, object $entity): object
    {
        /** @var ?ManagedEntity<TTransaction, TEntity> */
        $managed = $this->managed[$key] ?? null;

        if ($managed instanceof ExistingEntity) {
            return $managed->entity;
        }

        if ($managed instanceof NonExistingEntity && $managed->entity !== null) {
            $this->managed[$key] = new ExistingEntity($persister, $managed->entity);

            return $managed->entity;
        }

        $this->managed[$key] = new ExistingEntity($persister, $entity);

        return $entity;
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

        /** @var ManagedEntity<TTransaction, TEntity> */
        $managed = $this->managed[$key] ??= new NonExistingEntity($persister);
        $managed->add($entity);
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

        /** @var ManagedEntity<TTransaction, TEntity> */
        $managed = $this->managed[$key] ??= new NonExistingEntity($persister);
        $managed->remove($entity);
    }

    /**
     * @throws DuplicateEntity|ConcurrentModification
     */
    public function flush(): void
    {
        $this->ensureNotClosed();

        try {
            foreach ($this->managed as $entity) {
                $entity->flush($this->transaction);
            }
        } finally {
            $this->close();
        }
    }

    public function close(): void
    {
        $this->managed = [];
        $this->closed = true;
    }

    private function ensureNotClosed(): void
    {
        if ($this->closed) {
            throw new UnitOfWorkClosed();
        }
    }
}
