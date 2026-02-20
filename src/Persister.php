<?php

declare(strict_types=1);

namespace Thesis\ORM;

use Thesis\ORM\Exception\ConcurrentModification;
use Thesis\ORM\Exception\DuplicateEntity;

/**
 * @api
 *
 * @template-contravariant TTransaction of object
 * @template TEntity of object
 * @template-contravariant TCriteria
 */
interface Persister
{
    /**
     * @param TTransaction $transaction
     * @param TCriteria $criteria
     * @return iterable<TEntity>
     */
    public function select(object $transaction, mixed $criteria): iterable;

    /**
     * @param TTransaction $transaction
     * @param TEntity $entity
     * @throws DuplicateEntity
     */
    public function insert(object $transaction, object $entity): void;

    /**
     * @param TTransaction $transaction
     * @param TEntity $entity
     * @param TEntity $snapshot
     * @throws ConcurrentModification
     */
    public function update(object $transaction, object $entity, object $snapshot): void;

    /**
     * @param TTransaction $transaction
     * @param TEntity $entity
     * @throws ConcurrentModification
     */
    public function delete(object $transaction, object $entity): void;
}
