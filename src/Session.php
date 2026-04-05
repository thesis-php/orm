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
     * @var array<class-string, Repository<TExecutor, *, *>>
     */
    private array $repositories = [];

    /**
     * @template TEntity of object
     * @template TCriteria
     * @param class-string<TEntity> $class
     * @param Persister<TExecutor, TEntity, TCriteria> $persister
     * @param \Closure(TEntity): ?non-empty-string $getId
     * @return Repository<TExecutor, TEntity, TCriteria>
     */
    public function repository(string $class, Persister $persister, \Closure $getId): Repository
    {
        $this->ensureNotClosed();

        /** @var Repository<TExecutor, TEntity, TCriteria> */
        return $this->repositories[$class] ??= new Repository($this, $persister, $getId);
    }

    public function commit(): void
    {
        $this->ensureNotClosed();

        try {
            foreach ($this->repositories as $repository) {
                $repository->persist();
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
        $this->repositories = [];
        $this->isClosed = true;
    }
}
