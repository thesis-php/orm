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
interface ManagedEntity
{
    /**
     * @var ?TEntity
     */
    public ?object $entity { get; }

    /**
     * @param TEntity $entity
     * @throws DuplicateEntity
     */
    public function add(object $entity): void;

    /**
     * @param TEntity $entity
     * @throws EntityNotManaged
     */
    public function remove(object $entity): void;

    /**
     * @return Changes<TEntity>
     */
    public function collectChanges(): Changes;
}
