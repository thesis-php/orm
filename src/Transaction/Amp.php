<?php

declare(strict_types=1);

namespace Thesis\ORM\Transaction;

use Amp\Sql\SqlTransaction;
use Thesis\ORM\Transaction;

/**
 * @api
 *
 * @template TTransaction of SqlTransaction
 * @implements Transaction<TTransaction>
 */
final readonly class Amp implements Transaction
{
    /**
     * @param TTransaction $inner
     */
    public function __construct(
        public SqlTransaction $inner,
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
