<?php

declare(strict_types=1);

namespace Thesis\ORM\Persister;

use Thesis\ORM\Persister;

/**
 * @api
 *
 * @template TEntity of object
 * @template-contravariant TCriteria
 * @implements Persister<object, TEntity, TCriteria, array<mixed>|object>
 */
final class InMemory implements Persister
{
    /**
     * @var list<TEntity>
     */
    public array $entities {
        get => iterator_to_array($this->storage, preserve_keys: false);
    }

    /**
     * @var \SplObjectStorage<TEntity, null>
     */
    private readonly \SplObjectStorage $storage;

    /**
     * @var ?\Closure(TEntity, TCriteria): bool
     */
    private readonly ?\Closure $filter;

    /**
     * @var ?\Closure(TEntity, TEntity): (-1|0|1)
     */
    private readonly ?\Closure $sorter;

    /**
     * @param ?callable(TEntity, TCriteria): bool $filter
     * @param ?callable(TEntity, TEntity): (-1|0|1) $sorter
     * @param ?list<TEntity> $entities
     */
    public function __construct(
        ?callable $filter = null,
        ?callable $sorter = null,
        // default null prevents TEntity = never inference from an empty list
        ?array $entities = null,
    ) {
        $this->storage = new \SplObjectStorage();

        $this->filter = $filter === null ? null : $filter(...);
        $this->sorter = $sorter === null ? null : $sorter(...);

        foreach ($entities ?? [] as $entity) {
            $this->storage->offsetSet($entity);
        }
    }

    public function find(object $executor, mixed $criteria): iterable
    {
        $entities = $this->entities;

        if ($this->filter !== null) {
            $entities = array_filter(
                $entities,
                fn(object $entity) => ($this->filter)($entity, $criteria),
            );
        }

        if ($this->sorter !== null) {
            usort($entities, $this->sorter);
        }

        return array_values($entities);
    }

    public function insert(object $executor, array $entities): void
    {
        foreach ($entities as $entity) {
            $this->storage->offsetSet($entity);
        }
    }

    public function update(object $executor, array $changeSets): void {}

    public function delete(object $executor, array $entities): void
    {
        foreach ($entities as $entity) {
            $this->storage->offsetUnset($entity);
        }
    }
}
