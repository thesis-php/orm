<?php

declare(strict_types=1);

namespace Testing;

use Amp\Postgres\PostgresConnection;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Testing\Article\Repository;
use Thesis\ORM\AmpPostgres\Connection;
use Thesis\ORM\EntityManager;
use Thesis\ORM\Persister\InMemory;
use Thesis\ORM\Session;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertNotNull;

final class DraftArticleHandlerTest extends TestCase
{
    public function testItAddsAnArticle(): void
    {
        $entityManager = new EntityManager(new Connection($this->createMock(PostgresConnection::class)));
        $persister = new InMemory(static fn(Article $article, UuidInterface $id) => $article->id->equals($id));
        $repository = $entityManager->session(static fn(Session $session) => new Repository($session, $persister));
        $handler = new DraftArticleHandler($repository);
        $id = Uuid::uuid7();
        $title = 'PHP is awesome!';

        $handler($id, $title);

        assertNotNull($article = $repository->find($id));
        self::assertSame($title, $article->title);
    }

    public function testItPersistsAnArticle(): void
    {
        $entityManager = new EntityManager(new Connection($this->createMock(PostgresConnection::class)));
        $persister = new InMemory(static fn(Article $article) => true);
        $id = Uuid::uuid7();
        $title = 'PHP is awesome!';

        $entityManager->session(
            static function (Session $session) use ($persister, $id, $title): void {
                $repository = new Repository($session, $persister);

                new DraftArticleHandler($repository)($id, $title);
            },
        );

        assertCount(1, $persister->entities);
        self::assertSame($id, $persister->entities[0]->id);
        self::assertSame($title, $persister->entities[0]->title);
    }
}
