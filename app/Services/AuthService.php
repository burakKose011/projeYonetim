<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuthRepository;

class AuthService
{
    public function __construct(private AuthRepository $repo)
    {
    }

    public function login(string $email, string $password): array
    {
        $user = $this->repo->findActiveUserByEmail($email);
        if (!$user) {
            return ['success' => false, 'message' => 'Kullanıcı bulunamadı'];
        }
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Geçersiz e-posta veya şifre'];
        }
        $this->repo->updateLastLogin((int)$user['id']);
        return [
            'success' => true,
            'message' => 'Giriş başarılı',
            'user' => $user,
        ];
    }

    public function register(array $data): array
    {
        if (empty($data['username']) && !empty($data['email'])) {
            $data['username'] = explode('@', $data['email'])[0];
        }
        if ($this->repo->existsByUsernameOrEmail($data['username'], $data['email'])) {
            return ['success' => false, 'message' => 'Bu kullanıcı adı veya e-posta zaten kullanılıyor'];
        }
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $id = $this->repo->insertUser($data);
        return ['success' => true, 'message' => 'Kayıt başarılı', 'user_id' => $id];
    }

    public function getUserInfo(int $userId): array
    {
        $user = $this->repo->getUserById($userId);
        if (!$user) return ['success' => false, 'message' => 'Kullanıcı bulunamadı'];
        return ['success' => true, 'user' => $user];
    }

    public function getAllUsers(): array
    {
        return ['success' => true, 'users' => $this->repo->getAllActiveUsers()];
    }

    public function getAdminInfo(): array
    {
        $admin = $this->repo->getAdminInfo();
        if (!$admin) return ['success' => false, 'message' => 'Admin bulunamadı.'];
        return ['success' => true, 'admin' => $admin];
    }

    public function updateAdminInfo(array $data): array
    {
        $admin = $this->repo->getAdminInfo();
        if (!$admin) return ['success' => false, 'message' => 'Admin bulunamadı.'];

        $fields = [];
        foreach (['username','email','full_name','title','department','employee_id','phone'] as $key) {
            if (!empty($data[$key])) $fields[$key] = $data[$key];
        }
        if (!empty($data['password'])) $fields['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);

        if (!$fields) return ['success' => false, 'message' => 'Güncellenecek bilgi yok.'];

        $ok = $this->repo->updateAdminById((int)$admin['id'], $fields);
        return [
            'success' => $ok,
            'message' => $ok ? 'Admin bilgileri güncellendi.' : 'Güncelleme hatası'
        ];
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $hash = $this->repo->getPasswordHash($userId);
        if (!$hash) return ['success' => false, 'message' => 'Kullanıcı bulunamadı.'];
        if (!password_verify($currentPassword, $hash)) {
            return ['success' => false, 'message' => 'Mevcut şifre yanlış.'];
        }
        $this->repo->updatePasswordHash($userId, password_hash($newPassword, PASSWORD_DEFAULT));
        return ['success' => true, 'message' => 'Şifre başarıyla değiştirildi.'];
    }

    public function forgotPasswordByEmail(string $email): array
    {
        $user = $this->repo->findActiveUserByEmail($email);
        if (!$user) return ['success' => false, 'message' => 'Bu e-posta adresi ile kayıtlı kullanıcı bulunamadı.'];
        $temp = bin2hex(random_bytes(4));
        $this->repo->updatePasswordHash((int)$user['id'], password_hash($temp, PASSWORD_DEFAULT));
        return ['success' => true, 'message' => 'Geçici şifreniz e-posta adresinize gönderildi.', 'tempPassword' => $temp];
    }

    public function forgotPasswordByPhone(string $phone): array
    {
        $user = $this->repo->findActiveUserByPhone($phone);
        if (!$user) return ['success' => false, 'message' => 'Bu telefon numarası ile kayıtlı kullanıcı bulunamadı.'];
        $temp = bin2hex(random_bytes(4));
        $this->repo->updatePasswordHash((int)$user['id'], password_hash($temp, PASSWORD_DEFAULT));
        return ['success' => true, 'message' => 'Geçici şifreniz SMS ile gönderildi.', 'tempPassword' => $temp];
    }
} 