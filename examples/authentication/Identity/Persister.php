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

    public function persist(object $transaction, ORM\Changes $changes): void
    {
        $this->insert($transaction, $changes->inserts);
        $this->update($transaction, $changes->updates);
        $this->delete($transaction, $changes->deletes);
    }

    /**
     * @param list<Identity> $entities
     */
    private function insert(PostgresLink $transaction, array $entities): void
    {
        if ($entities === []) {
            return;
        }

        $statement = $transaction->prepare(
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

    /**
     * @param list<ORM\Update<Identity>> $updates
     */
    private function update(PostgresLink $transaction, array $updates): void
    {
        if ($updates === []) {
            return;
        }

        $statement = $transaction->prepare(
            <<<'SQL'
                update identity
                set password_hash = ?,
                    version = version + 1
                where id = ? and version = ?
                SQL,
        );

        foreach ($updates as $update) {
            if ($update->entity->passwordHash === $update->snapshot->passwordHash) {
                continue;
            }

            $result = $statement->execute([
                $update->entity->passwordHash,
                $update->entity->id->toString(),
                $update->entity->version,
            ]);

            if ($result->getRowCount() !== 1) {
                throw new Exception\ConcurrentModification();
            }
        }
    }

    /**
     * @param list<Identity> $entities
     */
    private function delete(PostgresLink $transaction, array $entities): void
    {
        if ($entities === []) {
            return;
        }

        $statement = $transaction->prepare(
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
