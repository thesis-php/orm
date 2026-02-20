<?php

declare(strict_types=1);

namespace Testing;

use Ramsey\Uuid\UuidInterface;

final readonly class Article
{
    public function __construct(
        public UuidInterface $id,
        public string $title,
    ) {}
}
