<?php

declare(strict_types=1);

namespace Thesis\ORM;

/**
 * @api
 *
 * @template-covariant TExecutor of object
 */
interface ConnectionHandle
{
    /**
     * @var TExecutor
     * @phpstan-ignore generics.variance
     */
    public object $executor { get; }

    /**
     * @return TransactionHandle<TExecutor>
     */
    public function beginTransaction(IsolationLevel $isolationLevel = IsolationLevel::ReadCommitted): TransactionHandle;
}
