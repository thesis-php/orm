<?php

declare(strict_types=1);

namespace Thesis\ORM;

/**
 * @api
 * @template TEntity of object
 */
final readonly class Changes
{
    /**
     * @template MEntity of object
     * @param list<self<MEntity>> $changes
     * @return self<MEntity>
     */
    public static function merge(array $changes): self
    {
        return new self(
            inserts: array_merge(...array_column($changes, 'inserts')),
            updates: array_merge(...array_column($changes, 'updates')),
            deletes: array_merge(...array_column($changes, 'deletes')),
        );
    }

    /**
     * @param list<TEntity> $inserts
     * @param list<Update<TEntity>> $updates
     * @param list<TEntity> $deletes
     */
    public function __construct(
        public array $inserts = [],
        public array $updates = [],
        public array $deletes = [],
    ) {}
}
