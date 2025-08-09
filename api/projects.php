<?php
/**
 * Proje Yönetimi API
 * Proje Yönetim Sistemi - İSTÜ
 */

header('Content-Type: application/json; charset=utf-8');
// Güvenli CORS ayarları
$allowedOrigins = [
    'http://localhost:8000',
    'http://localhost:8080',
    'http://127.0.0.1:8000',
    'http://127.0.0.1:8080',
    'https://yourdomain.com' // Production domain'inizi buraya ekleyin
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once __DIR__ . '/../app/bootstrap.php';
session_start();

// Güvenli dosya upload fonksiyonları
function validateFileUpload($file) {
    $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain'
    ];
    
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'message' => 'Geçersiz dosya'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'message' => 'Dosya boyutu çok büyük (max: 10MB)'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['valid' => false, 'message' => 'Geçersiz dosya türü'];
    }
    
    return ['valid' => true, 'mime_type' => $mimeType];
}

function secureFileUpload($file, $userId) {
    $validation = validateFileUpload($file);
    if (!$validation['valid']) {
        return $validation;
    }
    
    $uploadDir = "../uploads/";
    $userDir = $uploadDir . $userId . "/";
    
    if (!is_dir($userDir)) {
        mkdir($userDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $userDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'valid' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => $file['size'],
            'mime_type' => $validation['mime_type']
        ];
    }
    
    return ['valid' => false, 'message' => 'Dosya yüklenirken hata oluştu'];
}

