<?php

declare(strict_types=1);

namespace Authentication\Identity;

use Amp\Postgres\PostgresLink;
use Amp\Postgres\PostgresQueryError;
use Authentication\Identity;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Thesis\ORM;
use Thesis\ORM\Exception;

/**
 * @implements ORM\Persister<PostgresLink, Identity, ?UuidInterface>
 */
final readonly class Persister implements ORM\Persister
{
    public function select(object $transaction, mixed $criteria): iterable
    {
        $rows = $criteria === null
            ? $transaction->query(
                <<<'SQL'
                    select id, password_hash, version
                    from identity
                    order by id
                    SQL,
            )
            : $transaction->execute(
                <<<'SQL'
                    select id, password_hash, version
                    from identity
                    where id = ?
                    SQL,
                [$criteria->toString()],
            );

        foreach ($rows as $row) {
            /** @var array{id: non-empty-string, password_hash: non-empty-string, version: positive-int} $row */
            yield new Identity(
                id: Uuid::fromString($row['id']),
                passwordHash: $row['password_hash'],
                version: $row['version'],
            );
        }
    }

    public function insert(object $transaction, object $entity): void
    {
        try {
            $transaction->execute(
                <<<'SQL'
                    insert into identity (id, password_hash)
                    values (?, ?)
                    SQL,
                [
                    $entity->id->toString(),
                    $entity->passwordHash,
                ],
            );
        } catch (PostgresQueryError $error) {
            if (str_contains(strtolower($error->getMessage()), 'duplicate key value violates unique constraint')) {
                throw new Exception\DuplicateEntity(previous: $error);
            }

            throw $error;
        }
    }

    public function update(object $transaction, object $entity, object $snapshot): void
    {
        if ($entity->passwordHash === $snapshot->passwordHash) {
            return;
        }

        $result = $transaction->execute(
            <<<'SQL'
                update identity
                set password_hash = ?,
                    version = version + 1
                where id = ? and version = ?
                SQL,
            [
                $entity->passwordHash,
                $entity->id->toString(),
                $entity->version,
            ],
        );

        if ($result->getRowCount() !== 1) {
            throw new Exception\ConcurrentModification();
        }
    }

    public function delete(object $transaction, object $entity): void
    {
        $result = $transaction->execute(
            <<<'SQL'
                delete from identity
                where id = ? and version = ?
                SQL,
            [
                $entity->id->toString(),
                $entity->version,
            ],
        );

        if ($result->getRowCount() !== 1) {
            throw new Exception\ConcurrentModification();
        }
    }
}
