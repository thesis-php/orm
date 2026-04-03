<?php

declare(strict_types=1);

namespace Thesis\ORM\AmpPostgres;

use Amp\Postgres\PostgresTransaction;

/**
 * @api
 *
 * @implements \Thesis\ORM\Transaction<PostgresTransaction>
 */
final readonly class Transaction implements \Thesis\ORM\Transaction
{
    public function __construct(
        public PostgresTransaction $inner,
    ) {}

    public function commit(): void
    {
        $this->inner->commit();
    }

    public function rollback(): void
    {
        $this->inner->rollback();
    }
}
