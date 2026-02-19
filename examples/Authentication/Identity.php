<?php

declare(strict_types=1);

namespace Authentication;

use Ramsey\Uuid\UuidInterface as Uuid;

final class Identity
{
    public static function register(Uuid $id, #[\SensitiveParameter] string $password): self
    {
        return new self(
            id: $id,
            passwordHash: password_hash($password, PASSWORD_DEFAULT),
        );
    }

    public function __construct(
        public readonly Uuid $id,
        public private(set) string $passwordHash,
    ) {}

    public function authenticate(#[\SensitiveParameter] string $password): void
    {
        if (!password_verify($password, $this->passwordHash)) {
            throw new InvalidPassword();
        }
    }

    public function changePassword(
        #[\SensitiveParameter]
        string $oldPassword,
        #[\SensitiveParameter]
        string $newPassword,
    ): void {
        $this->authenticate($oldPassword);

        $this->passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    }
}
