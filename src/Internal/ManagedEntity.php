<?php

declare(strict_types=1);

namespace Thesis\ORM\Internal;

use Thesis\ORM\Exception\ConcurrentModification;
use Thesis\ORM\Exception\DuplicateEntity;
use Thesis\ORM\Exception\EntityNotManaged;

/**
 * @internal
 *
 * @template TTransaction of object
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
     * @param TTransaction $transaction
     * @throws DuplicateEntity|ConcurrentModification
     */
    public function flush(object $transaction): void;
}
