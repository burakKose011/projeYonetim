<?php
/**
 * Kimlik Doğrulama API
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
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once __DIR__ . '/../app/bootstrap.php';

// Güvenli session ayarları
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // HTTPS kullanıyorsanız 1 yapın
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// CSRF token oluştur
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF token kontrolü
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Input validation ve sanitization
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    // En az 8 karakter, büyük/küçük harf, rakam
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

function rateLimitCheck($action, $limit = 10, $timeWindow = 60) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "rate_limit_{$action}_{$ip}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $timeWindow];
    }
    
    if (time() > $_SESSION[$key]['reset_time']) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $timeWindow];
    }
    
    if ($_SESSION[$key]['count'] >= $limit) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

// Rate limiting durumunu temizleme fonksiyonu
function clearRateLimit($action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "rate_limit_{$action}_{$ip}";
    unset($_SESSION[$key]);
}

class AuthAPI {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        // Veritabanı bağlantısı kontrolü - daha detaylı hata mesajı
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
    
    public function login($email, $password) {
        try {
            // Rate limiting kontrolü - daha kullanıcı dostu ayarlar
            if (!rateLimitCheck('login', 10, 60)) {
                return [
                    'success' => false,
                    'message' => 'Çok fazla giriş denemesi. Lütfen 1 dakika bekleyin.'
                ];
            }
            
            // Input validation
            $email = sanitizeInput($email);
            if (!validateEmail($email)) {
                return [
                    'success' => false,
                    'message' => 'Geçersiz e-posta formatı'
                ];
            }
            
            if ($this->db === null) {
                error_log("Login: Veritabanı bağlantısı null");
                return [
                    'success' => false,
                    'message' => 'Veritabanı bağlantısı kurulamadı. Lütfen XAMPP\'te MySQL servisini başlatın.'
                ];
            }
            
            error_log("Login: Giriş denemesi - E-posta: " . $email);
            
            $query = "SELECT id, username, email, full_name, title, department, employee_id, phone, role, is_active, password_hash 
                     FROM users 
                     WHERE email = :email AND is_active = 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            error_log("Login: Sorgu sonucu - Bulunan kayıt sayısı: " . $stmt->rowCount());
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch();
                error_log("Login: Kullanıcı bulundu - ID: " . $user['id'] . ", Role: " . $user['role']);
                
                // Şifre kontrolü - password_verify kullan
                if (password_verify($password, $user['password_hash'])) {
                    error_log("Login: Şifre doğru");
                    
                    // Başarılı giriş durumunda rate limiting'i temizle
                    clearRateLimit('login');
                    
                    // Session oluştur
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Son giriş zamanını güncelle
                    $this->updateLastLogin($user['id']);
                    
                    return [
                        'success' => true,
                        'message' => 'Giriş başarılı',
                        'user' => $user,
                        'session_id' => session_id()
                    ];
                } else {
                    error_log("Login: Şifre yanlış");
                    return [
                        'success' => false,
                        'message' => 'Geçersiz e-posta veya şifre'
                    ];
                }
            } else {
                error_log("Login: Kullanıcı bulunamadı");
                return [
                    'success' => false,
                    'message' => 'Kullanıcı bulunamadı'
                ];
            }
        } catch (Exception $e) {
            error_log("Login: Veritabanı hatası: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ];
        }
    }
    
    private function updateLastLogin($userId) {
        try {
            if ($this->db === null) return;
            
            $query = "UPDATE users SET last_login = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
        } catch (Exception $e) {
            // Hata durumunda sessizce devam et
        }
    }
    
    public function logout() {
        session_destroy();
        return [
            'success' => true,
            'message' => 'Çıkış yapıldı'
        ];
    }
    
    public function checkSession() {
        if (isset($_SESSION['user_id'])) {
            return [
                'success' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'full_name' => $_SESSION['full_name'],
                    'role' => $_SESSION['role'],
                    'email' => $_SESSION['email']
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Oturum bulunamadı'
            ];
        }
    }
    
    public function register($userData) {
        try {
            if ($this->db === null) {
                error_log("Register: Veritabanı bağlantısı null");
                return [
                    'success' => false,
                    'message' => 'Veritabanı bağlantısı kurulamadı. Lütfen XAMPP\'te MySQL servisini başlatın.'
                ];
            }
            
            error_log("Register: Kullanıcı verileri alındı: " . json_encode($userData));
            
            // Kullanıcı adı yoksa e-posta öncesini ata
            if (empty($userData['username']) && !empty($userData['email'])) {
                $userData['username'] = explode('@', $userData['email'])[0];
            }
            // E-posta ve kullanıcı adı kontrolü
            $checkQuery = "SELECT id FROM users WHERE username = :username OR email = :email";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':username', $userData['username']);
            $checkStmt->bindParam(':email', $userData['email']);
            $checkStmt->execute();
            
            error_log("Register: Mevcut kullanıcı kontrolü - Bulunan kayıt sayısı: " . $checkStmt->rowCount());
            
            if ($checkStmt->rowCount() > 0) {
                error_log("Register: Kullanıcı adı veya email zaten mevcut");
                return [
                    'success' => false,
                    'message' => 'Bu kullanıcı adı veya e-posta zaten kullanılıyor'
                ];
            }
            
            // Şifreyi hashle
            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
            error_log("Register: Şifre hash'lendi - Hash uzunluğu: " . strlen($passwordHash));
            
            $query = "INSERT INTO users (username, email, password_hash, full_name, title, department, employee_id, phone, role) 
                     VALUES (:username, :email, :password_hash, :full_name, :title, :department, :employee_id, :phone, 'user')";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $userData['username']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':password_hash', $passwordHash);
            $stmt->bindParam(':full_name', $userData['full_name']);
            $stmt->bindParam(':title', $userData['title']);
            $stmt->bindParam(':department', $userData['department']);
            $stmt->bindParam(':employee_id', $userData['employee_id']);
            $stmt->bindParam(':phone', $userData['phone']);
            
            error_log("Register: INSERT sorgusu hazırlandı, execute ediliyor...");
            
            if ($stmt->execute()) {
                $userId = $this->db->lastInsertId();
                error_log("Register: Kullanıcı başarıyla eklendi - ID: " . $userId);
                return [
                    'success' => true,
                    'message' => 'Kayıt başarılı',
                    'user_id' => $userId
                ];
            } else {
                error_log("Register: Kullanıcı eklenirken hata oluştu - SQL hatası: " . implode(", ", $stmt->errorInfo()));
                return [
                    'success' => false,
                    'message' => 'Kayıt sırasında hata oluştu'
                ];
            }
        } catch (Exception $e) {
            error_log("Register: Veritabanı hatası: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ];
        }
    }

    public function updateProfile($userData) {
        try {
            if (!isset($_SESSION['user_id'])) {
                return [
                    'success' => false,
                    'message' => 'Oturum bulunamadı.'
                ];
            }
            $userId = $_SESSION['user_id'];
            $fields = [];
            $params = [];
            if (!empty($userData['full_name'])) {
                $fields[] = 'full_name = :full_name';
                $params[':full_name'] = $userData['full_name'];
            }
            if (!empty($userData['title'])) {
                $fields[] = 'title = :title';
                $params[':title'] = $userData['title'];
            }
            if (!empty($userData['department'])) {
                $fields[] = 'department = :department';
                $params[':department'] = $userData['department'];
            }
            if (!empty($userData['employee_id'])) {
                $fields[] = 'employee_id = :employee_id';
                $params[':employee_id'] = $userData['employee_id'];
            }
            if (!empty($userData['email'])) {
                $fields[] = 'email = :email';
                $params[':email'] = $userData['email'];
            }
            if (!empty($userData['phone'])) {
                $fields[] = 'phone = :phone';
                $params[':phone'] = $userData['phone'];
            }
            if (!empty($userData['password'])) {
                $fields[] = 'password_hash = :password_hash';
                $params[':password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            }
            if (empty($fields)) {
                return [
                    'success' => false,
                    'message' => 'Güncellenecek bilgi yok.'
                ];
            }
            $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':id', $userId);
            $stmt->execute();
            return [
                'success' => true,
                'message' => 'Profil başarıyla güncellendi.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Profil güncellenemedi: ' . $e->getMessage()
            ];
        }
    }

    public function getUserInfo($userId) {
        try {
            if ($this->db === null) {
                return [
                    'success' => false,
                    'message' => 'Veritabanı bağlantısı kurulamadı'
                ];
            }
            
            $query = "SELECT id, username, email, full_name, title, department, employee_id, phone, role, is_active, created_at, last_login 
                     FROM users 
                     WHERE id = :user_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch();
                return [
                    'success' => true,
                    'user' => $user
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Kullanıcı bulunamadı'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ];
        }
    }

    public function getAllUsers() {
        try {
            if ($this->db === null) {
                return [
                    'success' => false,
                    'message' => 'Veritabanı bağlantısı kurulamadı'
                ];
            }
            
            $query = "SELECT id, username, full_name, email, role FROM users WHERE is_active = 1 ORDER BY full_name";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $users = $stmt->fetchAll();
            return [
                'success' => true,
                'users' => $users
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ];
        }
    }

    public function getAdminInfo() {
        try {
            $query = "SELECT id, username, email, full_name, title, department, employee_id, phone, role FROM users WHERE LOWER(role) = 'admin' LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            if ($stmt->rowCount() === 1) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log('getAdminInfo: Admin bulundu: ' . json_encode($admin));
                return [
                    'success' => true,
                    'admin' => $admin
                ];
            } else {
                error_log('getAdminInfo: Admin bulunamadı!');
                return [
                    'success' => false,
                    'message' => 'Admin bulunamadı.'
                ];
            }
        } catch (Exception $e) {
            error_log('getAdminInfo Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Veritabanı hatası: ' . $e->getMessage()
            ];
        }
    }

    public function updateAdminInfo($adminData) {
        try {
            $query = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            if ($stmt->rowCount() !== 1) {
                return [
                    'success' => false,
                    'message' => 'Admin bulunamadı.'
                ];
            }
            $admin = $stmt->fetch();
            $fields = [];
            $params = [];
            if (!empty($adminData['username'])) {
                $fields[] = 'username = :username';
                $params[':username'] = $adminData['username'];
            }
            if (!empty($adminData['email'])) {
                $fields[] = 'email = :email';
                $params[':email'] = $adminData['email'];
            }
            if (!empty($adminData['full_name'])) {
                $fields[] = 'full_name = :full_name';
                $params[':full_name'] = $adminData['full_name'];
            }
            if (!empty($adminData['title'])) {
                $fields[] = 'title = :title';
                $params[':title'] = $adminData['title'];
            }
            if (!empty($adminData['department'])) {
                $fields[] = 'department = :department';
                $params[':department'] = $adminData['department'];
            }
            if (!empty($adminData['employee_id'])) {
                $fields[] = 'employee_id = :employee_id';
                $params[':employee_id'] = $adminData['employee_id'];
            }
            if (!empty($adminData['phone'])) {
                $fields[] = 'phone = :phone';
                $params[':phone'] = $adminData['phone'];
            }
            if (!empty($adminData['password'])) {
                $fields[] = 'password_hash = :password_hash';
                $params[':password_hash'] = password_hash($adminData['password'], PASSWORD_DEFAULT);
            }
            if (empty($fields)) {
                return [
                    'success' => false,
                    'message' => 'Güncellenecek bilgi yok.'
                ];
            }
            $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt2 = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt2->bindValue($key, $value);
            }
            $stmt2->bindValue(':id', $admin['id']);
            $stmt2->execute();
            return [
                'success' => true,
                'message' => 'Admin bilgileri güncellendi.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Güncelleme hatası: ' . $e->getMessage()
            ];
        }
    }
    
    public function changePassword($currentPassword, $newPassword, $userId = null) {
        try {
            // Eğer userId belirtilmemişse, session'dan al
            if ($userId === null) {
                if (!isset($_SESSION['user_id'])) {
                    return [
                        'success' => false,
                        'message' => 'Oturum bulunamadı. Lütfen tekrar giriş yapın.'
                    ];
                }
                $userId = $_SESSION['user_id'];
            }
            
            // Kullanıcıyı getir
            $query = "SELECT password_hash FROM users WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Kullanıcı bulunamadı.'
                ];
            }
            
            $user = $stmt->fetch();
            
            // Mevcut şifreyi kontrol et
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Mevcut şifre yanlış.'
                ];
            }
            
            // Yeni şifreyi hash'le ve güncelle
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $updateQuery = "UPDATE users SET password_hash = :password_hash WHERE id = :id";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindParam(':password_hash', $newPasswordHash);
            $updateStmt->bindParam(':id', $userId);
            $updateStmt->execute();
            
            return [
                'success' => true,
                'message' => 'Şifre başarıyla değiştirildi.'
            ];
            
        } catch (Exception $e) {
            error_log("changePassword hatası: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Şifre değiştirme hatası: ' . $e->getMessage()
            ];
        }
    }

    public function forgotPassword($email) {
        try {
            $email = sanitizeInput($email);
            if (!validateEmail($email)) {
                return [
                    'success' => false,
                    'message' => 'Geçersiz e-posta formatı'
                ];
            }
            $query = "SELECT id, username, full_name, email FROM users WHERE email = :email AND is_active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Bu e-posta adresi ile kayıtlı kullanıcı bulunamadı.'
                ];
            }
            $user = $stmt->fetch();
            $tempPassword = bin2hex(random_bytes(4));
            $tempPasswordHash = password_hash($tempPassword, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE users SET password_hash = :password_hash WHERE id = :id";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindParam(':password_hash', $tempPasswordHash);
            $updateStmt->bindParam(':id', $user['id']);
            $updateStmt->execute();
            if ($_ENV['APP_ENV'] !== 'production') {
                error_log("Şifremi unuttum - Kullanıcı: {$user['email']}, Geçici şifre: $tempPassword");
            }
            return [
                'success' => true,
                'message' => 'Geçici şifreniz e-posta adresinize gönderildi. Geliştirme ortamında şifre log dosyasında görülebilir.',
                'tempPassword' => $_ENV['APP_ENV'] !== 'production' ? $tempPassword : null
            ];
        } catch (Exception $e) {
            error_log("forgotPassword hatası: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Şifre sıfırlama hatası: ' . $e->getMessage()
            ];
        }
    }

    public function forgotPasswordByPhone($phone) {
        try {
            $phone = sanitizeInput($phone);
            // Telefon numarası formatını temizle
            $phone = preg_replace('/[^0-9]/', '', $phone);
            
            if (strlen($phone) < 10) {
                return [
                    'success' => false,
                    'message' => 'Geçersiz telefon numarası formatı'
                ];
            }
            
            $query = "SELECT id, username, full_name, phone FROM users WHERE phone = :phone AND is_active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':phone', $phone);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Bu telefon numarası ile kayıtlı kullanıcı bulunamadı.'
                ];
            }
            
            $user = $stmt->fetch();
            $tempPassword = bin2hex(random_bytes(4));
            $tempPasswordHash = password_hash($tempPassword, PASSWORD_DEFAULT);
            
            $updateQuery = "UPDATE users SET password_hash = :password_hash WHERE id = :id";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindParam(':password_hash', $tempPasswordHash);
            $updateStmt->bindParam(':id', $user['id']);
            $updateStmt->execute();
            
            if ($_ENV['APP_ENV'] !== 'production') {
                error_log("Şifremi unuttum (SMS) - Kullanıcı: {$user['phone']}, Geçici şifre: $tempPassword");
            }
            
            return [
                'success' => true,
                'message' => 'Geçici şifreniz SMS ile gönderildi. Geliştirme ortamında şifre log dosyasında görülebilir.',
                'tempPassword' => $_ENV['APP_ENV'] !== 'production' ? $tempPassword : null
            ];
        } catch (Exception $e) {
            error_log("forgotPasswordByPhone hatası: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'SMS şifre sıfırlama hatası: ' . $e->getMessage()
            ];
        }
    }
}

// API endpoint'lerini işle
try {
    $auth = new AuthAPI();
// Layered services bootstrap (non-breaking)
$__db = (new Database())->getConnection();
$__authRepo = new App\Repositories\AuthRepository($__db);
$__authService = new App\Services\AuthService($__authRepo);
$__authController = new App\Controllers\AuthController($__authService);
    $method = $_SERVER['REQUEST_METHOD'];
    $response = [];

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'login':
                $response = $__authController->login($input['email'], $input['password']);
                // also set session like original on success
                if (!empty($response['success']) && !empty($response['user'])) {
                    $_SESSION['user_id'] = $response['user']['id'];
                    $_SESSION['username'] = $response['user']['username'];
                    $_SESSION['full_name'] = $response['user']['full_name'];
                    $_SESSION['role'] = $response['user']['role'];
                    $_SESSION['email'] = $response['user']['email'];
                    $response['session_id'] = session_id();
                }
                break;
            case 'register':
                $response = $__authController->register($input);
                break;
            case 'updateProfile':
                $response = $auth->updateProfile($input);
                break;
            case 'getAdminInfo':
                $response = $__authController->getAdminInfo();
                break;
            case 'updateAdminInfo':
                $response = $__authController->updateAdminInfo($input);
                break;
            case 'changePassword':
                if (!isset($_SESSION['user_id'])) { $response = ['success'=>false,'message'=>'Oturum bulunamadı']; break; }
                $response = $__authController->changePassword((int)$_SESSION['user_id'], $input['currentPassword'], $input['newPassword']);
                break;
            case 'forgotPassword':
                $response = $__authController->forgotPassword($input['email']);
                break;
            case 'forgotPasswordByPhone':
                $response = $__authController->forgotPasswordByPhone($input['phone']);
                break;
            case 'logout':
                $response = $auth->logout();
                break;
            case 'clearRateLimit':
                clearRateLimit('login');
                $response = ['success' => true, 'message' => 'Rate limiting temizlendi'];
                break;
            default:
                $response = ['success' => false, 'message' => 'Geçersiz işlem'];
        }
    } elseif ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'userinfo':
                if (isset($_SESSION['user_id'])) {
                    $response = $__authController->userInfo((int)$_SESSION['user_id']);
                } else {
                    $response = ['success' => false, 'message' => 'Oturum bulunamadı'];
                }
                break;
            case 'getallusers':
                $response = $__authController->allUsers();
                break;
            case 'getAdminInfo':
                $response = $auth->getAdminInfo();
                break;
            default:
                $response = $auth->checkSession();
        }
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Sistem hatası: ' . $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?> 