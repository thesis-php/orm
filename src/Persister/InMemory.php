<?php

declare(strict_types=1);

namespace Thesis\ORM\Persister;

use Thesis\ORM\Persister;

/**
 * @api
 *
 * @template TEntity of object
 * @template-contravariant TCriteria
 * @implements Persister<object, TEntity, TCriteria>
 */
final class InMemory implements Persister
{
    /**
     * @var ?\Closure(TEntity, TCriteria): bool
     */
    private readonly ?\Closure $filter;

    /**
     * @var ?\Closure(TEntity, TEntity): (-1|0|1)
     */
    private readonly ?\Closure $sorter;

    /**
     * @var list<TEntity>
     */
    public array $entities;

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
        $this->filter = $filter === null ? null : $filter(...);
        $this->sorter = $sorter === null ? null : $sorter(...);
        $this->entities = $entities ?? [];
    }

    public function select(object $transaction, mixed $criteria): iterable
    {
        $entities = $this->entities;

        if ($this->filter !== null) {
            $entities = array_values(
                array_filter(
                    $this->entities,
                    fn(object $entity) => ($this->filter)($entity, $criteria),
                ),
            );
        }

        if ($this->sorter !== null) {
            usort($entities, $this->sorter);
        }

        return $entities;
    }

    public function insert(object $transaction, object $entity): void
    {
        $this->entities[] = $entity;
    }

    public function update(object $transaction, object $entity, object $snapshot): void {}

    public function delete(object $transaction, object $entity): void
    {
        $this->entities = array_values(
            array_filter(
                $this->entities,
                static fn(object $persisted) => $persisted !== $entity,
            ),
        );
    }
}
