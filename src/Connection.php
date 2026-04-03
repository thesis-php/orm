<?php

declare(strict_types=1);

namespace Thesis\ORM;

/**
 * @api
 *
 * @template-covariant TConnection of object
 * @template-covariant TTransaction of object
 */
interface Connection
{
    /**
     * @var TConnection
     * @phpstan-ignore generics.variance
     */
    public object $inner { get; }

    /**
     * @return Transaction<TTransaction>
     */
    public function beginTransaction(IsolationLevel $isolationLevel = IsolationLevel::ReadCommitted): Transaction;
}
