<?php

declare(strict_types=1);

namespace Thesis\ORM\AmpPostgres;

use Amp\Postgres\PostgresConnection;
use Amp\Postgres\PostgresLink;
use Amp\Sql\SqlTransactionIsolationLevel;
use Thesis\ORM;

/**
 * @api
 *
 * @implements ORM\ConnectionHandle<PostgresLink>
 */
final readonly class ConnectionHandle implements ORM\ConnectionHandle
{
    public function __construct(
        public PostgresConnection $executor,
    ) {}

    public function beginTransaction(ORM\IsolationLevel $isolationLevel = ORM\IsolationLevel::ReadCommitted): ORM\TransactionHandle
    {
        $previousIsolationLevel = $this->executor->getTransactionIsolation();

        $this->executor->setTransactionIsolation(
            match ($isolationLevel) {
                ORM\IsolationLevel::ReadCommitted => SqlTransactionIsolationLevel::Committed,
                ORM\IsolationLevel::RepeatableRead => SqlTransactionIsolationLevel::Repeatable,
                ORM\IsolationLevel::Serializable => SqlTransactionIsolationLevel::Serializable,
                ORM\IsolationLevel::ReadUncommitted => SqlTransactionIsolationLevel::Uncommitted,
            },
        );

        $transaction = $this->executor->beginTransaction();

        $transaction->onClose(function () use ($previousIsolationLevel): void {
            $this->executor->setTransactionIsolation($previousIsolationLevel);
        });

        return new TransactionHandle($transaction);
    }
}
