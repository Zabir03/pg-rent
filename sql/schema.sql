-- ============================================================
-- PG Rent Management System - Database Schema
-- MySQL 8+ / PHP 8+
-- ============================================================

CREATE DATABASE IF NOT EXISTS pg_rent_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pg_rent_db;

-- ----------------------------------------------------------
-- users : all logins (super_admin + pg_owner)
-- ----------------------------------------------------------
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(160) NOT NULL UNIQUE,
  phone         VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('super_admin','pg_owner') NOT NULL DEFAULT 'pg_owner',
  status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  reset_token   VARCHAR(255) DEFAULT NULL,
  reset_expires DATETIME DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- pg_owners : profile + business info, 1:1 with users
-- ----------------------------------------------------------
CREATE TABLE pg_owners (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  pg_name      VARCHAR(160) NOT NULL,
  address      TEXT,
  city         VARCHAR(80),
  logo         VARCHAR(255),
  is_active    TINYINT(1) DEFAULT 1,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_owner_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_owner_user (user_id)
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- rooms
-- ----------------------------------------------------------
CREATE TABLE rooms (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  owner_id      INT NOT NULL,
  room_number   VARCHAR(30) NOT NULL,
  floor_number  VARCHAR(20),
  room_type     VARCHAR(40),                 -- single / double / triple etc.
  capacity      INT NOT NULL DEFAULT 1,
  rent_amount   DECIMAL(10,2) NOT NULL DEFAULT 0,
  status        ENUM('available','occupied','maintenance') DEFAULT 'available',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_room_owner FOREIGN KEY (owner_id) REFERENCES pg_owners(id) ON DELETE CASCADE,
  INDEX idx_room_owner (owner_id),
  UNIQUE KEY uq_room (owner_id, room_number)
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- beds
-- ----------------------------------------------------------
CREATE TABLE beds (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  room_id     INT NOT NULL,
  bed_label   VARCHAR(20) NOT NULL,          -- A, B, 1, 2 ...
  status      ENUM('available','occupied') DEFAULT 'available',
  CONSTRAINT fk_bed_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  INDEX idx_bed_room (room_id)
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- students
-- ----------------------------------------------------------
CREATE TABLE students (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  owner_id         INT NOT NULL,
  full_name        VARCHAR(120) NOT NULL,
  mobile           VARCHAR(20),
  parent_name      VARCHAR(120),
  parent_mobile    VARCHAR(20),
  email            VARCHAR(160),
  address          TEXT,
  college_company  VARCHAR(160),
  id_proof_type    VARCHAR(60),
  id_proof_number  VARCHAR(80),
  joining_date     DATE,
  leaving_date     DATE DEFAULT NULL,
  security_deposit DECIMAL(10,2) DEFAULT 0,
  monthly_rent     DECIMAL(10,2) DEFAULT 0,
  photo            VARCHAR(255),
  aadhaar_doc      VARCHAR(255),
  id_proof_doc     VARCHAR(255),
  status           ENUM('active','left') DEFAULT 'active',
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_student_owner FOREIGN KEY (owner_id) REFERENCES pg_owners(id) ON DELETE CASCADE,
  INDEX idx_student_owner (owner_id),
  INDEX idx_student_status (status)
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- room_allocations : which student is in which bed
-- ----------------------------------------------------------
CREATE TABLE room_allocations (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  student_id   INT NOT NULL,
  room_id      INT NOT NULL,
  bed_id       INT DEFAULT NULL,
  allocated_on DATE NOT NULL,
  vacated_on   DATE DEFAULT NULL,
  is_active    TINYINT(1) DEFAULT 1,
  CONSTRAINT fk_alloc_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_alloc_room    FOREIGN KEY (room_id)    REFERENCES rooms(id)    ON DELETE CASCADE,
  CONSTRAINT fk_alloc_bed     FOREIGN KEY (bed_id)     REFERENCES beds(id)     ON DELETE SET NULL,
  INDEX idx_alloc_student (student_id),
  INDEX idx_alloc_room (room_id)
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- rent_payments
-- ----------------------------------------------------------
CREATE TABLE rent_payments (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  owner_id       INT NOT NULL,
  student_id     INT NOT NULL,
  rent_month     CHAR(7) NOT NULL,           -- format YYYY-MM
  amount         DECIMAL(10,2) NOT NULL,
  late_fee       DECIMAL(10,2) DEFAULT 0,
  due_date       DATE,
  payment_date   DATE DEFAULT NULL,
  payment_mode   ENUM('cash','upi','bank_transfer','card') DEFAULT 'cash',
  transaction_id VARCHAR(120),
  status         ENUM('paid','pending','overdue') DEFAULT 'pending',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rent_owner   FOREIGN KEY (owner_id)   REFERENCES pg_owners(id) ON DELETE CASCADE,
  CONSTRAINT fk_rent_student FOREIGN KEY (student_id) REFERENCES students(id)  ON DELETE CASCADE,
  INDEX idx_rent_owner (owner_id),
  INDEX idx_rent_status (status),
  INDEX idx_rent_month (rent_month)
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- expenses
-- ----------------------------------------------------------
CREATE TABLE expenses (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  owner_id     INT NOT NULL,
  category     VARCHAR(60) NOT NULL,         -- electricity, water, internet ...
  amount       DECIMAL(10,2) NOT NULL,
  expense_date DATE NOT NULL,
  note         VARCHAR(255),
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_expense_owner FOREIGN KEY (owner_id) REFERENCES pg_owners(id) ON DELETE CASCADE,
  INDEX idx_expense_owner (owner_id),
  INDEX idx_expense_date (expense_date)
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- subscriptions
-- ----------------------------------------------------------
CREATE TABLE subscriptions (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  owner_id     INT NOT NULL,
  plan         ENUM('monthly','yearly') NOT NULL,
  amount       DECIMAL(10,2) NOT NULL,
  start_date   DATE NOT NULL,
  renewal_date DATE NOT NULL,
  status       ENUM('active','expired','pending') DEFAULT 'pending',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sub_owner FOREIGN KEY (owner_id) REFERENCES pg_owners(id) ON DELETE CASCADE,
  INDEX idx_sub_owner (owner_id)
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- invoices
-- ----------------------------------------------------------
CREATE TABLE invoices (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  subscription_id INT NOT NULL,
  invoice_number VARCHAR(60) NOT NULL UNIQUE,
  amount         DECIMAL(10,2) NOT NULL,
  issued_on      DATE NOT NULL,
  status         ENUM('paid','unpaid') DEFAULT 'unpaid',
  CONSTRAINT fk_inv_sub FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- notifications
-- ----------------------------------------------------------
CREATE TABLE notifications (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  title      VARCHAR(160) NOT NULL,
  body       TEXT,
  type       VARCHAR(40),                    -- rent_due, overdue, sub_expiry ...
  is_read    TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notif_user (user_id)
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- support_tickets
-- ----------------------------------------------------------
CREATE TABLE support_tickets (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  subject    VARCHAR(200) NOT NULL,
  message    TEXT,
  status     ENUM('open','in_progress','closed') DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ticket_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- activity_logs
-- ----------------------------------------------------------
CREATE TABLE activity_logs (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT,
  action     VARCHAR(160) NOT NULL,
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_log_user (user_id)
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- settings : key/value, global or per-owner
-- ----------------------------------------------------------
CREATE TABLE settings (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  owner_id    INT DEFAULT NULL,
  setting_key VARCHAR(80) NOT NULL,
  setting_val TEXT,
  UNIQUE KEY uq_setting (owner_id, setting_key)
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- Seed: default super admin
-- password = Admin@123  (CHANGE AFTER FIRST LOGIN)
-- ----------------------------------------------------------
INSERT INTO users (name, email, password_hash, role, status) VALUES
('Super Admin', 'zabirhassan7890@gmail.com',
 '$2y$10$e0NRzD4i1mE0u1H6sQpY3uS6oQ2lXh5b1c8aXyZ0p9rT2vWqLmN3K',
 'super_admin', 'active');
-- NOTE: regenerate this hash with auth/make_hash.php to guarantee it matches.
