<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProjectRepository;

class ProjectService
{
    public function __construct(private ProjectRepository $repo)
    {
    }

    public function listForRole(int $userId, string $role): array
    {
        if (strtolower($role) === 'admin') {
            return [
                'success' => true,
                'projects' => $this->repo->findAllWithCreator(),
            ];
        }
        return [
            'success' => true,
            'projects' => $this->repo->findByCreator($userId),
        ];
    }

    public function getDetails(int $projectId, int $userId, string $role): array
    {
        $row = $this->repo->findDetails($projectId, $userId, $role);
        if (!$row) {
            return ['success' => false, 'message' => 'Proje bulunamadı'];
        }
        return ['success' => true, 'project' => $row];
    }

    public function add(array $data, int $userId): array
    {
        $required = ['title', 'subject', 'project_manager', 'responsible_person'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'message' => 'Zorunlu alan eksik: ' . $field,
                ];
            }
        }
        $id = $this->repo->insert($data, $userId);
        $this->repo->notifyAdminsInsert($id, $data['title']);
        return [
            'success' => true,
            'message' => 'Proje başarıyla eklendi',
            'project_id' => $id,
        ];
    }

    public function update(int $projectId, array $data, int $userId, string $role): array
    {
        if (!$this->repo->canMutate($projectId, $userId, $role)) {
            return ['success' => false, 'message' => 'Bu projeyi düzenleme yetkiniz yok'];
        }
        $ok = $this->repo->update($projectId, $data);
        return [
            'success' => $ok,
            'message' => $ok ? 'Proje başarıyla güncellendi' : 'Proje güncellenirken hata oluştu',
        ];
    }

    public function delete(int $projectId, int $userId, string $role): array
    {
        if (!$this->repo->canMutate($projectId, $userId, $role)) {
            return ['success' => false, 'message' => 'Bu projeyi silme yetkiniz yok'];
        }
        $ok = $this->repo->delete($projectId);
        return [
            'success' => $ok,
            'message' => $ok ? 'Proje başarıyla silindi' : 'Proje silinirken hata oluştu',
        ];
    }

    public function approve(int $projectId, int $adminId): array
    {
        $ok = $this->repo->approve($projectId, $adminId);
        if ($ok) {
            $this->repo->notifyProjectOwnerInsert($projectId, 'approved');
            return ['success' => true, 'message' => 'Proje onaylandı'];
        }
        return ['success' => false, 'message' => 'Proje onaylanırken hata oluştu'];
    }

    public function reject(int $projectId, int $adminId, string $reason): array
    {
        $ok = $this->repo->reject($projectId, $adminId, $reason);
        if ($ok) {
            $this->repo->notifyProjectOwnerInsert($projectId, 'rejected', $reason);
            return ['success' => true, 'message' => 'Proje reddedildi'];
        }
        return ['success' => false, 'message' => 'Proje reddedilirken hata oluştu'];
    }
} 