<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\ProjectService;

class ProjectsController
{
    public function __construct(private ProjectService $service)
    {
    }

    public function list(array $session): array
    {
        $userId = $session['user_id'];
        $role = $session['role'];
        return $this->service->listForRole((int)$userId, (string)$role);
    }

    public function details(int $projectId, array $session): array
    {
        $userId = $session['user_id'];
        $role = $session['role'];
        return $this->service->getDetails($projectId, (int)$userId, (string)$role);
    }

    public function add(array $data, array $session): array
    {
        $userId = $session['user_id'];
        return $this->service->add($data, (int)$userId);
    }

    public function update(int $projectId, array $data, array $session): array
    {
        $userId = $session['user_id'];
        $role = $session['role'];
        return $this->service->update($projectId, $data, (int)$userId, (string)$role);
    }

    public function delete(int $projectId, array $session): array
    {
        $userId = $session['user_id'];
        $role = $session['role'];
        return $this->service->delete($projectId, (int)$userId, (string)$role);
    }

    public function approve(int $projectId, array $session): array
    {
        $adminId = $session['user_id'];
        return $this->service->approve($projectId, (int)$adminId);
    }

    public function reject(int $projectId, string $reason, array $session): array
    {
        $adminId = $session['user_id'];
        return $this->service->reject($projectId, (int)$adminId, $reason);
    }
} 