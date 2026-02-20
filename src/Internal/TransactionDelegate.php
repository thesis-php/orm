<?php

declare(strict_types=1);

namespace Thesis\ORM\Internal;

use Thesis\ORM\Transaction;

/**
 * @internal
 *
 * @template-covariant TTransaction of object
 * @implements Transaction<TTransaction>
 */
final readonly class TransactionDelegate implements Transaction
{
    /**
     * @param TTransaction $inner
     */
    public function __construct(
        public object $inner,
    ) {}

    public function commit(): void
    {
        $this->inner->commit(); // @phpstan-ignore method.notFound
    }

    public function rollback(): void
    {
        $this->inner->rollback(); // @phpstan-ignore method.notFound
    }
}
