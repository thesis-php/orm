<?php

declare(strict_types=1);

namespace Thesis\ORM\Internal;

use Thesis\ORM\Changes;
use Thesis\ORM\Exception\DuplicateEntity;
use Thesis\ORM\Exception\EntityNotManaged;
use Thesis\ORM\Update;

/**
 * @internal
 *
 * @template TEntity of object
 */
final class Existing
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

    /**
     * @param TEntity $entity
     * @throws DuplicateEntity
     */
    public function add(object $entity): void
    {
        if ($entity !== $this->entity) {
            throw new DuplicateEntity();
        }

        $this->remove = false;
    }

    /**
     * @param TEntity $entity
     * @throws EntityNotManaged
     */
    public function remove(object $entity): void
    {
        if ($entity !== $this->entity) {
            throw new EntityNotManaged();
        }

        $this->remove = true;
    }

    /**
     * @return Changes<TEntity>
     */
    public function collectChanges(): Changes
    {
        if ($this->remove) {
            return new Changes(deletes: [$this->entity]);
        }

        return new Changes(updates: [new Update($this->entity, $this->snapshot)]);
    }
}
