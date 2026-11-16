-- Fitness Club Management System
-- MySQL 8.0+
-- Charset: utf8mb4

CREATE DATABASE IF NOT EXISTS fitness_club_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE fitness_club_db;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'trainer', 'client') NOT NULL DEFAULT 'client',
    status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE clients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    birth_date DATE NULL,
    gender ENUM('male', 'female', 'other', 'unspecified') NOT NULL DEFAULT 'unspecified',
    emergency_contact VARCHAR(150) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clients_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE trainers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    specialization VARCHAR(150) NOT NULL,
    experience_years TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bio TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_trainers_experience CHECK (experience_years <= 60),
    CONSTRAINT fk_trainers_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE membership_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    duration_days SMALLINT UNSIGNED NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_plans_duration CHECK (duration_days > 0),
    CONSTRAINT chk_plans_price CHECK (price >= 0)
) ENGINE=InnoDB;

CREATE TABLE memberships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'active', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_memberships_dates CHECK (end_date >= start_date),
    CONSTRAINT fk_memberships_client
      FOREIGN KEY (client_id) REFERENCES clients(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_memberships_plan
      FOREIGN KEY (plan_id) REFERENCES membership_plans(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_memberships_client_status (client_id, status),
    INDEX idx_memberships_dates (start_date, end_date)
) ENGINE=InnoDB;

CREATE TABLE services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    default_duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_services_duration CHECK (default_duration_minutes > 0),
    CONSTRAINT chk_services_price CHECK (price >= 0)
) ENGINE=InnoDB;

CREATE TABLE training_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id BIGINT UNSIGNED NOT NULL,
    trainer_id BIGINT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    capacity SMALLINT UNSIGNED NOT NULL,
    status ENUM('scheduled', 'cancelled', 'completed') NOT NULL DEFAULT 'scheduled',
    notes VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_sessions_time CHECK (ends_at > starts_at),
    CONSTRAINT chk_sessions_capacity CHECK (capacity > 0),
    CONSTRAINT fk_sessions_service
      FOREIGN KEY (service_id) REFERENCES services(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sessions_trainer
      FOREIGN KEY (trainer_id) REFERENCES trainers(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_sessions_starts_status (starts_at, status),
    INDEX idx_sessions_trainer_time (trainer_id, starts_at, ends_at)
) ENGINE=InnoDB;

CREATE TABLE bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,
    session_id BIGINT UNSIGNED NOT NULL,
    status ENUM('booked', 'cancelled', 'attended', 'no_show') NOT NULL DEFAULT 'booked',
    booked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelled_at DATETIME NULL,
    CONSTRAINT uq_bookings_client_session UNIQUE (client_id, session_id),
    CONSTRAINT fk_bookings_client
      FOREIGN KEY (client_id) REFERENCES clients(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_session
      FOREIGN KEY (session_id) REFERENCES training_sessions(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_bookings_session_status (session_id, status)
) ENGINE=InnoDB;

CREATE TABLE attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL UNIQUE,
    marked_by BIGINT UNSIGNED NOT NULL,
    attendance_status ENUM('present', 'absent', 'late') NOT NULL,
    marked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes VARCHAR(500) NULL,
    CONSTRAINT fk_attendance_booking
      FOREIGN KEY (booking_id) REFERENCES bookings(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_attendance_user
      FOREIGN KEY (marked_by) REFERENCES users(id)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'online') NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    transaction_reference VARCHAR(120) NULL UNIQUE,
    paid_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_payments_amount CHECK (amount > 0),
    CONSTRAINT fk_payments_client
      FOREIGN KEY (client_id) REFERENCES clients(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_payments_membership
      FOREIGN KEY (membership_id) REFERENCES memberships(id)
      ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_payments_client_status (client_id, payment_status),
    INDEX idx_payments_paid_at (paid_at)
) ENGINE=InnoDB;
