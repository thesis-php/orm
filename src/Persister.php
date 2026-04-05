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
 * @template-contravariant TChangeSet of array<mixed>|object
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
     * @param non-empty-list<TEntity> $entities
     * @throws DuplicateEntity
     */
    public function insert(object $executor, array $entities): void;

    /**
     * @param TExecutor $executor
     * @param non-empty-list<TChangeSet> $changeSets
     * @throws ConcurrentModification
     */
    public function update(object $executor, array $changeSets): void;

    /**
     * @param TExecutor $executor
     * @param non-empty-list<TEntity> $entities
     * @throws ConcurrentModification
     */
    public function delete(object $executor, array $entities): void;
}
