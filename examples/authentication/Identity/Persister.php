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
 * @implements ORM\Persister<PostgresLink, Identity, ?UuidInterface, Identity>
 */
final readonly class Persister implements ORM\Persister
{
    public function find(object $executor, mixed $criteria): iterable
    {
        $rows = $criteria === null
            ? $executor->query(
                <<<'SQL'
                    select id, password_hash, version
                    from identity
                    order by id
                    SQL,
            )
            : $executor->execute(
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

    public function insert(object $executor, array $entities): void
    {
        $statement = $executor->prepare(
            <<<'SQL'
                insert into identity (id, password_hash)
                values (?, ?)
                SQL,
        );

        foreach ($entities as $entity) {
            try {
                $statement->execute([$entity->id->toString(), $entity->passwordHash]);
            } catch (PostgresQueryError $error) {
                if (str_contains(strtolower($error->getMessage()), 'duplicate key value violates unique constraint')) {
                    throw new Exception\DuplicateEntity(previous: $error);
                }

                throw $error;
            }
        }
    }

    public function update(object $executor, array $changeSets): void
    {
        $statement = $executor->prepare(
            <<<'SQL'
                update identity
                set password_hash = ?,
                    version = version + 1
                where id = ? and version = ?
                SQL,
        );

        foreach ($changeSets as $changeSet) {
            $result = $statement->execute([
                $changeSet->passwordHash,
                $changeSet->id->toString(),
                $changeSet->version,
            ]);

            if ($result->getRowCount() !== 1) {
                throw new Exception\ConcurrentModification();
            }
        }
    }

    public function delete(object $executor, array $entities): void
    {
        $statement = $executor->prepare(
            <<<'SQL'
                delete from identity
                where id = ? and version = ?
                SQL,
        );

        foreach ($entities as $entity) {
            $result = $statement->execute([$entity->id->toString(), $entity->version]);

            if ($result->getRowCount() !== 1) {
                throw new Exception\ConcurrentModification();
            }
        }
    }
}
