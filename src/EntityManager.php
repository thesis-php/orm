<?php

declare(strict_types=1);

namespace Thesis\ORM;

/**
 * @api
 *
 * @template TConnection of object
 * @template TTransaction of object
 */
final readonly class EntityManager
{
    /**
     * @param Connection<TConnection, TTransaction> $connection
     */
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @template T
     * @param callable(Session<TConnection, TTransaction>): T $function
     * @return T
     */
    public function session(callable $function, IsolationLevel $isolationLevel = IsolationLevel::ReadCommitted): mixed
    {
        $session = new Session($this->connection, $isolationLevel);

        $result = $function($session);

        if (!$session->isClosed) {
            $session->commit();
        }

        return $result;
    }
}
