<?php

declare(strict_types=1);

namespace Thesis\ORM;

use Thesis\ORM\Exception\ConcurrentModification;
use Thesis\ORM\Exception\DuplicateEntity;

/**
 * @api
 *
 * @template-contravariant TConnection of object
 * @template-contravariant TTransaction of object
 * @template TEntity of object
 * @template-contravariant TCriteria
 */
interface Persister
{
    /**
     * @param Session<TConnection, TTransaction> $session
     * @param TCriteria $criteria
     * @return iterable<TEntity>
     */
    public function findBy(Session $session, mixed $criteria): iterable;

    /**
     * @param Session<TConnection, TTransaction> $session
     * @param Changes<TEntity> $changes
     * @throws DuplicateEntity
     * @throws ConcurrentModification
     */
    public function persist(Session $session, Changes $changes): void;
}
