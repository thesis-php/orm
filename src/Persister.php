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
     * @param Changes<TEntity> $changes
     * @throws DuplicateEntity
     * @throws ConcurrentModification
     */
    public function persist(object $transaction, Changes $changes): void;
}
