<?php

declare(strict_types=1);

namespace Thesis\ORM;

use Thesis\ORM\Exception\UnitOfWorkClosed;

/**
 * @api
 *
 * @template-covariant TConnection of object
 * @template-covariant TTransaction of object
 */
final class Session
{
    /**
     * @var TConnection
     * @phpstan-ignore generics.variance
     */
    public object $connection {
        get => $this->connectionHandle->inner;
    }

    /**
     * @var ?Transaction<TTransaction>
     */
    private ?Transaction $transactionHandle = null;

    /**
     * @var TTransaction
     * @phpstan-ignore generics.variance
     */
    public object $lazyTransaction {
        get {
            $this->ensureNotClosed();

            $this->transactionHandle ??= $this->connectionHandle->beginTransaction($this->isolationLevel);

            return $this->transactionHandle->inner;
        }
    }

    /**
     * @var array<class-string, Repository<TConnection, TTransaction, *, *>>
     */
    private array $repositories = [];

    /**
     * @internal
     *
     * @param Connection<TConnection, TTransaction> $connectionHandle
     */
    public function __construct(
        private readonly Connection $connectionHandle,
        private readonly IsolationLevel $isolationLevel = IsolationLevel::ReadCommitted,
    ) {}

    /**
     * @template TEntity of object
     * @template TCriteria
     * @param class-string<TEntity> $class
     * @param Persister<TConnection, TTransaction, TEntity, TCriteria> $persister
     * @param \Closure(TEntity): ?non-empty-string $getId
     * @return Repository<TConnection, TTransaction, TEntity, TCriteria>
     */
    public function repository(string $class, Persister $persister, \Closure $getId): Repository
    {
        $this->ensureNotClosed();

        /** @var Repository<TConnection, TTransaction, TEntity, TCriteria> */
        return $this->repositories[$class] ??= new Repository($this, $persister, $getId);
    }

    public function commit(): void
    {
        $this->ensureNotClosed();

        try {
            foreach ($this->repositories as $repository) {
                $repository->persist();
            }

            $this->transactionHandle?->commit();
        } catch (\Throwable $exception) {
            $this->transactionHandle?->rollback();

            throw $exception;
        } finally {
            $this->close();
        }
    }

    public function rollback(): void
    {
        $this->ensureNotClosed();

        try {
            $this->transactionHandle?->rollback();
        } finally {
            $this->close();
        }
    }

    public private(set) bool $isClosed = false;

    private function ensureNotClosed(): void
    {
        if ($this->isClosed) {
            throw new UnitOfWorkClosed();
        }
    }

    private function close(): void
    {
        $this->repositories = [];
        $this->transactionHandle = null;
        $this->isClosed = true;
    }
}
