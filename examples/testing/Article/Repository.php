<?php

declare(strict_types=1);

namespace Testing\Article;

use Amp\Postgres\PostgresLink;
use Amp\Postgres\PostgresTransaction;
use Ramsey\Uuid\UuidInterface;
use Testing\Article;
use Thesis\ORM;

final readonly class Repository
{
    /**
     * @var ORM\Repository<PostgresLink, PostgresTransaction, Article, UuidInterface>
     */
    private ORM\Repository $repository;

    /**
     * @param ORM\Session<PostgresLink, PostgresTransaction> $session
     * @param ORM\Persister<PostgresLink, PostgresTransaction, Article, UuidInterface> $persister
     */
    public function __construct(
        ORM\Session $session,
        ORM\Persister $persister,
    ) {
        $this->repository = $session->repository(
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
