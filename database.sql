CREATE DATABASE IF NOT EXISTS fitness_club CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fitness_club;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  login VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NULL,
  role ENUM('client','trainer','admin') NOT NULL DEFAULT 'client',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE trainers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  specialty VARCHAR(100) NOT NULL DEFAULT '',
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE membership_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  visits INT NULL COMMENT 'NULL = беҳад',
  duration_days SMALLINT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE memberships (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type_id INT NOT NULL,
  visits_total INT NULL,
  visits_left INT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('active','frozen','expired','finished') NOT NULL DEFAULT 'active',
  frozen_at DATE NULL,
  freeze_days_used SMALLINT NOT NULL DEFAULT 0,
  price_paid DECIMAL(10,2) NOT NULL DEFAULT 0,
  sold_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id, status),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (type_id) REFERENCES membership_types(id),
  FOREIGN KEY (sold_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  trainer_id INT NOT NULL,
  starts_at DATETIME NOT NULL,
  duration_min SMALLINT NOT NULL DEFAULT 60,
  capacity SMALLINT NOT NULL,
  room VARCHAR(40) NOT NULL DEFAULT 'Толори 1',
  cancelled TINYINT(1) NOT NULL DEFAULT 0,
  INDEX (starts_at),
  FOREIGN KEY (trainer_id) REFERENCES trainers(id)
) ENGINE=InnoDB;

CREATE TABLE class_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('booked','waitlist','cancelled') NOT NULL DEFAULT 'booked',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_class_user (class_id, user_id),
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE visits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  membership_id INT NOT NULL,
  class_id INT NULL,
  checked_by INT NOT NULL,
  visited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (membership_id) REFERENCES memberships(id),
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
  FOREIGN KEY (checked_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Парол барои ҳама: 123456 (дар аввалин вуруд худкор ҳэш мешавад)
INSERT INTO users (full_name, login, password_hash, phone, role) VALUES
('Маъмур Саидаҳмад', 'admin', 'NEW', '+992900000001', 'admin'),
('Мураббӣ Нодир', 'trainer1', 'NEW', '+992900000002', 'trainer'),
('Мураббӣ Мадина', 'trainer2', 'NEW', '+992900000003', 'trainer'),
('Алӣ Каримов', 'client1', 'NEW', '+992900000004', 'client'),
('Зарина Расулова', 'client2', 'NEW', '+992900000005', 'client');

INSERT INTO trainers (user_id, specialty) VALUES (2, 'Кроссфит, бокс'), (3, 'Йога, пилатес, зумба');

INSERT INTO membership_types (name, visits, duration_days, price) VALUES
('Санҷишӣ (1 ташриф)', 1, 7, 30),
('8 ташриф / 30 рӯз', 8, 30, 250),
('12 ташриф / 45 рӯз', 12, 45, 340),
('Беҳад / 30 рӯз', NULL, 30, 400),
('Беҳад / 90 рӯз', NULL, 90, 1000);

INSERT INTO memberships (user_id, type_id, visits_total, visits_left, start_date, end_date, price_paid, sold_by) VALUES
(4, 4, NULL, NULL, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 400, 1),
(5, 2, 8, 8, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 250, 1);

INSERT INTO classes (title, trainer_id, starts_at, duration_min, capacity, room) VALUES
('Йога', 2, TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00'), 60, 3, 'Толори 1'),
('Кроссфит', 1, TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '18:00:00'), 60, 2, 'Толори 2'),
('Зумба', 2, TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 2 DAY), '19:00:00'), 50, 15, 'Толори 1'),
('Бокс', 1, TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 3 DAY), '17:30:00'), 60, 8, 'Толори 2'),
('Пилатес', 2, TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 4 DAY), '09:00:00'), 55, 10, 'Толори 1'),
('Кроссфит', 1, TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 5 DAY), '10:00:00'), 60, 10, 'Толори 2');
