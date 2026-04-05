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
final class NonExistingEntity implements ManagedEntity
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

    public function collectChanges(Changes $changes): void
    {
        if ($this->entity !== null) {
            $changes->insert($this->entity);
        }
    }
}
