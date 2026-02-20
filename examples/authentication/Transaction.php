<?php

declare(strict_types=1);

namespace Authentication;

use Amp\Sql\SqlTransaction;
use Thesis\ORM;

/**
 * @template TTransaction of SqlTransaction
 * @implements ORM\Transaction<TTransaction>
 */
final readonly class Transaction implements ORM\Transaction
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
