<?php

declare(strict_types=1);

namespace Testing;

use Amp\Postgres\PostgresLink;
use Amp\Postgres\PostgresTransaction;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Testing\Article\Repository;
use Thesis\ORM\EntityManager;
use Thesis\ORM\Persister\InMemory;
use Thesis\ORM\UnitOfWork;
use Thesis\Transaction\Fake;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertNotNull;

final class DraftArticleHandlerTest extends TestCase
{
    public function testItAddsAnArticle(): void
    {
        $repository = new Repository(
            unitOfWork: new UnitOfWork($this->createMock(PostgresLink::class)),
            persister: new InMemory(static fn(Article $article, UuidInterface $id) => $article->id->equals($id)),
        );
        $handler = new DraftArticleHandler($repository);
        $id = Uuid::uuid7();
        $title = 'PHP is awesome!';

        $handler($id, $title);

        assertNotNull($article = $repository->find($id));
        self::assertSame($title, $article->title);
    }

    public function testItPersistsAnArticle(): void
    {
        $entityManager = new EntityManager(
            beginTransaction: fn() => new Fake($this->createMock(PostgresTransaction::class)),
        );
        $persister = new InMemory(static fn(Article $article) => true);
        $id = Uuid::uuid7();
        $title = 'PHP is awesome!';

        $entityManager->inTransaction(
            static function (UnitOfWork $unitOfWork) use ($persister, $id, $title): void {
                $repository = new Repository($unitOfWork, $persister);

                new DraftArticleHandler($repository)($id, $title);
            },
        );

        assertCount(1, $persister->entities);
        self::assertSame($id, $persister->entities[0]->id);
        self::assertSame($title, $persister->entities[0]->title);
    }
}
