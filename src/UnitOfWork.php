<?php

declare(strict_types=1);

namespace Thesis\ORM;

use Thesis\ORM\Internal\ManagedEntity;

/**
 * @api
 *
 * @template-covariant TTransaction of object
 */
final class UnitOfWork
{
    private bool $closed = false;

    /**
     * @var array<non-empty-string, ManagedEntity<TTransaction, *, *>>
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
     * @template TId of int|string|array|object
     * @param non-empty-string $key
     * @param Persister<TTransaction, TEntity, TId> $persister
     * @param TId $id
     * @return ?TEntity
     */
    public function find(string $key, Persister $persister, mixed $id): ?object
    {
        /** @var ManagedEntity<TTransaction, TEntity, TId> */
        $managed = $this->managed[$key] ??= ManagedEntity::load($persister, $this->transaction, $id);

        return $managed->entity;
    }

    /**
     * @template TEntity of object
     * @template TId of int|string|array|object
     * @param non-empty-string $key
     * @param Persister<TTransaction, TEntity, TId> $persister
     * @param TEntity $entity
     */
    public function add(string $key, Persister $persister, object $entity): void
    {
        /** @var ManagedEntity<TTransaction, TEntity, TId> */
        $managed = $this->managed[$key] = ManagedEntity::new($persister);
        $managed->add($entity);
    }

    /**
     * @template TEntity of object
     * @template TId of int|string|array|object
     * @param non-empty-string $key
     * @param Persister<TTransaction, TEntity, TId> $persister
     * @param TEntity $entity
     */
    public function remove(string $key, Persister $persister, object $entity): void
    {
        /** @var ManagedEntity<TTransaction, TEntity, TId> */
        $managed = $this->managed[$key] = ManagedEntity::new($persister);
        $managed->remove($entity);
    }

    /**
     * @throws OptimisticLockFailed
     */
    public function flush(): void
    {
        $this->ensureNotClosed();

        try {
            foreach ($this->managed as $entity) {
                $entity->flush($this->transaction);
            }
        } finally {
            $this->managed = [];
            $this->closed = true;
        }
    }

    private function ensureNotClosed(): void
    {
        if ($this->closed) {
            throw new \LogicException();
        }
    }
}
