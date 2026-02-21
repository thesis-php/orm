<?php

declare(strict_types=1);

namespace Authentication;

use Ramsey\Uuid\UuidInterface;

final class Identity
{
    private const int DEFAULT_VERSION = 1;

    public static function register(UuidInterface $id, #[\SensitiveParameter] string $password): self
    {
        return new self(
            id: $id,
            passwordHash: password_hash($password, PASSWORD_DEFAULT),
            version: self::DEFAULT_VERSION,
        );
    }

    /**
     * @param non-empty-string $passwordHash
     * @param positive-int $version
     */
    public function __construct(
        public readonly UuidInterface $id,
        public private(set) string $passwordHash,
        public readonly int $version,
    ) {}

    public function authenticate(#[\SensitiveParameter] string $password): void
    {
        if (!password_verify($password, $this->passwordHash)) {
            throw new \Exception('Invalid password');
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
