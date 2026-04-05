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
     * @var ORM\Repository<PostgresLink, Article, UuidInterface, Article>
     */
    private ORM\Repository $repository;

    /**
     * @param ORM\Session<PostgresLink> $session
     * @param ORM\Persister<PostgresLink, Article, UuidInterface, Article> $persister
     */
    public function __construct(
        ORM\Session $session,
        ORM\Persister $persister,
    ) {
        $this->repository = $session->repository(
            class: Article::class,
            persister: $persister,
            getId: static fn(Article $article) => $article->id->toString(),
            calculateChangeSet: static fn() => null,
        );
    }

    public function find(UuidInterface $id): ?Article
    {
        return $this->repository->find($id)[0] ?? null;
    }

    public function add(Article $article): void
    {
        $this->repository->add($article);
    }
}
