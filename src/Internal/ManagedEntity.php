<?php

declare(strict_types=1);

namespace Thesis\ORM\Internal;

use Thesis\ORM\DuplicateEntity;
use Thesis\ORM\OptimisticLockFailed;
use Thesis\ORM\Persister;

/**
 * @internal
 *
 * @template TTransaction of object
 * @template TEntity of object
 * @template TId of int|string|array|object
 */
abstract class ManagedEntity
{
    /**
     * @template MTransaction of object
     * @template MEntity of object
     * @template MId of int|string|array|object
     * @param Persister<MTransaction, MEntity, MId> $persister
     * @param MTransaction $transaction
     * @param MId $id
     * @return self<MTransaction, MEntity, MId>
     */
    final public static function load(Persister $persister, object $transaction, mixed $id): self
    {
        $entity = $persister->select($transaction, $id);

        if ($entity === null) {
            return new NonExistingEntity($persister);
        }

        return new ExistingEntity($persister, $entity);
    }

    /**
     * @template MTransaction of object
     * @template MEntity of object
     * @template MId of int|string|array|object
     * @param Persister<MTransaction, MEntity, MId> $persister
     * @return self<MTransaction, MEntity, MId>
     */
    final public static function new(Persister $persister): self
    {
        return new NonExistingEntity($persister);
    }

    /**
     * @param Persister<TTransaction, TEntity, TId> $persister
     */
    protected function __construct(
        protected readonly Persister $persister,
    ) {}

    /**
     * @var ?TEntity
     */
    abstract public ?object $entity { get; }

    /**
     * @param TEntity $entity
     */
    abstract public function add(object $entity): void;

    /**
     * @param TEntity $entity
     */
    abstract public function remove(object $entity): void;

    /**
     * @param TTransaction $transaction
     * @throws DuplicateEntity|OptimisticLockFailed
     */
    abstract public function flush(object $transaction): void;
}
