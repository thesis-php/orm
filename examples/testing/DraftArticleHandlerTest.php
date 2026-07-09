<?php

declare(strict_types=1);

namespace Testing;

use Amp\Postgres\PostgresConnectionPool;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Testing\Article\Repository;
use Testo\Assert;
use Testo\Test;
use Thesis\ORM\AmpPostgres\ConnectionHandle;
use Thesis\ORM\EntityManager;
use Thesis\ORM\Persister\InMemory;
use Thesis\ORM\Session;

#[Test]
final class DraftArticleHandlerTest
{
    public function testItAddsAnArticle(): void
    {
        $entityManager = new EntityManager(new ConnectionHandle($this->mockPostgres()));
        $persister = new InMemory(static fn(Article $article, UuidInterface $id) => $article->id->equals($id));
        $repository = $entityManager->session(static fn(Session $session) => new Repository($session, $persister));
        $handler = new DraftArticleHandler($repository);
        $id = Uuid::uuid7();
        $title = 'PHP is awesome!';

        $handler($id, $title);

        Assert::notNull($article = $repository->find($id));
        Assert::same($article->title, $title);
    }

    public function testItPersistsAnArticle(): void
    {
        $entityManager = new EntityManager(new ConnectionHandle($this->mockPostgres()));
        $persister = new InMemory(static fn(Article $article) => true);
        $id = Uuid::uuid7();
        $title = 'PHP is awesome!';

        $entityManager->session(
            static function (Session $session) use ($persister, $id, $title): void {
                $repository = new Repository($session, $persister);

                new DraftArticleHandler($repository)($id, $title);
            },
        );

        Assert::true($persister->entities !== []);
        Assert::same($persister->entities[0]->id, $id);
        Assert::same($persister->entities[0]->title, $title);
    }

    private function mockPostgres(): PostgresConnectionPool
    {
        return new \ReflectionClass(PostgresConnectionPool::class)->newLazyProxy(
            static fn() => throw new \LogicException(),
        );
    }
}
