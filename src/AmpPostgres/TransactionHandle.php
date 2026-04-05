<?php

declare(strict_types=1);

namespace Thesis\ORM\AmpPostgres;

use Amp\Postgres\PostgresLink;
use Amp\Postgres\PostgresTransaction;
use Thesis\ORM;

/**
 * @api
 *
 * @implements ORM\TransactionHandle<PostgresLink>
 */
final readonly class TransactionHandle implements ORM\TransactionHandle
{
    public function __construct(
        public PostgresTransaction $executor,
    ) {}

    public function commit(): void
    {
        $this->executor->commit();
    }

    public function rollback(): void
    {
        $this->executor->rollback();
    }

    public function beginTransaction(): ORM\TransactionHandle
    {
        return new self($this->executor->beginTransaction());
    }
}
