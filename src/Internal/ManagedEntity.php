<?php

declare(strict_types=1);

namespace Thesis\ORM\Internal;

use Thesis\ORM\Changes;
use Thesis\ORM\Exception\DuplicateEntity;
use Thesis\ORM\Exception\EntityNotManaged;

/**
 * @internal
 *
 * @template TEntity of object
 */
final class ManagedEntity
{
    /**
     * @param Existing<TEntity>|NonExisting<TEntity> $state
     */
    public function __construct(
        private Existing|NonExisting $state,
    ) {}

    /**
     * @param TEntity $entity
     * @return TEntity
     */
    public function resolveFound(object $entity): object
    {
        if ($this->state instanceof Existing) {
            return $this->state->entity;
        }

        if ($this->state->entity !== null) {
            $entity = $this->state->entity;
            $this->state = new Existing($entity);

            return $entity;
        }

        $this->state = new Existing($entity);

        return $entity;
    }

    /**
     * @param TEntity $entity
     * @throws DuplicateEntity
     */
    public function add(object $entity): void
    {
        $this->state->add($entity);
    }

    /**
     * @param TEntity $entity
     * @throws EntityNotManaged
     */
    public function remove(object $entity): void
    {
        $this->state->remove($entity);
    }

    /**
     * @var Changes<TEntity>
     */
    public Changes $changes { get => $this->state->collectChanges(); }
}
