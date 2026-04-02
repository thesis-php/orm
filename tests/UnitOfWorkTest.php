<?php

declare(strict_types=1);

namespace Thesis\ORM;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\ORM\Persister\InMemory;

#[CoversClass(UnitOfWork::class)]
final class UnitOfWorkTest extends TestCase
{
    public function testFindAll(): void
    {
        $articles = [new Article(1), new Article(2)];
        $persister = new InMemory(entities: $articles);
        $unitOfWork = new UnitOfWork(new \stdClass());

        $found = $unitOfWork->findBy(
            keyFactory: static fn(Article $article) => (string) $article->id,
            persister: $persister,
            criteria: null,
        );

        self::assertSame($articles, $found);
    }

    public function testFindByCriteria(): void
    {
        $article1 = new Article(1);
        $article2 = new Article(2);
        $persister = new InMemory(
            filter: static fn(Article $article, int $id) => $article->id === $id,
            entities: [$article1, $article2],
        );
        $unitOfWork = new UnitOfWork(new \stdClass());

        $found = $unitOfWork->findBy(
            keyFactory: static fn(Article $article) => (string) $article->id,
            persister: $persister,
            criteria: 1,
        );

        self::assertSame([$article1], $found);
    }

    public function testAdd(): void
    {
        $persister = new InMemory();
        $unitOfWork = new UnitOfWork(new \stdClass());
        $article = new Article(1);

        $unitOfWork->add('key', $persister, $article);
        $unitOfWork->flush();

        self::assertSame([$article], $persister->entities);
    }

    public function testAddRemove(): void
    {
        $persister = new InMemory();
        $unitOfWork = new UnitOfWork(new \stdClass());
        $article = new Article(1);

        $unitOfWork->add('key', $persister, $article);
        $unitOfWork->remove('key', $persister, $article);
        $unitOfWork->flush();

        self::assertSame([], $persister->entities);
    }
}
