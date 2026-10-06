# HPP CaseTrack — Setup Instructions

## 🚀 Cara Menjalankan Aplikasi

### 1. Prasyarat
- **XAMPP / Laragon / WampServer** (PHP 8.1+ & MySQL 5.7+)
- Browser modern (Chrome, Firefox, Edge)

---

### 2. Setup Database

1. Buka **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Buat database baru: `hpp_casetrack`
3. Import file: **`database.sql`**
   - Klik tab "Import" → pilih `database.sql` → klik "Go"

---

### 3. Konfigurasi Koneksi Database

Buka `config/database.php` dan sesuaikan:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Username MySQL Anda
define('DB_PASS', '');           // Password MySQL Anda
define('DB_NAME', 'hpp_casetrack');
```

---

### 4. Letakkan di Web Server

Copy seluruh folder `hpp-casetrack/` ke:
- **XAMPP**: `C:/xampp/htdocs/hpp-casetrack/`
- **Laragon**: `C:/laragon/www/hpp-casetrack/`

---

### 5. Akses Aplikasi

Buka browser: `http://localhost/hpp-casetrack/`

---

## 🔑 Akun Demo (password: `password`)

| Role | Email | Keterangan |
|------|-------|------------|
| Administrator | `admin@hpp-casetrack.id` | Akses penuh sistem |
| Legal Admin | `legal@hpp-casetrack.id` | Kelola pengaduan & kasus |
| CEO | `ceo@hpp-casetrack.id` | Approval kontrak & laporan |
| Pengacara PJ | `budi@hpp-casetrack.id` | Pimpin tim kasus |
| Lawyer | `sari@hpp-casetrack.id` | Update lapangan |
| Finance | `finance@hpp-casetrack.id` | Kelola invoice |
| Head Finance | `headfinance@hpp-casetrack.id` | Otorisasi reimbursement |
| Klien | `klien@demo.id` | Akun klien demo |

---

## 📁 Struktur Direktori

```
hpp-casetrack/
├── index.php              ← Landing page
├── login.php              ← Halaman login
├── register.php           ← Registrasi klien
├── otp-verify.php         ← Verifikasi OTP
├── 403.php                ← Halaman akses ditolak
│
├── config/
│   ├── database.php       ← Koneksi PDO
│   └── auth.php           ← Auth & RBAC
│
├── includes/
│   ├── header.php         ← HTML head + topbar
│   ├── sidebar.php        ← Sidebar navigasi dinamis
│   ├── footer.php         ← Footer & script
│   └── functions.php      ← Helper functions
│
├── assets/
│   ├── css/app.css        ← Design system CSS
│   └── js/
│       ├── app.js         ← Global JavaScript
│       └── map.js         ← Leaflet.js module
│
├── client/                ← Modul Klien
├── legal-admin/           ← Modul Legal Admin
├── ceo/                   ← Modul CEO
├── pengacara-pj/          ← Modul Pengacara PJ
├── lawyer/                ← Modul Lawyer
├── finance/               ← Modul Finance
├── head-finance/          ← Modul Head Finance
├── admin/                 ← Modul Administrator
│
├── api/                   ← REST API endpoints
│   ├── auth/logout.php
│   └── notifications/
│
├── uploads/               ← File uploads (auto-created)
└── database.sql           ← Schema & seed data
```

---

## 🗺️ Fitur Leaflet.js (Geotagging)

- Halaman: `/lawyer/field-update.php`
- Klik tombol **"Deteksi GPS"** untuk mendapatkan koordinat otomatis
- Klik peta untuk pin manual
- Draggable marker dengan reverse geocoding (nama lokasi otomatis)
- Koordinat disimpan ke database `case_updates.latitude/longitude`

---

## 💳 Simulasi Pembayaran

1. Klien menerima invoice di `/client/invoice.php`
2. Klien transfer manual ke rekening yang tertera
3. Klien upload bukti transfer (JPG/PNG/PDF)
4. Finance memverifikasi di `/finance/invoice-detail.php?id=X`
5. Status berubah menjadi **"Lunas"** dan klien mendapat notifikasi
