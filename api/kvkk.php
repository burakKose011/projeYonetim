<?php
/**
 * KVKK API - Kişisel Verilerin Korunması Kanunu
 * Proje Yönetim Sistemi - İSTE
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
session_start();

class KVKKAPI {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Kullanıcının kişisel verilerini sil
     */
    public function deleteUserData($userId) {
        try {
            // Kullanıcının projelerini sil
            $deleteProjects = "DELETE FROM projects WHERE created_by = :user_id OR manager_id = :user_id";
            $stmt = $this->db->prepare($deleteProjects);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            // Kullanıcının dosyalarını sil
            $this->deleteUserFiles($userId);
            
            // Kullanıcıyı sil
            $deleteUser = "DELETE FROM users WHERE id = :user_id";
            $stmt = $this->db->prepare($deleteUser);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            // Log kaydı
            $this->logDataDeletion($userId);
            
            return [
                'success' => true,
                'message' => 'Kişisel verileriniz başarıyla silindi.',
                'deleted_at' => date('Y-m-d H:i:s')
            ];
            
        } catch(PDOException $e) {
            return [
                'success' => false,
                'message' => 'Veri silme işlemi başarısız: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Kullanıcının dosyalarını sil
     */
    private function deleteUserFiles($userId) {
        $uploadDir = "../uploads/";
        
        // Kullanıcı adını bul
        $query = "SELECT username FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user) {
            $userDir = $uploadDir . $user['username'] . "/";
            if (is_dir($userDir)) {
                $files = glob($userDir . "*");
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($userDir);
            }
        }
    }
    
    /**
     * Kullanıcının verilerini dışa aktar
     */
    public function exportUserData($userId) {
        try {
            // Kullanıcı bilgileri
            $userQuery = "SELECT id, username, email, full_name, title, department, employee_id, phone, role, created_at, last_login FROM users WHERE id = :user_id";
            $stmt = $this->db->prepare($userQuery);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $userData = $stmt->fetch();
            
            // Kullanıcının projeleri
            $projectsQuery = "SELECT * FROM projects WHERE created_by = :user_id OR manager_id = :user_id";
            $stmt = $this->db->prepare($projectsQuery);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $projects = $stmt->fetchAll();
            
            // Dosya listesi
            $files = $this->getUserFiles($userId);
            
            $exportData = [
                'user_info' => $userData,
                'projects' => $projects,
                'files' => $files,
                'export_date' => date('Y-m-d H:i:s'),
                'export_reason' => 'KVKK veri talep hakkı'
            ];
            
            return [
                'success' => true,
                'data' => $exportData,
                'message' => 'Verileriniz başarıyla dışa aktarıldı.'
            ];
            
        } catch(PDOException $e) {
            return [
                'success' => false,
                'message' => 'Veri dışa aktarma başarısız: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Kullanıcının dosyalarını listele
     */
    private function getUserFiles($userId) {
        $uploadDir = "../uploads/";
        
        $query = "SELECT username FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $user = $stmt->fetch();
        
        $files = [];
        if ($user) {
            $userDir = $uploadDir . $user['username'] . "/";
            if (is_dir($userDir)) {
                $fileList = glob($userDir . "*");
                foreach ($fileList as $file) {
                    if (is_file($file)) {
                        $files[] = [
                            'filename' => basename($file),
                            'size' => filesize($file),
                            'modified' => date('Y-m-d H:i:s', filemtime($file))
                        ];
                    }
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Veri silme işlemini logla
     */
    private function logDataDeletion($userId) {
        $logQuery = "INSERT INTO data_deletion_logs (user_id, deletion_date, reason) VALUES (:user_id, NOW(), 'KVKK veri silme talebi')";
        $stmt = $this->db->prepare($logQuery);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
    }
    
    /**
     * KVKK veri talep formunu işle
     */
    public function processDataRequest($requestData) {
        try {
            $query = "INSERT INTO kvkk_requests (full_name, email, phone, tc_no, request_type, description, status, created_at) 
                      VALUES (:full_name, :email, :phone, :tc_no, :request_type, :description, 'pending', NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':full_name', $requestData['fullName']);
            $stmt->bindParam(':email', $requestData['email']);
            $stmt->bindParam(':phone', $requestData['phone']);
            $stmt->bindParam(':tc_no', $requestData['tcNo']);
            $stmt->bindParam(':request_type', $requestData['requestType']);
            $stmt->bindParam(':description', $requestData['description']);
            $stmt->execute();
            
            return [
                'success' => true,
                'message' => 'KVKK veri talebiniz alınmıştır. En kısa sürede size dönüş yapılacaktır.',
                'request_id' => $this->db->lastInsertId()
            ];
            
        } catch(PDOException $e) {
            return [
                'success' => false,
                'message' => 'Talep işlemi başarısız: ' . $e->getMessage()
            ];
        }
    }
}

// API endpoint'leri
$kvkk = new KVKKAPI();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';
    
    switch($action) {
        case 'delete_user_data':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Oturum gerekli']);
                exit;
            }
            $result = $kvkk->deleteUserData($_SESSION['user_id']);
            echo json_encode($result);
            break;
            
        case 'export_user_data':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Oturum gerekli']);
                exit;
            }
            $result = $kvkk->exportUserData($_SESSION['user_id']);
            echo json_encode($result);
            break;
            
        case 'data_request':
            $result = $kvkk->processDataRequest($input);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir']);
}
?> 