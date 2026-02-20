<?php

declare(strict_types=1);

namespace Thesis\ORM\Internal;

use Thesis\ORM\Exception\DuplicateEntity;
use Thesis\ORM\Exception\EntityNotManaged;
use Thesis\ORM\Persister;

/**
 * @internal
 *
 * @template TTransaction of object
 * @template TEntity of object
 * @implements ManagedEntity<TTransaction, TEntity>
 */
final class ExistingEntity implements ManagedEntity
{
    private bool $remove = false;

    /**
     * @var TEntity
     */
    private readonly object $snapshot;

    /**
     * @param Persister<TTransaction, TEntity, *> $persister
     * @param TEntity $entity
     */
    public function __construct(
        private readonly Persister $persister,
        public readonly object $entity,
    ) {
        $this->snapshot = clone $entity;
    }

    public function add(object $entity): void
    {
        if ($entity !== $this->entity) {
            throw new DuplicateEntity();
        }

        $this->remove = false;
    }

    public function remove(object $entity): void
    {
        if ($entity !== $this->entity) {
            throw new EntityNotManaged();
        }

        $this->remove = true;
    }

    public function flush(object $transaction): void
    {
        if ($this->remove) {
            $this->persister->delete($transaction, $this->entity);
        } else {
            $this->persister->update($transaction, $this->entity, $this->snapshot);
        }
    }
}
