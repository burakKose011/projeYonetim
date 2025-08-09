<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class ProjectRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function findAllWithCreator(): array
    {
        $sql = "SELECT p.*, u.full_name as creator_name, u.department as creator_department
                FROM projects p JOIN users u ON p.created_by = u.id
                ORDER BY p.created_at DESC";
        $st = $this->db->prepare($sql);
        $st->execute();
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            if (!empty($r['file_info'])) {
                $r['file_info'] = json_decode($r['file_info'], true);
            }
        }
        return $rows;
    }

    public function findByCreator(int $userId): array
    {
        $sql = "SELECT p.*, u.full_name as creator_name
                FROM projects p JOIN users u ON p.created_by = u.id
                WHERE p.created_by = :uid
                ORDER BY p.created_at DESC";
        $st = $this->db->prepare($sql);
        $st->bindValue(':uid', $userId, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            if (!empty($r['file_info'])) {
                $r['file_info'] = json_decode($r['file_info'], true);
            }
        }
        return $rows;
    }

    public function findDetails(int $projectId, ?int $userId, string $role): ?array
    {
        $sql = "SELECT p.*, u.full_name as creator_name, u.department as creator_department
                FROM projects p JOIN users u ON p.created_by = u.id
                WHERE p.id = :pid";
        if (strtolower($role) !== 'admin') {
            $sql .= " AND p.created_by = :uid";
        }
        $st = $this->db->prepare($sql);
        $st->bindValue(':pid', $projectId, PDO::PARAM_INT);
        if (strtolower($role) !== 'admin') {
            $st->bindValue(':uid', $userId, PDO::PARAM_INT);
        }
        $st->execute();
        $row = $st->fetch();
        if (!$row) return null;
        if (!empty($row['file_info'])) {
            $row['file_info'] = json_decode($row['file_info'], true);
        }
        return $row;
    }

    public function insert(array $data, int $userId): int
    {
        $sql = "INSERT INTO projects (title, subject, description, keywords, project_manager,
                 responsible_person, start_date, end_date, duration_days, status, file_info, created_by)
                VALUES (:title, :subject, :description, :keywords, :project_manager,
                 :responsible_person, :start_date, :end_date, :duration_days, 'pending', :file_info, :created_by)";
        $st = $this->db->prepare($sql);
        $st->bindValue(':title', $data['title']);
        $st->bindValue(':subject', $data['subject']);
        $st->bindValue(':description', $data['description']);
        $st->bindValue(':keywords', $data['keywords']);
        $st->bindValue(':project_manager', $data['project_manager']);
        $st->bindValue(':responsible_person', $data['responsible_person']);
        $st->bindValue(':start_date', $data['start_date']);
        $st->bindValue(':end_date', $data['end_date']);
        $st->bindValue(':duration_days', $data['duration_days']);
        $st->bindValue(':file_info', isset($data['file_info']) ? json_encode($data['file_info']) : null);
        $st->bindValue(':created_by', $userId, PDO::PARAM_INT);
        $st->execute();
        return (int)$this->db->lastInsertId();
    }

    public function update(int $projectId, array $data): bool
    {
        $fileInfo = isset($data['file_info']) && $data['file_info'] !== null;
        $sql = "UPDATE projects SET
                title=:title, subject=:subject, description=:description,
                keywords=:keywords, project_manager=:project_manager,
                responsible_person=:responsible_person, start_date=:start_date,
                end_date=:end_date, duration_days=:duration_days";
        if ($fileInfo) {
            $sql .= ", file_info=:file_info";
        }
        $sql .= ", updated_at=NOW() WHERE id=:id";

        $st = $this->db->prepare($sql);
        $st->bindValue(':title', $data['title']);
        $st->bindValue(':subject', $data['subject']);
        $st->bindValue(':description', $data['description']);
        $st->bindValue(':keywords', $data['keywords']);
        $st->bindValue(':project_manager', $data['project_manager']);
        $st->bindValue(':responsible_person', $data['responsible_person']);
        $st->bindValue(':start_date', $data['start_date']);
        $st->bindValue(':end_date', $data['end_date']);
        $st->bindValue(':duration_days', $data['duration_days']);
        if ($fileInfo) {
            $st->bindValue(':file_info', json_encode($data['file_info']));
        }
        $st->bindValue(':id', $projectId, PDO::PARAM_INT);
        return $st->execute();
    }

    public function canMutate(int $projectId, int $userId, string $role): bool
    {
        if (strtolower($role) === 'admin') return true;
        $st = $this->db->prepare("SELECT id FROM projects WHERE id=:id AND created_by=:uid");
        $st->execute([':id' => $projectId, ':uid' => $userId]);
        return $st->rowCount() > 0;
    }

    public function delete(int $projectId): bool
    {
        $st = $this->db->prepare("DELETE FROM projects WHERE id=:id");
        return $st->execute([':id' => $projectId]);
    }

    public function approve(int $projectId, int $adminId): bool
    {
        $st = $this->db->prepare("UPDATE projects SET status='approved', approved_by=:a, approved_at=NOW() WHERE id=:id");
        return $st->execute([':a' => $adminId, ':id' => $projectId]);
    }

    public function reject(int $projectId, int $adminId, string $reason): bool
    {
        $st = $this->db->prepare("UPDATE projects SET status='rejected', approved_by=:a, approved_at=NOW(), rejection_reason=:r WHERE id=:id");
        return $st->execute([':a' => $adminId, ':r' => $reason, ':id' => $projectId]);
    }

    public function notifyAdminsInsert(int $projectId, string $projectTitle): void
    {
        $sql = "INSERT INTO notifications (user_id, title, message, type, related_project_id)
                SELECT id, 'Yeni Proje Eklendi', :message, 'info', :pid FROM users WHERE role='admin'";
        $st = $this->db->prepare($sql);
        $message = 'Yeni proje eklendi: ' . $projectTitle;
        $st->execute([':message' => $message, ':pid' => $projectId]);
    }

    public function notifyProjectOwnerInsert(int $projectId, string $status, string $reason = ''): void
    {
        $title = $status === 'approved' ? 'Proje Onaylandı' : 'Proje Reddedildi';
        $message = $status === 'approved' ? 'Projeniz onaylandı.' : ('Projeniz reddedildi. Sebep: ' . $reason);
        $type = $status === 'approved' ? 'success' : 'error';
        $sql = "INSERT INTO notifications (user_id, title, message, type, related_project_id)
                SELECT created_by, :title, :message, :type, :pid FROM projects WHERE id=:pid";
        $st = $this->db->prepare($sql);
        $st->execute([':title' => $title, ':message' => $message, ':type' => $type, ':pid' => $projectId]);
    }
} 