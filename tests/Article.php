<?php

declare(strict_types=1);

namespace Thesis\ORM;

final readonly class Article
{
    public function __construct(
        public int $id,
        public string $title = '',
    ) {}
}
