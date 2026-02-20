<?php

declare(strict_types=1);

namespace Thesis\ORM;

use Thesis\ORM\Exception\DuplicateEntity;
use Thesis\ORM\Exception\EntityNotManaged;

/**
 * @api
 *
 * @template-covariant TTransaction of object
 * @template TEntity of object
 * @template-contravariant TCriteria
 */
final readonly class Repository
{
    /**
     * @param UnitOfWork<TTransaction> $unitOfWork
     * @param class-string<TEntity> $class
     * @param Persister<TTransaction, TEntity, TCriteria> $persister
     * @param \Closure(TEntity): ?non-empty-string $getId
     */
    public function __construct(
        private UnitOfWork $unitOfWork,
        private string $class,
        private Persister $persister,
        private \Closure $getId,
    ) {}

    /**
     * @param TEntity $entity
     * @return non-empty-string
     */
    public function key(object $entity): string
    {
        $id = ($this->getId)($entity);

        if ($id === null) {
            return $this->class . '#' . spl_object_id($entity);
        }

        return $this->class . ':' . $id;
    }

    /**
     * @param TCriteria $criteria
     * @return list<TEntity>
     */
    public function findBy(mixed $criteria): array
    {
        return $this->unitOfWork->findBy($this->key(...), $this->persister, $criteria);
    }

    /**
     * @param TEntity $entity
     * @throws DuplicateEntity
     */
    public function add(object $entity): void
    {
        $this->unitOfWork->add($this->key($entity), $this->persister, $entity);
    }

    /**
     * @param TEntity $entity
     * @throws EntityNotManaged
     */
    public function remove(object $entity): void
    {
        $this->unitOfWork->remove($this->key($entity), $this->persister, $entity);
    }
}
