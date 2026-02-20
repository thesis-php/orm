<?php

declare(strict_types=1);

namespace Authentication;

use Authentication\Identity\Repository;
use Ramsey\Uuid\UuidInterface as Uuid;

final readonly class ChangePasswordHandler
{
    public function __construct(
        private Repository $repository,
    ) {}

    /**
     * @throws NotRegistered|InvalidPassword
     */
    public function __invoke(
        Uuid $id,
        #[\SensitiveParameter]
        string $oldPassword,
        #[\SensitiveParameter]
        string $newPassword,
    ): void {
        $identity = $this->repository->find($id) ?? throw new NotRegistered();
        $identity->changePassword($oldPassword, $newPassword);
    }
}