class ProjectsAPI {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        // Veritabanı bağlantısı kontrolü
        if ($this->db === null) {
            $errorMessage = 'Veritabanı bağlantısı kurulamadı. ';
            $errorMessage .= 'Lütfen şunları kontrol edin:';
            $errorMessage .= '<br>1. XAMPP\'te MySQL servisinin çalıştığından emin olun';
            $errorMessage .= '<br>2. MySQL port 3306\'da çalışıyor olmalı';
            $errorMessage .= '<br>3. Socket dosyası: /Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
            $errorMessage .= '<br>4. Root kullanıcısı ve şifresi doğru olmalı';
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $errorMessage,
                'error_type' => 'database_connection'
            ]);
            exit;
        }
    }
    
    // Kullanıcının projelerini getir
    public function getUserProjects($userId) {
        try {
            $query = "SELECT p.*, u.full_name as creator_name 
                     FROM projects p 
                     JOIN users u ON p.created_by = u.id 
                     WHERE p.created_by = :user_id 
                     ORDER BY p.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $projects = $stmt->fetchAll();
            
            // Debug: İlk projenin alanlarını logla
            if (!empty($projects)) {
                error_log("getUserProjects Debug - İlk proje alanları: " . json_encode(array_keys($projects[0])));
                error_log("getUserProjects Debug - İlk proje verisi: " . json_encode($projects[0]));
            }
            
            // Dosya bilgisini JSON'dan decode et
            foreach ($projects as &$project) {
                if ($project['file_info']) {
                    $project['file_info'] = json_decode($project['file_info'], true);
                }
            }
            
            return [
                'success' => true,
                'projects' => $projects
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ];
        }
    }
    
    // Admin için tüm projeleri getir
    public function getAllProjects() {
        try {
            $query = "SELECT p.*, u.full_name as creator_name, u.department as creator_department 
                     FROM projects p 
                     JOIN users u ON p.created_by = u.id 
                     ORDER BY p.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $projects = $stmt->fetchAll();
            
            // Dosya bilgisini JSON'dan decode et
            foreach ($projects as &$project) {
                if ($project['file_info']) {
                    $project['file_info'] = json_decode($project['file_info'], true);
                }
            }
            
            return [
                'success' => true,
                'projects' => $projects
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ];
        }
    }
    
    // Yeni proje ekle
    public function addProject($projectData, $userId) {
        try {
            // Debug log
            error_log("Proje ekleme başladı - User ID: $userId");
            error_log("Gelen veri: " . json_encode($projectData));
            
            // Zorunlu alanları kontrol et
            $requiredFields = ['title', 'subject', 'project_manager', 'responsible_person'];
            foreach ($requiredFields as $field) {
                if (empty($projectData[$field])) {
                    error_log("Zorunlu alan eksik: $field");
                    return [
                        'success' => false,
                        'message' => 'Zorunlu alan eksik: ' . $field
                    ];
                }
            }
            
            $query = "INSERT INTO projects (title, subject, description, keywords, project_manager, 
                     responsible_person, start_date, end_date, duration_days, status, file_info, created_by) 
                     VALUES (:title, :subject, :description, :keywords, :project_manager, 
                     :responsible_person, :start_date, :end_date, :duration_days, 'pending', :file_info, :created_by)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':title', $projectData['title']);
            $stmt->bindParam(':subject', $projectData['subject']);
            $stmt->bindParam(':description', $projectData['description']);
            $stmt->bindParam(':keywords', $projectData['keywords']);
            $stmt->bindParam(':project_manager', $projectData['project_manager']);
            $stmt->bindParam(':responsible_person', $projectData['responsible_person']);
            $stmt->bindParam(':start_date', $projectData['start_date']);
            $stmt->bindParam(':end_date', $projectData['end_date']);
            $stmt->bindParam(':duration_days', $projectData['duration_days']);
            $fileInfo = isset($projectData['file_info']) ? json_encode($projectData['file_info']) : null;
            $stmt->bindParam(':file_info', $fileInfo);
            $stmt->bindParam(':created_by', $userId);
            
            if ($stmt->execute()) {
                $projectId = $this->db->lastInsertId();
                error_log("Proje başarıyla eklendi - ID: $projectId");
                
                // Admin'lere bildirim gönder
                $this->notifyAdmins($projectId, $projectData['title']);
                
                return [
                    'success' => true,
                    'message' => 'Proje başarıyla eklendi',
                    'project_id' => $projectId
                ];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Proje eklenirken SQL hatası: " . json_encode($errorInfo));
                return [
                    'success' => false,
                    'message' => 'Proje eklenirken hata oluştu: ' . $errorInfo[2]
                ];
            }
        } catch (Exception $e) {
            error_log("Proje eklenirken exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ];
        }
    }
    
    // Proje detayını getir
    public function getProjectDetails($projectId, $userId, $userRole) {
        try {
            $query = "SELECT p.*, u.full_name as creator_name, u.department as creator_department 
                     FROM projects p 
                     JOIN users u ON p.created_by = u.id 
                     WHERE p.id = :project_id";
            
            // Admin değilse sadece kendi projelerini görebilir
            if ($userRole !== 'admin') {
                $query .= " AND p.created_by = :user_id";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':project_id', $projectId);
            if ($userRole !== 'admin') {
                $stmt->bindParam(':user_id', $userId);
            }
            $stmt->execute();
            
            if ($stmt->rowCount() === 1) {
                return [
                    'success' => true,
                    'project' => $stmt->fetch()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Proje bulunamadı'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ];
        }
    }
    
    // Proje güncelle
    public function updateProject($projectId, $projectData, $userId, $userRole) {
        try {
            // Yetki kontrolü
            if ($userRole !== 'admin') {
                $checkQuery = "SELECT id FROM projects WHERE id = :project_id AND created_by = :user_id";
                $checkStmt = $this->db->prepare($checkQuery);
                $checkStmt->bindParam(':project_id', $projectId);
                $checkStmt->bindParam(':user_id', $userId);
                $checkStmt->execute();
                
                if ($checkStmt->rowCount() === 0) {
                    return [
                        'success' => false,
                        'message' => 'Bu projeyi düzenleme yetkiniz yok'
                    ];
                }
            }
            
            // Dosya bilgisi varsa ekle
            $fileInfo = null;
            if (isset($projectData['file_info']) && $projectData['file_info']) {
                $fileInfo = json_encode($projectData['file_info']);
            }
            
            $query = "UPDATE projects SET 
                     title = :title, subject = :subject, description = :description, 
                     keywords = :keywords, project_manager = :project_manager, 
                     responsible_person = :responsible_person, start_date = :start_date, 
                     end_date = :end_date, duration_days = :duration_days";
            
            // Dosya bilgisi varsa query'ye ekle
            if ($fileInfo) {
                $query .= ", file_info = :file_info";
            }
            
            $query .= ", updated_at = NOW() WHERE id = :project_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':title', $projectData['title']);
            $stmt->bindParam(':subject', $projectData['subject']);
            $stmt->bindParam(':description', $projectData['description']);
            $stmt->bindParam(':keywords', $projectData['keywords']);
            $stmt->bindParam(':project_manager', $projectData['project_manager']);
            $stmt->bindParam(':responsible_person', $projectData['responsible_person']);
            $stmt->bindParam(':start_date', $projectData['start_date']);
            $stmt->bindParam(':end_date', $projectData['end_date']);
            $stmt->bindParam(':duration_days', $projectData['duration_days']);
            $stmt->bindParam(':project_id', $projectId);
            
            // Dosya bilgisi varsa bind et
            if ($fileInfo) {
                $stmt->bindParam(':file_info', $fileInfo);
            }
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Proje başarıyla güncellendi'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Proje güncellenirken hata oluştu'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ];
        }
    }
    
    // Proje sil
    public function deleteProject($projectId, $userId, $userRole) {
        try {
            // Yetki kontrolü
            if ($userRole !== 'admin') {
                $checkQuery = "SELECT id FROM projects WHERE id = :project_id AND created_by = :user_id";
                $checkStmt = $this->db->prepare($checkQuery);
                $checkStmt->bindParam(':project_id', $projectId);
                $checkStmt->bindParam(':user_id', $userId);
                $checkStmt->execute();
                
                if ($checkStmt->rowCount() === 0) {
                    return [
                        'success' => false,
                        'message' => 'Bu projeyi silme yetkiniz yok'
                    ];
                }
            }
            
            $query = "DELETE FROM projects WHERE id = :project_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':project_id', $projectId);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Proje başarıyla silindi'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Proje silinirken hata oluştu'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ];
        }
    }
    
    // Admin proje onaylama
    public function approveProject($projectId, $adminId) {
        try {
            $query = "UPDATE projects SET status = 'approved', approved_by = :admin_id, 
                     approved_at = NOW() WHERE id = :project_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':admin_id', $adminId);
            $stmt->bindParam(':project_id', $projectId);
            
            if ($stmt->execute()) {
                // Proje sahibine bildirim gönder
                $this->notifyProjectOwner($projectId, 'approved');
                
                return [
                    'success' => true,
                    'message' => 'Proje onaylandı'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Proje onaylanırken hata oluştu'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ];
        }
    }
    
    // Admin proje reddetme
    public function rejectProject($projectId, $adminId, $reason) {
        try {
            $query = "UPDATE projects SET status = 'rejected', approved_by = :admin_id, 
                     approved_at = NOW(), rejection_reason = :reason WHERE id = :project_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':admin_id', $adminId);
            $stmt->bindParam(':reason', $reason);
            $stmt->bindParam(':project_id', $projectId);
            
            if ($stmt->execute()) {
                // Proje sahibine bildirim gönder
                $this->notifyProjectOwner($projectId, 'rejected', $reason);
                
                return [
                    'success' => true,
                    'message' => 'Proje reddedildi'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Proje reddedilirken hata oluştu'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ];
        }
    }
    
    // Admin'lere bildirim gönder
    private function notifyAdmins($projectId, $projectTitle) {
        $query = "INSERT INTO notifications (user_id, title, message, type, related_project_id)
                 SELECT id, 'Yeni Proje Eklendi', :message, 'info', :project_id
                 FROM users WHERE role = 'admin'";
        
        $stmt = $this->db->prepare($query);
        $message = "Yeni proje eklendi: " . $projectTitle;
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':project_id', $projectId);
        $stmt->execute();
    }
    
    // Proje sahibine bildirim gönder
    private function notifyProjectOwner($projectId, $status, $reason = '') {
        $query = "INSERT INTO notifications (user_id, title, message, type, related_project_id)
                 SELECT created_by, :title, :message, :type, :project_id
                 FROM projects WHERE id = :project_id";
        
        $stmt = $this->db->prepare($query);
        
        if ($status === 'approved') {
            $title = 'Proje Onaylandı';
            $message = 'Projeniz onaylandı.';
            $type = 'success';
        } else {
            $title = 'Proje Reddedildi';
            $message = 'Projeniz reddedildi. Sebep: ' . $reason;
            $type = 'error';
        }
        
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':project_id', $projectId);
        $stmt->execute();
    }
}

