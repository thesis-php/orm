<?php

declare(strict_types=1);

namespace Authentication;

use Authentication\Identity\Repository;
use Ramsey\Uuid\UuidInterface;

final readonly class RegisterHandler
{
    public function __construct(
        private Repository $repository,
    ) {}

    public function __invoke(UuidInterface $id, #[\SensitiveParameter] string $password): void
    {
        $identity = Identity::register(id: $id, password: $password);

        $this->repository->add($identity);
    }
}
