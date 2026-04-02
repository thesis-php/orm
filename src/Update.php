<?php

declare(strict_types=1);

namespace Thesis\ORM;

/**
 * @api
 *
 * @template TEntity of object
 */
final readonly class Update
{
    /**
     * @param TEntity $entity
     * @param TEntity $snapshot
     */
    public function __construct(
        public object $entity,
        public object $snapshot,
    ) {}
}
