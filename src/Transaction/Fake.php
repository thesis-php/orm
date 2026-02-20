<?php

declare(strict_types=1);

namespace Thesis\ORM\Transaction;

use Thesis\ORM\Transaction;

/**
 * @api
 *
 * @template-covariant TTransaction of object
 * @implements Transaction<TTransaction>
 */
final readonly class Fake implements Transaction
{
    /**
     * @param TTransaction $inner
     */
    public function __construct(
        public object $inner,
    ) {}

    public function commit(): void {}

    public function rollback(): void {}
}
