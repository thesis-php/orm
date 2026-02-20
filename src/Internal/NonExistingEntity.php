<?php

declare(strict_types=1);

namespace Thesis\ORM\Internal;

use Thesis\ORM\Exception\DuplicateEntity;
use Thesis\ORM\Exception\EntityNotManaged;

/**
 * @internal
 *
 * @template TTransaction of object
 * @template TEntity of object
 * @template TId of int|string|array|object
 * @extends ManagedEntity<TTransaction, TEntity, TId>
 */
final class NonExistingEntity extends ManagedEntity
{
    public private(set) ?object $entity = null;

    public function add(object $entity): void
    {
        if ($this->entity === null) {
            $this->entity = $entity;

            return;
        }

        if ($this->entity !== $entity) {
            throw new DuplicateEntity();
        }
    }

    public function remove(object $entity): void
    {
        if ($this->entity === null) {
            return;
        }

        if ($this->entity !== $entity) {
            throw new EntityNotManaged();
        }

        $this->entity = null;
    }

    public function flush(object $transaction): void
    {
        if ($this->entity !== null) {
            $this->persister->insert($transaction, $this->entity);
        }
    }
}
