<?php

declare(strict_types=1);

namespace Thesis\ORM\AmpPostgres;

use Amp\Postgres\PostgresConnection;
use Amp\Postgres\PostgresTransaction;
use Amp\Sql\SqlTransactionIsolationLevel;
use Thesis\ORM;

/**
 * @api
 *
 * @implements ORM\Connection<PostgresConnection, PostgresTransaction>
 */
final readonly class Connection implements ORM\Connection
{
    public function __construct(
        public PostgresConnection $inner,
    ) {}

    public function beginTransaction(ORM\IsolationLevel $isolationLevel = ORM\IsolationLevel::ReadCommitted): ORM\Transaction
    {
        $previousIsolationLevel = $this->inner->getTransactionIsolation();

        $this->inner->setTransactionIsolation(
            match ($isolationLevel) {
                ORM\IsolationLevel::ReadCommitted => SqlTransactionIsolationLevel::Committed,
                ORM\IsolationLevel::RepeatableRead => SqlTransactionIsolationLevel::Repeatable,
                ORM\IsolationLevel::Serializable => SqlTransactionIsolationLevel::Serializable,
                ORM\IsolationLevel::ReadUncommitted => SqlTransactionIsolationLevel::Uncommitted,
            },
        );

        $transaction = $this->inner->beginTransaction();

        $transaction->onClose(function () use ($previousIsolationLevel): void {
            $this->inner->setTransactionIsolation($previousIsolationLevel);
        });

        return new Transaction($transaction);
    }
}
