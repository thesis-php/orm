<?php

declare(strict_types=1);

namespace Thesis\ORM;

use Thesis\ORM\Exception\DuplicateEntity;
use Thesis\ORM\Exception\EntityNotManaged;
use Thesis\ORM\Exception\RepositoryClosed;
use Thesis\ORM\Internal\Changes;
use Thesis\ORM\Internal\ExistingEntity;
use Thesis\ORM\Internal\ManagedEntity;
use Thesis\ORM\Internal\NonExistingEntity;

/**
 * @api
 *
 * @template-covariant TExecutor of object
 * @template TEntity of object
 * @template-contravariant TCriteria
 * @template-contravariant TChangeSet of array|object
 */
final class Repository
{
    /**
     * @var array<non-empty-string, ManagedEntity<TEntity>>
     */
    private array $entities = [];

    /**
     * @internal
     *
     * @param Session<TExecutor> $session
     * @param Persister<TExecutor, TEntity, TCriteria, TChangeSet> $persister
     * @param \Closure(TEntity): ?non-empty-string $getId
     * @param \Closure(TEntity, TEntity): ?TChangeSet $calculateChangeSet
     * @param-out \Closure(): void $persist
     */
    public function __construct(
        private readonly Session $session,
        private readonly Persister $persister,
        private readonly \Closure $getId,
        private readonly \Closure $calculateChangeSet,
        mixed &$persist,
    ) {
        $persist = $this->persist(...);
    }

    /**
     * @param TCriteria $criteria
     * @return list<TEntity>
     */
    public function find(mixed $criteria): array
    {
        return $this->doFind($this->session->transaction, $criteria);
    }

    /**
     * @param TCriteria $criteria
     * @return list<TEntity>
     */
    public function findOutsideTransaction(mixed $criteria): array
    {
        return $this->doFind($this->session->connection, $criteria);
    }

    /**
     * @param TExecutor $executor
     * @param TCriteria $criteria
     * @return list<TEntity>
     */
    private function doFind(object $executor, mixed $criteria): array
    {
        $this->ensureNotClosed();

        return array_map(
            function (object $entity): object {
                $key = $this->key($entity);
                $managed = $this->entities[$key] ?? null;

                if ($managed instanceof ExistingEntity) {
                    return $managed->entity;
                }

                if ($managed !== null && $managed->entity !== null) {
                    $this->entities[$key] = new ExistingEntity($managed->entity);

                    return $managed->entity;
                }

                $this->entities[$key] = new ExistingEntity($entity);

                return $entity;
            },
            iterator_to_array(
                $this->persister->find($executor, $criteria),
                preserve_keys: false,
            ),
        );
    }

    /**
     * @param TEntity $entity
     * @throws DuplicateEntity
     */
    public function add(object $entity): void
    {
        $this->ensureNotClosed();

        ($this->entities[$this->key($entity)] ??= $this->createNonExistingEntity())->add($entity);
    }

    /**
     * @param TEntity $entity
     * @throws EntityNotManaged
     */
    public function remove(object $entity): void
    {
        $this->ensureNotClosed();

        ($this->entities[$this->key($entity)] ??= $this->createNonExistingEntity())->remove($entity);
    }

    /**
     * @param TEntity $entity
     * @return non-empty-string
     */
    private function key(object $entity): string
    {
        return ($this->getId)($entity) ?? (string) spl_object_id($entity);
    }

    /**
     * @return NonExistingEntity<TEntity>
     */
    private function createNonExistingEntity(): NonExistingEntity
    {
        /** @var NonExistingEntity<TEntity> */
        return new NonExistingEntity();
    }

    private function persist(): void
    {
        $this->ensureNotClosed();

        try {
            $changes = new Changes($this->calculateChangeSet);

            foreach ($this->entities as $entity) {
                $entity->collectChanges($changes);
            }

            $changes->persist($this->session, $this->persister);
        } finally {
            $this->close();
        }
    }

    private bool $isClosed = false;

    private function ensureNotClosed(): void
    {
        if ($this->isClosed) {
            throw new RepositoryClosed();
        }
    }

    private function close(): void
    {
        $this->entities = [];
        $this->isClosed = true;
    }
}
