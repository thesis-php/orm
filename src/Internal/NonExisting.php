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
final class NonExisting
{
    /**
     * @param ?TEntity $entity
     */
    public function __construct(
        public private(set) ?object $entity = null,
    ) {}

    /**
     * @param TEntity $entity
     * @throws DuplicateEntity
     */
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

    /**
     * @param TEntity $entity
     * @throws EntityNotManaged
     */
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

    /**
     * @return Changes<TEntity>
     */
    public function collectChanges(): Changes
    {
        return new Changes(inserts: $this->entity === null ? [] : [$this->entity]);
    }
}
