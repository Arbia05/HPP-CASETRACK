-- ============================================================
-- HPP-CaseTrack Database Schema v2.0
-- Updated: Synced with application code
-- ============================================================

CREATE DATABASE IF NOT EXISTS hpp_casetrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hpp_casetrack;

-- ============================================================
-- TABLE 1: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(100) UNIQUE,
    email          VARCHAR(150) UNIQUE NOT NULL,
    password       VARCHAR(255) NOT NULL,
    role           ENUM('client','legal_admin','ceo','pengacara_pj','lawyer','finance','head_finance','admin') NOT NULL DEFAULT 'client',
    name           VARCHAR(150) NOT NULL,
    phone          VARCHAR(20),
    address        TEXT,
    photo          VARCHAR(255),
    is_active      TINYINT(1) DEFAULT 1,
    otp_code       VARCHAR(10),
    otp_expires_at DATETIME,
    email_verified_at DATETIME,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE 2: complaints (pengaduan awal klien)
-- ============================================================
CREATE TABLE IF NOT EXISTS complaints (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    client_id      INT NOT NULL,
    category       VARCHAR(100),
    title          VARCHAR(255) NOT NULL,
    description    TEXT NOT NULL,
    attachment_path VARCHAR(255),
    status         ENUM('new','pending_completion','verified','rejected','revision_requested') DEFAULT 'new',
    admin_notes    TEXT,
    reviewed_by    INT,
    reviewed_at    DATETIME,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE 3: cases (kasus perkara)
-- ============================================================
CREATE TABLE IF NOT EXISTS cases (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    case_number    VARCHAR(30) UNIQUE NOT NULL,
    complaint_id   INT,
    client_id      INT NOT NULL,
    assigned_pj_id INT,
    case_type      VARCHAR(100),
    title          VARCHAR(255),
    description    TEXT,
    strategy       TEXT,
    status         ENUM('pending','active','on_hold','closed','won','lost','cancelled') DEFAULT 'pending',
    opened_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    closed_at      DATETIME,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id)   REFERENCES complaints(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id)      REFERENCES users(id),
    FOREIGN KEY (assigned_pj_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE 4: case_team
-- ============================================================
CREATE TABLE IF NOT EXISTS case_team (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    case_id    INT NOT NULL,
    lawyer_id  INT NOT NULL,
    role       VARCHAR(100) DEFAULT 'lawyer',
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_member (case_id, lawyer_id),
    FOREIGN KEY (case_id)   REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (lawyer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE 5: case_activities (field updates + timeline)
-- ============================================================
CREATE TABLE IF NOT EXISTS case_activities (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    case_id     INT NOT NULL,
    user_id     INT NOT NULL,
    activity_type VARCHAR(50) DEFAULT 'update',
    description TEXT NOT NULL,
    location    VARCHAR(255),
    latitude    DECIMAL(10,8),
    longitude   DECIMAL(11,8),
    evidence_path VARCHAR(255),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================================
-- TABLE 6: case_documents
-- ============================================================
CREATE TABLE IF NOT EXISTS case_documents (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    case_id     INT NOT NULL,
    uploaded_by INT NOT NULL,
    file_path   VARCHAR(255) NOT NULL,
    file_name   VARCHAR(255),
    file_type   VARCHAR(50) DEFAULT 'document',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id)     REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- ============================================================
-- TABLE 7: invoices
-- ============================================================
CREATE TABLE IF NOT EXISTS invoices (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    case_id        INT,
    client_id      INT NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    amount         DECIMAL(15,2) NOT NULL,
    description    TEXT,
    payment_proof_path VARCHAR(255),
    status         ENUM('unpaid','verification','paid','overdue') DEFAULT 'unpaid',
    due_date       DATE,
    paid_at        DATETIME,
    verified_by    INT,
    verified_at    DATETIME,
    notes          TEXT,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id)     REFERENCES cases(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id)   REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE 8: reimbursements
-- ============================================================
CREATE TABLE IF NOT EXISTS reimbursements (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    case_id      INT NOT NULL,
    submitted_by INT NOT NULL,
    description  TEXT NOT NULL,
    amount       DECIMAL(15,2) NOT NULL,
    receipt_path VARCHAR(255),
    activity_date DATE,
    status       ENUM('pending','approved','rejected','paid') DEFAULT 'pending',
    reviewed_by  INT,
    review_notes TEXT,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id)      REFERENCES cases(id),
    FOREIGN KEY (submitted_by) REFERENCES users(id),
    FOREIGN KEY (reviewed_by)  REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE 9: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    title          VARCHAR(255),
    message        TEXT NOT NULL,
    type           VARCHAR(50) DEFAULT 'system',
    link           VARCHAR(255),
    reference_id   INT,
    reference_type VARCHAR(50),
    is_read        TINYINT(1) DEFAULT 0,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE 10: milestones
-- ============================================================
CREATE TABLE IF NOT EXISTS milestones (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    case_id     INT NOT NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    due_date    DATE,
    status      ENUM('pending','in_progress','completed','overdue') DEFAULT 'pending',
    created_by  INT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id)    REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE 11: lawyers (profil pengacara)
-- ============================================================
CREATE TABLE IF NOT EXISTS lawyers (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL UNIQUE,
    license_number      VARCHAR(100),
    specialization      VARCHAR(255),
    years_experience    INT DEFAULT 0,
    bio                 TEXT,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- SEED DATA: Default accounts (password: password)
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi = "password"
-- ============================================================

INSERT INTO users (email, password, role, name, phone, is_active, email_verified_at) VALUES
('admin@hpp-casetrack.id',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',        'Administrator Sistem',     '08100000001', 1, NOW()),
('legal@hpp-casetrack.id',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'legal_admin',  'Legal Admin HPP',          '08100000002', 1, NOW()),
('ceo@hpp-casetrack.id',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ceo',          'Direktur HPP Law Office',  '08100000003', 1, NOW()),
('finance@hpp-casetrack.id',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'finance',      'Staff Keuangan',           '08100000004', 1, NOW()),
('headfinance@hpp-casetrack.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'head_finance', 'Kepala Keuangan',          '08100000005', 1, NOW()),
('budi@hpp-casetrack.id',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pengacara_pj', 'Budi Santoso, S.H.',       '08100000006', 1, NOW()),
('sari@hpp-casetrack.id',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lawyer',       'Sari Wulandari, S.H.',     '08100000007', 1, NOW()),
('klien@demo.id',                '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client',       'Budi Hartono',             '08123456789', 1, NOW());

INSERT INTO lawyers (user_id, license_number, specialization, years_experience, bio) VALUES
(6, 'LIC-2019-001', 'Litigasi Perdata & Pidana', 8, 'Pengacara senior dengan pengalaman 8 tahun di bidang litigasi perdata dan pidana.'),
(7, 'LIC-2021-002', 'Hukum Bisnis & Korporat',   4, 'Spesialis hukum bisnis, merger, dan akuisisi korporat.');

-- Demo complaint
INSERT INTO complaints (client_id, category, title, description, status) VALUES
(8, 'Sengketa Tanah', 'Sengketa Kepemilikan Tanah Kavling B-12', 'Saya ingin mengajukan gugatan atas sengketa tanah di Jakarta Selatan yang dikuasai pihak ketiga tanpa hak.', 'verified');

-- Demo case
INSERT INTO cases (case_number, complaint_id, client_id, assigned_pj_id, case_type, title, description, status) VALUES
('HPP-2025-0001', 1, 8, 6, 'Sengketa Tanah', 'Sengketa Kepemilikan Tanah Kavling B-12', 'Klien mengajukan gugatan atas sengketa tanah kavling B-12 di Jakarta Selatan.', 'active');

-- Demo team
INSERT INTO case_team (case_id, lawyer_id, role) VALUES (1, 7, 'lawyer');

-- Demo activity
INSERT INTO case_activities (case_id, user_id, activity_type, description, location, latitude, longitude) VALUES
(1, 6, 'field_visit', 'Survei lokasi tanah sengketa di kawasan Kebayoran Baru. Didokumentasikan kondisi fisik lahan dan batas-batas tanah.', 'Kebayoran Baru, Jakarta Selatan', -6.2441, 106.7964),
(1, 7, 'update', 'Penelitian yurisprudensi terkait sengketa tanah serupa. Ditemukan 3 putusan MA yang mendukung posisi klien.', NULL, NULL, NULL);

-- Demo invoice
INSERT INTO invoices (case_id, client_id, invoice_number, amount, description, status, due_date) VALUES
(1, 8, 'INV-202501-0001', 15000000.00, 'Retainer Fee — Penanganan Kasus Sengketa Tanah Kavling B-12', 'unpaid', DATE_ADD(NOW(), INTERVAL 14 DAY));

-- Demo notifications
INSERT INTO notifications (user_id, title, message, type, link) VALUES
(8, 'Kasus Anda Aktif', 'Kasus HPP-2025-0001 sudah aktif dan tim pengacara sedang bekerja.', 'case', 'client/case-detail.php?id=1'),
(8, 'Invoice Diterbitkan', 'Invoice INV-202501-0001 senilai Rp 15.000.000 telah diterbitkan. Harap segera dilunasi.', 'invoice', 'client/invoice.php'),
(6, 'Ditugaskan ke Kasus', 'Anda ditugaskan sebagai Pengacara PJ untuk kasus HPP-2025-0001.', 'case', 'pengacara-pj/case-detail.php?id=1');
