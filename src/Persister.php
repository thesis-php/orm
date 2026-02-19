<?php

declare(strict_types=1);

namespace Thesis\ORM;

/**
 * @api
 *
 * @template-contravariant TTransaction of object
 * @template TEntity of object
 * @template TId of int|string|array|object
 */
interface Persister
{
    /**
     * @param TTransaction $transaction
     * @param TId $id
     * @return ?EntityVersion<TEntity>
     */
    public function select(object $transaction, mixed $id): ?EntityVersion;

    /**
     * @param TTransaction $transaction
     * @param TEntity $entity
     * @throws DuplicateEntity|OptimisticLockFailed
     */
    public function insert(object $transaction, object $entity): void;

    /**
     * @param TTransaction $transaction
     * @param TEntity $entity
     * @param positive-int $version
     * @param TEntity $snapshot
     * @throws OptimisticLockFailed
     */
    public function update(object $transaction, object $entity, int $version, object $snapshot): void;

    /**
     * @param TTransaction $transaction
     * @param TEntity $entity
     * @param positive-int $version
     * @throws OptimisticLockFailed
     */
    public function delete(object $transaction, object $entity, int $version): void;
}
