-- SBO Nexus: Event Management module
-- Database schema (MySQL / MariaDB)

CREATE DATABASE IF NOT EXISTS sbo_nexus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sbo_nexus;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role ENUM('student','officer','admin') NOT NULL DEFAULT 'student', -- 'admin' is the internal database value for the Adviser role
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  google_id VARCHAR(255) NULL UNIQUE,
  student_id VARCHAR(50) NULL UNIQUE,
  year_level ENUM('1st Year','2nd Year','3rd Year','4th Year') NULL,
  course VARCHAR(100) NULL,
  photo VARCHAR(255) NULL,
  position_title VARCHAR(100) NULL,
  bio VARCHAR(500) NULL,
  email_notifications TINYINT(1) NOT NULL DEFAULT 1,
  password_hash VARCHAR(255) NOT NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0, -- set when the Adviser resets the password
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  event_date DATE NOT NULL,
  event_end_date DATE NULL,
  event_time TIME NULL,
  event_end_time TIME NULL,
  venue VARCHAR(150) NOT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  geofence_radius SMALLINT UNSIGNED NOT NULL DEFAULT 100,
  attendance_status ENUM('auto','open','paused','closed') NOT NULL DEFAULT 'auto',
  category ENUM('Academic','Sports','Cultural','Outreach','Organizational') NOT NULL DEFAULT 'Academic',
  capacity INT UNSIGNED NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  notified_at DATETIME NULL,
  reminder_sent_at DATETIME NULL,
  created_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registrations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  attended TINYINT(1) NOT NULL DEFAULT 0,
  checkin_method ENUM('manual','geofence') NULL,
  checked_in_at TIMESTAMP NULL,
  checkin_lat DECIMAL(10,7) NULL,
  checkin_lng DECIMAL(10,7) NULL,
  checkin_distance_m INT NULL,
  checkin_accuracy_m INT NULL,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_registration (event_id, user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_feedback (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  message VARCHAR(500) NOT NULL,
  rating TINYINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_photos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  is_cover TINYINT(1) NOT NULL DEFAULT 0,
  uploaded_by INT UNSIGNED NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;



-- ===== Event committees (organizers assigned to a committee) =====
CREATE TABLE IF NOT EXISTS event_organizers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  added_by INT UNSIGNED NOT NULL,
  committee VARCHAR(100) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_event_organizer (event_id, user_id)
) ENGINE=InnoDB;

-- ===== Per-event accomplishment report (officer-editable narrative) =====
CREATE TABLE IF NOT EXISTS event_reports (
  event_id INT UNSIGNED NOT NULL PRIMARY KEY,
  narrative TEXT NULL,
  recommendations TEXT NULL,
  updated_by INT UNSIGNED NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== Editable accomplishment report (one saved document per officer and event) =====
-- event_id is NULL for the officer's all-events summary report.
CREATE TABLE IF NOT EXISTS report_documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  event_id INT UNSIGNED NULL,
  template VARCHAR(30) NOT NULL DEFAULT 'standard',
  content MEDIUMTEXT NOT NULL,
  updated_at TIMESTAMP NULL,
  KEY idx_report_doc_owner (user_id, event_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Activity log: who signed in and when (viewed by the Adviser) =====
CREATE TABLE IF NOT EXISTS login_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  email VARCHAR(150) NOT NULL DEFAULT '',
  event VARCHAR(20) NOT NULL,
  method VARCHAR(20) NOT NULL DEFAULT '',
  detail VARCHAR(120) NOT NULL DEFAULT '',
  ip VARCHAR(45) NOT NULL DEFAULT '',
  user_agent VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  KEY idx_log_created (created_at),
  KEY idx_log_user (user_id, created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Student Concerns & Feedback module =====
CREATE TABLE IF NOT EXISTS concerns (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  subject VARCHAR(150) NOT NULL,
  category ENUM('Academic','Facility','Event','Administrative','Other') NOT NULL DEFAULT 'Other',
  message TEXT NOT NULL,
  is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('open','in_review','resolved') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS concern_replies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (concern_id) REFERENCES concerns(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== Polls & Surveys module =====
CREATE TABLE IF NOT EXISTS polls (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(255) NOT NULL,
  description VARCHAR(500) NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS poll_options (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  poll_id INT UNSIGNED NOT NULL,
  option_text VARCHAR(150) NOT NULL,
  image_path VARCHAR(255) NULL,
  FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS poll_votes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  poll_id INT UNSIGNED NOT NULL,
  option_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
  FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_vote (poll_id, user_id)
) ENGINE=InnoDB;

-- Sample/demo records are intentionally omitted from the public schema.

-- Sample/demo records are intentionally omitted from the public schema.

-- Sample/demo records are intentionally omitted from the public schema.
