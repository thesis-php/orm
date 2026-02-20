<?php

declare(strict_types=1);

namespace Thesis\ORM;

/**
 * @api
 *
 * @template TTransaction of object
 */
final readonly class EntityManager
{
    /**
     * @var \Closure(): Transaction<TTransaction>
     */
    private \Closure $beginTransaction;

    /**
     * @param callable(): Transaction<TTransaction> $beginTransaction
     */
    public function __construct(callable $beginTransaction)
    {
        $this->beginTransaction = $beginTransaction(...);
    }

    /**
     * @template T
     * @param callable(UnitOfWork<TTransaction>): T $function
     * @return T
     */
    public function inTransaction(callable $function): mixed
    {
        $transaction = ($this->beginTransaction)();

        $unitOfWork = new UnitOfWork($transaction->inner);

        try {
            $result = $function($unitOfWork);

            $unitOfWork->flush();

            $transaction->commit();

            return $result;
        } catch (\Throwable $exception) {
            $unitOfWork->close();

            $transaction->rollback();

            throw $exception;
        }
    }
}
