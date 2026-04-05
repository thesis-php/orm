<?php

declare(strict_types=1);

namespace Thesis\ORM;

/**
 * @api
 *
 * @template TExecutor of object
 */
final readonly class EntityManager
{
    /**
     * @param ConnectionHandle<TExecutor> $connectionHandle
     */
    public function __construct(
        private ConnectionHandle $connectionHandle,
    ) {}

    /**
     * @template T
     * @param callable(Session<TExecutor>): T $function
     * @return T
     */
    public function session(callable $function, IsolationLevel $isolationLevel = IsolationLevel::ReadCommitted): mixed
    {
        return Session::in($this->connectionHandle, $isolationLevel, $function);
    }
}
