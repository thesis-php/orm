<?php

declare(strict_types=1);

namespace Thesis\ORM;

/**
 * @api
 *
 * @template-covariant TExecutor of object
 */
interface TransactionHandle
{
    /**
     * @var TExecutor
     * @phpstan-ignore generics.variance
     */
    public object $executor { get; }

    public function commit(): void;

    public function rollback(): void;

    /**
     * @return self<TExecutor>
     */
    public function beginTransaction(): self;
}
