-- USE cyber;

-- -- CREATE TABLE IF NOT EXISTS users (
-- --  id INT AUTO_INCREMENT PRIMARY KEY,
-- --  username VARCHAR(50),
-- --  password VARCHAR(50)
-- -- );

-- CREATE TABLE users (
--   id INT AUTO_INCREMENT PRIMARY KEY,
--   nim VARCHAR(20),
--   team VARCHAR(50),
--   role ENUM('mahasiswa','dosen'),
--   password VARCHAR(50)
-- );

-- INSERT INTO users (username, password)
-- VALUES ('admin','admin123');

-- CREATE TABLE IF NOT EXISTS comments (
--  comment TEXT
-- );

-- CREATE TABLE flags (
--  id INT AUTO_INCREMENT PRIMARY KEY,
--  flag VARCHAR(100)
-- );

-- INSERT INTO flags VALUES
-- (1,'CTF{basic_sql_injection}');

-- CREATE TABLE students (
--     nim VARCHAR(20) PRIMARY KEY,
--     name VARCHAR(100)
-- );

-- CREATE TABLE scores (
--     nim VARCHAR(20),
--     score INT DEFAULT 0,
--     grade CHAR(1),
--     PRIMARY KEY (nim)
-- );

-- -- CREATE TABLE submissions (
-- --     id INT AUTO_INCREMENT PRIMARY KEY,
-- --     nim VARCHAR(20),
-- --     challenge VARCHAR(20),
-- --     time DATETIME,
-- --     score INT
-- -- );

-- CREATE TABLE submissions (
--   id INT AUTO_INCREMENT PRIMARY KEY,
--   user_id INT,
--   challenge VARCHAR(50),
--   score INT,
--   submit_time DATETIME
-- );

-- INSERT INTO users (nim, team, role, password)
-- VALUES ('0000', 'DOSEN', 'dosen', 'admin');

-- INSERT INTO users (nim, team, role, password)
-- VALUES ('12345678', 'TIM_A', 'mahasiswa', '123');

DROP DATABASE IF EXISTS cyber;
CREATE DATABASE cyber;
USE cyber;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nim VARCHAR(20) UNIQUE,
  nama VARCHAR(100),
  team VARCHAR(10),
  role ENUM('mahasiswa','dosen') DEFAULT 'mahasiswa',
  password VARCHAR(255)
);

CREATE TABLE submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  challenge_id INT,
  score INT,
  submit_time DATETIME,
  UNIQUE KEY unique_submission (user_id, challenge_id)
);

CREATE TABLE challenges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100),
  category ENUM('easy', 'medium', 'hard'),
  description TEXT,
  flag VARCHAR(100),
  points INT,
  solves INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tracks ephemeral per-user challenge containers spawned via spawn.php.
-- One live instance per (user, challenge); cleaned up on correct flag or TTL expiry.
CREATE TABLE active_instances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  challenge_id INT NOT NULL,
  container_id VARCHAR(64) NOT NULL,
  container_name VARCHAR(100) NOT NULL,
  port INT NOT NULL,
  status ENUM('running','stopped') DEFAULT 'running',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  UNIQUE KEY uniq_user_challenge (user_id, challenge_id)
);

INSERT INTO challenges (title, category, description, flag, points) VALUES
('Web SQL Injection', 'easy', 'Temukan celah SQL Injection pada form login untuk mendapatkan flag.', 'CTF{sql_1nj3ct10n_byp4ss_m4st3r}', 100),
('Reverse Engineering', 'easy', 'Analisa binary sederhana untuk menemukan flag tersembunyi.', 'CTF{d3c0mp1l3_b1n4ry_4nd_c0nqu3r}', 100),
('Web XSS Attack', 'medium', 'Exploitasi celah XSS untuk mengeksekusi JavaScript dan mendapatkan flag.', 'CTF{p0pp1ng_x55_4l3rt_b0x3s}', 150),
('Cryptography Challenge', 'medium', 'Dekripsi pesan yang terenkripsi dengan kombinasi Caesar dan Base64.', 'CTF{br34k1ng_p0st_qu4ntum_c1ph3r}', 150),
('Network PCAP Analysis', 'hard', 'Analisa file PCAP untuk menemukan flag yang tersembunyi dalam traffic jaringan.', 'CTF{f0ll0w_th3_tcp_str34m_sh4rk}', 200);

INSERT INTO users (nim, nama, team, role, password)
VALUES ('0000', 'otsukare', 'DOSEN', 'dosen', 'admin');