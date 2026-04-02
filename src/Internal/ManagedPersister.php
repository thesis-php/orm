<?php

declare(strict_types=1);

namespace Thesis\ORM\Internal;

use Thesis\ORM\Changes;
use Thesis\ORM\Persister;

/**
 * @internal
 *
 * @template TTransaction of object
 * @template TEntity of object
 */
final class ManagedPersister
{
    /**
     * @var list<ManagedEntity<TEntity>>
     */
    private array $entities = [];

    /**
     * @param Persister<TTransaction, TEntity, *> $persister
     */
    public function __construct(
        private readonly Persister $persister,
    ) {}

    /**
     * @param ManagedEntity<TEntity> $entity
     */
    public function addEntity(ManagedEntity $entity): void
    {
        $this->entities[] = $entity;
    }

    /**
     * @param TTransaction $transaction
     */
    public function persist(object $transaction): void
    {
        $this->persister->persist($transaction, Changes::merge(array_column($this->entities, 'changes')));
    }
}
