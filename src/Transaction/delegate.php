<?php

declare(strict_types=1);

namespace Thesis\ORM\Transaction;

use Thesis\ORM\Internal\TransactionDelegate;
use Thesis\ORM\Transaction;

/**
 * @api
 *
 * @template TTransaction of object
 * @param TTransaction $transaction
 * @return Transaction<TTransaction>
 */
function delegate(object $transaction): Transaction
{
    /** @var array<class-string, true> */
    static $checked = [];

    if (isset($checked[$transaction::class])) {
        return new TransactionDelegate($transaction);
    }

    $reflection = new \ReflectionObject($transaction);

    if (!$reflection->hasMethod('commit')
        || !$reflection->getMethod('commit')->isPublic()
        || $reflection->getMethod('commit')->isStatic()
        || $reflection->getMethod('commit')->getNumberOfRequiredParameters() > 0
        || !$reflection->hasMethod('rollback')
        || !$reflection->getMethod('rollback')->isPublic()
        || $reflection->getMethod('rollback')->isStatic()
        || $reflection->getMethod('rollback')->getNumberOfRequiredParameters() > 0
    ) {
        throw new \InvalidArgumentException(\sprintf(
            'Transaction class `%s` should have `commit()` and `rollback()` public instance methods with 0 required parameters',
            $transaction::class,
        ));
    }

    $checked[$transaction::class] = true;

    return new TransactionDelegate($transaction);
}
