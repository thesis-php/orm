<?php

declare(strict_types=1);

namespace Thesis\ORM\Internal;

use Thesis\ORM\Persister;
use Thesis\ORM\Session;

/**
 * @internal
 *
 * @template TEntity of object
 * @template TChangeSet of array<mixed>|object
 */
final class Changes
{
    /**
     * @var list<TEntity>
     */
    private array $insertEntities = [];

    /**
     * @var list<TChangeSet>
     */
    private array $updateChangeSets = [];

    /**
     * @var list<TEntity>
     */
    private array $deleteEntities = [];

    /**
     * @param \Closure(TEntity, TEntity): ?TChangeSet $calculateChangeSet
     */
    public function __construct(
        private readonly \Closure $calculateChangeSet,
    ) {}

    /**
     * @param TEntity $entity
     */
    public function insert(object $entity): void
    {
        $this->insertEntities[] = $entity;
    }

    /**
     * @param TEntity $entity
     * @param TEntity $snapshot
     */
    public function update(object $entity, object $snapshot): void
    {
        $changeSet = ($this->calculateChangeSet)($entity, $snapshot);

        if ($changeSet !== null) {
            $this->updateChangeSets[] = $changeSet;
        }
    }

    /**
     * @param TEntity $entity
     */
    public function delete(object $entity): void
    {
        $this->deleteEntities[] = $entity;
    }

    /**
     * @template TExecutor of object
     * @param Session<TExecutor> $session
     * @param Persister<TExecutor, TEntity, *, TChangeSet> $persister
     */
    public function persist(Session $session, Persister $persister): void
    {
        if ($this->insertEntities !== []) {
            $persister->insert($session->transaction, $this->insertEntities);
        }

        if ($this->updateChangeSets !== []) {
            $persister->update($session->transaction, $this->updateChangeSets);
        }

        if ($this->deleteEntities !== []) {
            $persister->delete($session->transaction, $this->deleteEntities);
        }
    }
}
