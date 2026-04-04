<?php

declare(strict_types=1);

namespace Thesis\ORM;

use Thesis\ORM\Exception\DuplicateEntity;
use Thesis\ORM\Exception\EntityNotManaged;
use Thesis\ORM\Exception\UnitOfWorkClosed;
use Thesis\ORM\Internal\ExistingEntity;
use Thesis\ORM\Internal\ManagedEntity;
use Thesis\ORM\Internal\NonExistingEntity;

/**
 * @api
 *
 * @template-covariant TConnection of object
 * @template-covariant TTransaction of object
 * @template TEntity of object
 * @template-contravariant TCriteria
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
     * @param Session<TConnection, TTransaction> $session
     * @param Persister<TConnection, TTransaction, TEntity, TCriteria> $persister
     * @param \Closure(TEntity): ?non-empty-string $getId
     */
    public function __construct(
        private readonly Session $session,
        private readonly Persister $persister,
        private readonly \Closure $getId,
    ) {}

    /**
     * @param TCriteria $criteria
     * @return list<TEntity>
     */
    public function findBy(mixed $criteria): array
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
                $this->persister->findBy($this->session, $criteria),
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

        ($this->entities[$this->key($entity)] ??= $this->createNonExistingEntity())->add($entity);
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

    /**
     * @internal
     */
    public function persist(): void
    {
        $this->ensureNotClosed();

        try {
            $this->persister->persist($this->session, Changes::merge(array_map(
                static fn(ManagedEntity $entity) => $entity->collectChanges(),
                array_values($this->entities),
            )));
        } finally {
            $this->close();
        }
    }

    public private(set) bool $isClosed = false;

    private function ensureNotClosed(): void
    {
        if ($this->isClosed) {
            throw new UnitOfWorkClosed();
        }
    }

    private function close(): void
    {
        $this->entities = [];
        $this->isClosed = true;
    }
}
