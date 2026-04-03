<?php

declare(strict_types=1);

namespace Thesis\ORM;

/**
 * @api
 */
enum IsolationLevel: string
{
    case ReadUncommitted = 'READ UNCOMMITTED';
    case ReadCommitted = 'READ COMMITTED';
    case RepeatableRead = 'REPEATABLE READ';
    case Serializable = 'SERIALIZABLE';
}
