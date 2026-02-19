<?php

declare(strict_types=1);

namespace Thesis\ORM;

/**
 * @api
 *
 * @template-covariant TEntity of object
 */
final readonly class EntityVersion
{
    /**
     * @param TEntity $entity
     * @param positive-int $version
     */
    public function __construct(
        public object $entity,
        public int $version,
    ) {}
}