// API endpoint'lerini işle
$projects = new ProjectsAPI();
// Layered services bootstrap (non-breaking)
$__db = (new Database())->getConnection();
$__projectRepo = new App\Repositories\ProjectRepository($__db);
$__projectService = new App\Services\ProjectService($__projectRepo);
$__projectController = new App\Controllers\ProjectsController($__projectService);
$method = $_SERVER['REQUEST_METHOD'];
$response = [];

// Session kontrolü
if (!isset($_SESSION['user_id'])) {
    $response = ['success' => false, 'message' => 'Oturum bulunamadı'];
} else {
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'];
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'list':
                $response = $__projectController->list($_SESSION);
                break;
            case 'details':
                $projectId = (int)($_GET['id'] ?? 0);
                $response = $__projectController->details($projectId, $_SESSION);
                break;
            default:
                $response = ['success' => false, 'message' => 'Geçersiz işlem'];
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'add':
                $response = $__projectController->add($input, $_SESSION);
                break;
            case 'update':
                $projectId = (int)($input['project_id'] ?? 0);
                $response = $__projectController->update($projectId, $input, $_SESSION);
                break;
            case 'delete':
                $projectId = (int)($input['project_id'] ?? 0);
                $response = $__projectController->delete($projectId, $_SESSION);
                break;
            case 'approve':
                if ($userRole === 'admin') {
                    $projectId = (int)($input['id'] ?? 0);
                    $response = $__projectController->approve($projectId, $_SESSION);
                } else {
                    $response = ['success' => false, 'message' => 'Yetkiniz yok'];
                }
                break;
            case 'reject':
                if ($userRole === 'admin') {
                    $projectId = (int)($input['id'] ?? 0);
                    $reason = (string)($input['reason'] ?? '');
                    $response = $__projectController->reject($projectId, $reason, $_SESSION);
                } else {
                    $response = ['success' => false, 'message' => 'Yetkiniz yok'];
                }
                break;
            default:
                $response = ['success' => false, 'message' => 'Geçersiz işlem'];
        }
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?> 