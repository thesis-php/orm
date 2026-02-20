<?php

declare(strict_types=1);

namespace Testing\Article;

use Amp\Postgres\PostgresLink;
use Ramsey\Uuid\UuidInterface;
use Testing\Article;
use Thesis\ORM;

final readonly class Repository
{
    /**
     * @var ORM\Repository<PostgresLink, Article, UuidInterface>
     */
    private ORM\Repository $repository;

    /**
     * @param ORM\UnitOfWork<PostgresLink> $unitOfWork
     * @param ORM\Persister<PostgresLink, Article, UuidInterface> $persister
     */
    public function __construct(
        ORM\UnitOfWork $unitOfWork,
        ORM\Persister $persister,
    ) {
        $this->repository = $unitOfWork->repository(
            class: Article::class,
            persister: $persister,
            getId: static fn(Article $article) => $article->id->toString(),
        );
    }

    public function find(UuidInterface $id): ?Article
    {
        return $this->repository->findBy($id)[0] ?? null;
    }

    public function add(Article $article): void
    {
        $this->repository->add($article);
    }
}
