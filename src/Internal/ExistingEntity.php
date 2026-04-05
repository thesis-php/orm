<?php

declare(strict_types=1);

namespace Thesis\ORM\Internal;

use Thesis\ORM\Exception\DuplicateEntity;
use Thesis\ORM\Exception\EntityNotManaged;

/**
 * @internal
 *
 * @template TEntity of object
 * @implements ManagedEntity<TEntity>
 */
final class ExistingEntity implements ManagedEntity
{
    private bool $remove = false;

    /**
     * @var TEntity
     */
    private readonly object $snapshot;

    /**
     * @param TEntity $entity
     */
    public function __construct(
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

    public function collectChanges(Changes $changes): void
    {
        if ($this->remove) {
            $changes->delete($this->entity);
        } else {
            $changes->update($this->entity, $this->snapshot);
        }
    }
}
