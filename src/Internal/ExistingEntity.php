<?php

declare(strict_types=1);

namespace Thesis\ORM\Internal;

use Thesis\ORM\EntityVersion;
use Thesis\ORM\Persister;

/**
 * @internal
 *
 * @template TTransaction of object
 * @template TEntity of object
 * @template TId of int|string|array|object
 * @extends ManagedEntity<TTransaction, TEntity, TId>
 */
final class ExistingEntity extends ManagedEntity
{
    private bool $remove = false;

    /**
     * @var TEntity
     */
    public readonly object $entity;

    /**
     * @var positive-int
     */
    private readonly int $version;

    /**
     * @var TEntity
     */
    private readonly object $snapshot;

    /**
     * @param Persister<TTransaction, TEntity, TId> $persister
     * @param EntityVersion<TEntity> $entityVersion
     */
    protected function __construct(
        Persister $persister,
        EntityVersion $entityVersion,
    ) {
        parent::__construct($persister);

        $this->entity = $entityVersion->entity;
        $this->version = $entityVersion->version;
        $this->snapshot = clone $entityVersion->entity;
    }

    public function add(object $entity): void
    {
        if ($entity !== $this->entity) {
            throw new \LogicException();
        }

        $this->remove = false;
    }

    public function remove(object $entity): void
    {
        if ($entity !== $this->entity) {
            throw new \LogicException();
        }

        $this->remove = true;
    }

    public function flush(object $transaction): void
    {
        if ($this->remove) {
            $this->persister->delete($transaction, $this->entity, $this->version);
        } else {
            $this->persister->update($transaction, $this->entity, $this->version, $this->snapshot);
        }
    }
}
