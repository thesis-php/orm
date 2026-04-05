<?php

declare(strict_types=1);

namespace Thesis\ORM;

use Thesis\ORM\Exception\ConcurrentModification;
use Thesis\ORM\Exception\DuplicateEntity;

/**
 * @api
 *
 * @template-contravariant TExecutor of object
 * @template TEntity of object
 * @template-contravariant TCriteria
 */
interface Persister
{
    /**
     * @param TExecutor $executor
     * @param TCriteria $criteria
     * @return iterable<TEntity>
     */
    public function find(object $executor, mixed $criteria): iterable;

    /**
     * @param TExecutor $executor
     * @param Changes<TEntity> $changes
     * @throws DuplicateEntity
     * @throws ConcurrentModification
     */
    public function persist(object $executor, Changes $changes): void;
}
