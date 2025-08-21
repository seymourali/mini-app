# 📘 Layihənin Quraşdırılması və İstifadə Təlimatı

Bu sənəd layihəni yerli serverdə necə quraşdırmaq və işə salmaq barədə təlimat verir.

---

## ⚙️ 1. Quraşdırma

Layihəni işə salmaq üçün aşağıdakı addımları yerinə yetirin:

### ✅ PHP Versiyası

Layihənin düzgün işləməsi üçün **PHP 7.4 və ya daha yeni versiyası** tələb olunur.

### ✅ Verilənlər Bazasının Bağlantısı

Verilənlər bazası bağlantısı **`src/config/database.php`** faylında konfiqurasiya edilir.  
Aşağıdakı dəyişənləri öz lokal parametrlərinizə uyğun dəyişin:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'password');
define('DB_NAME', 'your_database');
```

### ✅ Composer Asılılıqları

Layihədə istifadə olunan bütün PHP asılılıqlarını (PhpSpreadsheet, Dompdf, PHPMailer) yükləmək üçün:

```
composer install
```

## 🗄️ 2. Verilənlər Bazasının Scheması

Lazım olan users cədvəlini yaratmaq üçün aşağıdakı SQL skriptindən istifadə edin:

```
CREATE TABLE `registrations` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`full_name` varchar(255) NOT NULL,
`email` varchar(255) NOT NULL,
`company` varchar(255) DEFAULT NULL,
`created_at` timestamp NOT NULL DEFAULT current_timestamp(),
PRIMARY KEY (`id`)
);
```

## 📧 3. SMTP Ayarları

Form vasitəsilə yeni istifadəçi qeydiyyatdan keçdikdə, sistem adminə e-poçt bildirişi göndərir.
Bunun işləməsi üçün src/config/database.php faylında SMTP ayarlarını əlavə edin:

define('SMTP_HOST', 'smtp.example.com');
define('SMTP_USER', 'your-email@example.com');
define('SMTP_PASS', 'your-password');
define('ADMIN_EMAIL', 'admin@example.com');

## 🌐 4. Tətbiqə Giriş

Aşağıdakı linklər vasitəsilə layihənin əsas səhifələrinə daxil ola bilərsiniz:

📝 Qeydiyyat Formu: Yeni istifadəçilərin qeydiyyatı
👉 http://localhost/index.php

📋 İstifadəçi Siyahısı: Qeydiyyatdan keçmiş istifadəçilərin siyahısı və məlumatların ixracı
👉 http://localhost/list.php
