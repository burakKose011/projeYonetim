<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;

class AuthController
{
    public function __construct(private AuthService $service)
    {
    }

    public function login(string $email, string $password): array
    {
        return $this->service->login($email, $password);
    }

    public function register(array $data): array
    {
        return $this->service->register($data);
    }

    public function updateProfile(int $userId, array $data): array
    {
        // In current app, updateProfile writes directly; for now we can reuse register-like update in repo later
        return ['success' => false, 'message' => 'Not implemented in layered controller yet'];
    }

    public function getAdminInfo(): array
    {
        return $this->service->getAdminInfo();
    }

    public function updateAdminInfo(array $data): array
    {
        return $this->service->updateAdminInfo($data);
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        return $this->service->changePassword($userId, $currentPassword, $newPassword);
    }

    public function forgotPassword(string $email): array
    {
        return $this->service->forgotPasswordByEmail($email);
    }

    public function forgotPasswordByPhone(string $phone): array
    {
        return $this->service->forgotPasswordByPhone($phone);
    }

    public function userInfo(int $userId): array
    {
        return $this->service->getUserInfo($userId);
    }

    public function allUsers(): array
    {
        return $this->service->getAllUsers();
    }
} 