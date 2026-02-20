<?php

declare(strict_types=1);

namespace Testing;

use Ramsey\Uuid\UuidInterface;
use Testing\Article\Repository;

final readonly class DraftArticleHandler
{
    public function __construct(
        private Repository $repository,
    ) {}

    public function __invoke(UuidInterface $id, string $title): void
    {
        $article = new Article($id, $title);

        $this->repository->add($article);
    }
}
