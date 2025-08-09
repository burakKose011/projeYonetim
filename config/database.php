<?php
/**
 * Veritabanı Bağlantı Sınıfı
 * Proje Yönetim Sistemi - İSTE
 */

// Basit .env loader (her PHP dosyasının başında bir kez çağrılmalı)
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = array_map('trim', explode('=', $line, 2));
        $_ENV[$name] = $value;
    }
}

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    private $socket;
    private $conn;
    private $maxRetries = 3;

    public function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'proje_yonetim';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
        $this->charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
        $this->socket = $_ENV['DB_SOCKET'] ?? '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
    }

    public function getConnection() {
        $this->conn = null;
        
        // Farklı bağlantı yöntemlerini dene
        $connectionMethods = [
            'socket' => function() {
                if (file_exists($this->socket)) {
                    $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset};unix_socket={$this->socket}";
                    error_log("Socket ile bağlantı deneniyor: " . $this->socket);
                    return new PDO($dsn, $this->username, $this->password);
                }
                return null;
            },
            'tcp_localhost' => function() {
                $dsn = "mysql:host=localhost;dbname={$this->db_name};charset={$this->charset};port=3306";
                error_log("TCP ile bağlantı deneniyor: localhost:3306");
                return new PDO($dsn, $this->username, $this->password);
            },
            'tcp_127' => function() {
                $dsn = "mysql:host=127.0.0.1;dbname={$this->db_name};charset={$this->charset};port=3306";
                error_log("TCP ile bağlantı deneniyor: 127.0.0.1:3306");
                return new PDO($dsn, $this->username, $this->password);
            },
            'tcp_no_port' => function() {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
                error_log("TCP ile bağlantı deneniyor: {$this->host} (varsayılan port)");
                return new PDO($dsn, $this->username, $this->password);
            }
        ];

        // Her yöntemi dene
        foreach ($connectionMethods as $method => $connectFunction) {
            for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
                try {
                    $this->conn = $connectFunction();
                    if ($this->conn) {
                        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                        $this->conn->setAttribute(PDO::ATTR_TIMEOUT, 10);
                        
                        error_log("Veritabanı bağlantısı başarılı! (Yöntem: {$method})");
                        
                        // Tabloları kontrol et ve oluştur
                        $this->createTables();
                        return $this->conn;
                    }
                } catch(PDOException $exception) {
                    error_log("Bağlantı denemesi {$attempt} başarısız ({$method}): " . $exception->getMessage());
                    
                    if ($attempt < $this->maxRetries) {
                        sleep(1); // 1 saniye bekle
                    }
                }
            }
        }
        
        // Hiçbir yöntem çalışmadı
        error_log("Tüm bağlantı yöntemleri başarısız oldu. MySQL servisinin çalıştığından emin olun.");
        return null;
    }
    
    private function createTables() {
        try {
            // Users tablosu
            $usersTable = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                title VARCHAR(100),
                department VARCHAR(100),
                employee_id VARCHAR(50),
                phone VARCHAR(20),
                role ENUM('admin', 'user', 'manager') DEFAULT 'user',
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL
            )";
            
            $this->conn->exec($usersTable);
            error_log("Users tablosu kontrol edildi/oluşturuldu");
            
            // Projects tablosu
            $projectsTable = "CREATE TABLE IF NOT EXISTS projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(200) NOT NULL,
                description TEXT,
                status ENUM('planning', 'active', 'completed', 'cancelled') DEFAULT 'planning',
                priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
                start_date DATE,
                end_date DATE,
                budget DECIMAL(10,2),
                manager_id INT,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            )";
            
                    $this->conn->exec($projectsTable);
            error_log("Projects tablosu kontrol edildi/oluşturuldu");
            
            // KVKK tabloları
            $kvkkRequestsTable = "CREATE TABLE IF NOT EXISTS kvkk_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(20),
                tc_no VARCHAR(11),
                request_type ENUM('info', 'data', 'correct', 'delete', 'third_party') NOT NULL,
                description TEXT,
                status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $this->conn->exec($kvkkRequestsTable);
            error_log("KVKK requests tablosu kontrol edildi/oluşturuldu");
            
            $dataDeletionLogsTable = "CREATE TABLE IF NOT EXISTS data_deletion_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                deletion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                reason VARCHAR(255),
                admin_notes TEXT,
                INDEX idx_user_id (user_id),
                INDEX idx_deletion_date (deletion_date)
            )";
            $this->conn->exec($dataDeletionLogsTable);
            error_log("Data deletion logs tablosu kontrol edildi/oluşturuldu");
            
            // Demo kullanıcıları ekle
            $this->insertDemoUsers();
            
        } catch(PDOException $exception) {
            error_log("Tablo oluşturma hatası: " . $exception->getMessage());
        }
    }
    
    private function insertDemoUsers() {
        try {
            // Admin kullanıcısı
            $adminQuery = "INSERT IGNORE INTO users (username, email, password_hash, full_name, title, department, employee_id, phone, role) 
                          VALUES ('admin', 'admin@iste.edu.tr', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'Sistem Yöneticisi', 'Prof. Dr.', 'Bilgi İşlem', 'ADMIN001', '05551234567', 'admin')";
            $this->conn->exec($adminQuery);
            
            // Demo kullanıcısı
            $demoQuery = "INSERT IGNORE INTO users (username, email, password_hash, full_name, title, department, role) 
                         VALUES ('demo', 'demo@iste.edu.tr', '" . password_hash('demo123', PASSWORD_DEFAULT) . "', 'Demo Kullanıcı', 'Proje Yöneticisi', 'Bilgisayar Mühendisliği', 'manager')";
            $this->conn->exec($demoQuery);
            
            error_log("Demo kullanıcılar kontrol edildi/eklendi");
            
        } catch(PDOException $exception) {
            error_log("Demo kullanıcı ekleme hatası: " . $exception->getMessage());
        }
    }
}
?> 