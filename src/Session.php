<?php

declare(strict_types=1);

namespace Thesis\ORM;

use Thesis\ORM\Exception\SessionClosed;
use Thesis\Sync\Once;

/**
 * @api
 *
 * @template-covariant TExecutor of object
 */
final class Session
{
    /**
     * @template T
     * @template TInExecutor of object
     * @param ConnectionHandle<TInExecutor> $connectionHandle
     * @param callable(self<TInExecutor>): T $function
     * @return T
     */
    public static function in(
        ConnectionHandle $connectionHandle,
        IsolationLevel $isolationLevel,
        callable $function,
    ): mixed {
        $session = new self($connectionHandle, $isolationLevel);

        $result = $function($session);

        if (!$session->isClosed) {
            $session->commit();
        }

        return $result;
    }

    /**
     * @var TExecutor
     * @phpstan-ignore generics.variance
     */
    public object $connection {
        get => $this->connectionHandle->executor;
    }

    /**
     * @var ?Once<TransactionHandle<TExecutor>>
     */
    private ?Once $transactionHandle = null;

    /**
     * @var TExecutor
     * @phpstan-ignore generics.variance
     */
    public object $transaction {
        get {
            $this->ensureNotClosed();

            if ($this->transactionHandle === null) {
                $connection = $this->connectionHandle;
                $level = $this->isolationLevel;
                $this->transactionHandle = new Once(static fn() => $connection->beginTransaction($level));
            }

            try {
                return $this->transactionHandle->await()->executor;
            } catch (\Throwable $exception) {
                // the session cannot be used if beginTransaction() failed
                $this->close();

                throw $exception;
            }
        }
    }

    /**
     * @internal
     *
     * @param ConnectionHandle<TExecutor> $connectionHandle
     */
    private function __construct(
        private readonly ConnectionHandle $connectionHandle,
        private readonly IsolationLevel $isolationLevel = IsolationLevel::ReadCommitted,
    ) {}

    /**
     * @var list<\Closure(): void>
     */
    private array $persists = [];

    /**
     * @template TEntity of object
     * @template TCriteria
     * @template TChangeSet of array<mixed>|object
     * @param Persister<TExecutor, TEntity, TCriteria, TChangeSet> $persister
     * @param callable(TEntity): ?non-empty-string $getId
     * @param callable(TEntity $entity, TEntity $snapshot): ?TChangeSet $calculateChangeSet
     * @return Repository<TExecutor, TEntity, TCriteria, TChangeSet>
     */
    public function createRepository(Persister $persister, callable $getId, callable $calculateChangeSet): Repository
    {
        $this->ensureNotClosed();

        $repository = new Repository(
            session: $this,
            persister: $persister,
            getId: $getId(...),
            calculateChangeSet: $calculateChangeSet(...),
            persist: $persist,
        );

        $this->persists[] = $persist;

        return $repository;
    }

    public function commit(): void
    {
        $this->ensureNotClosed();

        try {
            foreach ($this->persists as $persist) {
                $persist();
            }

            $this->transactionHandle?->await()->commit();
        } catch (\Throwable $exception) {
            $this->transactionHandle?->await()->rollback();

            throw $exception;
        } finally {
            $this->close();
        }
    }

    public function rollback(): void
    {
        $this->ensureNotClosed();

        try {
            $this->transactionHandle?->await()->rollback();
        } finally {
            $this->close();
        }
    }

    private bool $isClosed = false;

    private function ensureNotClosed(): void
    {
        if ($this->isClosed) {
            throw new SessionClosed();
        }
    }

    private function close(): void
    {
        $this->transactionHandle = null;
        $this->persists = [];
        $this->isClosed = true;
    }
}
