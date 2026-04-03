<?php

declare(strict_types=1);

namespace Thesis\ORM;

/**
 * @api
 *
 * @template-covariant TTransaction of object
 */
interface Transaction
{
    /**
     * @var TTransaction
     * @phpstan-ignore generics.variance
     */
    public object $inner { get; }

    public function commit(): void;

    public function rollback(): void;

    // /**
    //  * @return self<TTransaction>
    //  */
    // public function beginTransaction(): self;
}
