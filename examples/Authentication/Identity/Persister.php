<?php

declare(strict_types=1);

namespace Authentication\Identity;

use Amp\Postgres\PostgresLink;
use Authentication\Identity;
use Ramsey\Uuid\UuidInterface as Uuid;
use Thesis\ORM\EntityVersion;
use Thesis\ORM\OptimisticLockFailed;
use Thesis\ORM\Persister as ORMPersister;

/**
 * @implements ORMPersister<PostgresLink, Identity, Uuid>
 */
final readonly class Persister implements ORMPersister
{
    public function select(object $transaction, mixed $id): ?EntityVersion
    {
        /** @var ?array{password_hash: string, version: positive-int} */
        $row = $transaction
            ->execute(
                <<<'SQL'
                    select password_hash, version
                    from identity
                    where id = ?
                    SQL,
                [$id->toString()],
            )
            ->fetchRow();

        if ($row === null) {
            return null;
        }

        return new EntityVersion(
            entity: new Identity(
                id: $id,
                passwordHash: $row['password_hash'],
            ),
            version: $row['version'],
        );
    }

    public function insert(object $transaction, object $entity): void
    {
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
    }

    public function update(object $transaction, object $entity, int $version, object $snapshot): void
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
                $version,
            ],
        );

        if ($result->getRowCount() !== 1) {
            throw new OptimisticLockFailed();
        }
    }

    public function delete(object $transaction, object $entity, int $version): void
    {
        $result = $transaction->execute(
            <<<'SQL'
                delete from identity
                where id = ? and version = ?
                SQL,
            [
                $entity->id->toString(),
                $version,
            ],
        );

        if ($result->getRowCount() !== 1) {
            throw new OptimisticLockFailed();
        }
    }
}
