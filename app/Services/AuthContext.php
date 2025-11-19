<?php

namespace App\Services;

/**
 * Lightweight per-request context for storing authenticated user metadata.
 */
class AuthContext
{
    private ?object $userPayload = null;
    private ?int $userId = null;
    private ?int $schoolId = null;
    private bool $isAdmin = false;

    public function setUserPayload(?object $payload): void
    {
        $this->userPayload = $payload;
    }

    public function getUserPayload(): ?object
    {
        return $this->userPayload;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setSchoolId(?int $schoolId): void
    {
        $this->schoolId = $schoolId;
    }

    public function getSchoolId(): ?int
    {
        return $this->schoolId;
    }

    public function setIsAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function reset(): void
    {
        $this->userPayload = null;
        $this->userId = null;
        $this->schoolId = null;
        $this->isAdmin = false;
    }
}
