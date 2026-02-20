<?php

declare(strict_types=1);

namespace Thesis\ORM;

use Thesis\ORM\Exception\DuplicateEntity;
use Thesis\ORM\Exception\EntityNotManaged;

/**
 * @api
 *
 * @template TTransaction of object
 * @template TEntity of object
 * @template TId of int|string|array|object
 */
final readonly class Repository
{
    /**
     * @param UnitOfWork<TTransaction> $unitOfWork
     * @param Persister<TTransaction, TEntity, TId> $persister
     * @param class-string<TEntity> $class
     * @param \Closure(TEntity): ?TId $getId
     * @param \Closure(TId): non-empty-string $stringifyId
     */
    public function __construct(
        private UnitOfWork $unitOfWork,
        private Persister $persister,
        private string $class,
        private \Closure $getId,
        private \Closure $stringifyId,
    ) {}

    /**
     * @param TId $id
     * @return ?TEntity
     */
    public function find(mixed $id): ?object
    {
        return $this->unitOfWork->find($this->keyFromId($id), $this->persister, $id);
    }

    /**
     * @param TEntity $entity
     * @throws DuplicateEntity
     */
    public function add(object $entity): void
    {
        $this->unitOfWork->add($this->keyFromEntity($entity), $this->persister, $entity);
    }

    /**
     * @param TEntity $entity
     * @throws EntityNotManaged
     */
    public function remove(object $entity): void
    {
        $this->unitOfWork->remove($this->keyFromEntity($entity), $this->persister, $entity);
    }

    /**
     * @param TId $id
     * @return non-empty-string
     */
    private function keyFromId(mixed $id): string
    {
        return $this->class . ':' . ($this->stringifyId)($id);
    }

    /**
     * @param TEntity $entity
     * @return non-empty-string
     */
    private function keyFromEntity(object $entity): string
    {
        $id = ($this->getId)($entity);

        if ($id === null) {
            return $this->class . '#' . spl_object_id($entity);
        }

        return $this->keyFromId($id);
    }
}
