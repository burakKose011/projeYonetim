# Proje Yönetim Sistemi

Modern ve güvenli bir proje yönetim sistemi. PHP, MySQL ve modern web teknolojileri kullanılarak geliştirilmiştir.

## 🚀 Özellikler

### 👥 Kullanıcı Yönetimi
- Güvenli kullanıcı kayıt ve giriş sistemi
- Rol tabanlı erişim kontrolü (Admin/User)
- Profil yönetimi
- Şifre değiştirme

### 📋 Proje Yönetimi
- Proje oluşturma ve düzenleme
- Dosya yükleme sistemi
- Proje durumu takibi (Beklemede/Onaylandı/Reddedildi)
- Admin onay sistemi

### 🔍 Filtreleme ve Arama
- Durum bazlı filtreleme
- Kullanıcı bazlı filtreleme
- Tarih aralığı filtreleme
- Anahtar kelime arama

### 🛡️ Güvenlik Özellikleri
- SQL Injection koruması
- XSS koruması
- CSRF token sistemi
- Rate limiting
- Güvenli dosya upload
- Session güvenliği

## 📋 Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- XAMPP (geliştirme için)
- Modern web tarayıcısı

## 🛠️ Kurulum

### 1. Projeyi İndirin
```bash
git clone [repository-url]
cd projeYonetim
```

### 2. Veritabanını Kurun
- XAMPP'i başlatın
- phpMyAdmin'e gidin
- `proje_yonetim` adında yeni bir veritabanı oluşturun
- Tablolar otomatik olarak oluşturulacaktır

### 3. Konfigürasyon
```bash
# config/env.example dosyasını .env olarak kopyalayın
cp config/env.example config/.env

# Veritabanı ayarlarını düzenleyin
nano config/.env
```

### 4. Sunucuyu Başlatın
```bash
# Geliştirme sunucusu
php -S localhost:8000 -t .

# Veya XAMPP ile
# Dosyaları htdocs klasörüne kopyalayın
```

### 5. Erişim
- Tarayıcıda `http://localhost:8000` adresine gidin
- Demo kullanıcılar:
  - Admin: `admin@iste.edu.tr` / `admin123`
  - User: `demo@iste.edu.tr` / `demo123`

## 🔧 Production Deployment

### 1. Güvenlik Ayarları
```bash
# .env dosyasında
APP_ENV=production
APP_DEBUG=false
FORCE_HTTPS=true
```

### 2. Veritabanı Güvenliği
- Güçlü şifre kullanın
- Sadece gerekli kullanıcıları oluşturun
- Düzenli yedekleme yapın

### 3. SSL Sertifikası
- HTTPS kullanın
- Güvenli cookie ayarları

### 4. Hosting Seçenekleri
- **Heroku**: PHP + MySQL add-on
- **Railway**: Modern platform
- **Render**: Statik hosting
- **VPS**: Tam kontrol

## 🛡️ Güvenlik Kontrol Listesi

### ✅ Tamamlanan
- [x] SQL Injection koruması
- [x] XSS koruması
- [x] CSRF token sistemi
- [x] Rate limiting
- [x] Güvenli dosya upload
- [x] Session güvenliği
- [x] Input validation
- [x] CORS güvenliği

### 🔄 Geliştirilebilir
- [ ] Email doğrulama
- [ ] İki faktörlü kimlik doğrulama
- [ ] API rate limiting
- [ ] Logging sistemi
- [ ] Backup sistemi

## 📁 Proje Yapısı

```
projeYonetim/
├── api/                    # API dosyaları
│   ├── auth.php           # Kimlik doğrulama
│   └── projects.php       # Proje yönetimi
├── config/                 # Konfigürasyon
│   ├── database.php       # Veritabanı bağlantısı
│   └── env.example        # Environment örneği
├── uploads/               # Yüklenen dosyalar
├── .htaccess             # Apache güvenlik ayarları
├── index.html            # Ana sayfa
├── admin_dashboard.html  # Admin paneli
├── dashboard.html        # Kullanıcı paneli
├── style.css             # Stil dosyası
└── README.md             # Bu dosya
```

## 🤝 Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/AmazingFeature`)
3. Commit yapın (`git commit -m 'Add some AmazingFeature'`)
4. Push yapın (`git push origin feature/AmazingFeature`)
5. Pull Request oluşturun

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır.

## 📞 İletişim

- **Geliştirici**: [Adınız]
- **Email**: [email@example.com]
- **Proje Linki**: [https://github.com/username/proje-yonetim]

## 🙏 Teşekkürler

- Font Awesome (ikonlar için)
- Modern CSS framework'leri
- PHP topluluğu 