<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class AuthRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function findActiveUserByEmail(string $email): ?array
    {
        $sql = "SELECT id, username, email, full_name, title, department, employee_id, phone, role, is_active, password_hash 
                FROM users WHERE email = :email AND is_active = 1";
        $st = $this->db->prepare($sql);
        $st->bindValue(':email', $email);
        $st->execute();
        $row = $st->fetch();
        return $row ?: null;
    }

    public function updateLastLogin(int $userId): void
    {
        $st = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $st->bindValue(':id', $userId, PDO::PARAM_INT);
        $st->execute();
    }

    public function insertUser(array $data): int
    {
        $sql = "INSERT INTO users (username, email, password_hash, full_name, title, department, employee_id, phone, role)
                VALUES (:username, :email, :password_hash, :full_name, :title, :department, :employee_id, :phone, 'user')";
        $st = $this->db->prepare($sql);
        $st->bindValue(':username', $data['username']);
        $st->bindValue(':email', $data['email']);
        $st->bindValue(':password_hash', $data['password_hash']);
        $st->bindValue(':full_name', $data['full_name']);
        $st->bindValue(':title', $data['title']);
        $st->bindValue(':department', $data['department']);
        $st->bindValue(':employee_id', $data['employee_id']);
        $st->bindValue(':phone', $data['phone']);
        $st->execute();
        return (int)$this->db->lastInsertId();
    }

    public function existsByUsernameOrEmail(string $username, string $email): bool
    {
        $st = $this->db->prepare("SELECT id FROM users WHERE username = :u OR email = :e");
        $st->execute([':u' => $username, ':e' => $email]);
        return $st->rowCount() > 0;
    }

    public function getUserById(int $userId): ?array
    {
        $sql = "SELECT id, username, email, full_name, title, department, employee_id, phone, role, is_active, created_at, last_login 
                FROM users WHERE id = :id";
        $st = $this->db->prepare($sql);
        $st->bindValue(':id', $userId, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch();
        return $row ?: null;
    }

    public function getAllActiveUsers(): array
    {
        $st = $this->db->prepare("SELECT id, username, full_name, email, role FROM users WHERE is_active = 1 ORDER BY full_name");
        $st->execute();
        return $st->fetchAll();
    }

    public function getAdminInfo(): ?array
    {
        $st = $this->db->prepare("SELECT id, username, email, full_name, title, department, employee_id, phone, role FROM users WHERE LOWER(role) = 'admin' LIMIT 1");
        $st->execute();
        $row = $st->fetch();
        return $row ?: null;
    }

    public function updateAdminById(int $adminId, array $fields): bool
    {
        $sets = [];
        $params = [':id' => $adminId];
        foreach ($fields as $k => $v) {
            $sets[] = $k . ' = :' . $k;
            $params[':' . $k] = $v;
        }
        if (!$sets) return false;
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $st = $this->db->prepare($sql);
        return $st->execute($params);
    }

    public function getPasswordHash(int $userId): ?string
    {
        $st = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $st->bindValue(':id', $userId, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch();
        return $row['password_hash'] ?? null;
    }

    public function updatePasswordHash(int $userId, string $hash): void
    {
        $st = $this->db->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
        $st->execute([':h' => $hash, ':id' => $userId]);
    }

    public function findActiveUserByPhone(string $phone): ?array
    {
        $st = $this->db->prepare("SELECT id, username, full_name, phone FROM users WHERE phone = :p AND is_active = 1");
        $st->bindValue(':p', $phone);
        $st->execute();
        $row = $st->fetch();
        return $row ?: null;
    }
} 