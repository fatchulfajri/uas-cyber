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

INSERT INTO challenges (title, category, description, flag, points) VALUES
('Web SQL Injection', 'easy', 'Temukan celah SQL Injection pada form login untuk mendapatkan flag.', 'CTF{b4s1c_sql_1nject10n}', 100),
('Reverse Engineering', 'easy', 'Analisa binary sederhana untuk menemukan flag tersembunyi.', 'CTF{s1mpl3_r3v3rs3_3lf}', 100),
('Web XSS Attack', 'medium', 'Exploitasi celah XSS untuk mengeksekusi JavaScript dan mendapatkan flag.', 'CTF{st0r3d_x55}', 200),
('Cryptography Challenge', 'medium', 'Dekripsi pesan yang terenkripsi dengan kombinasi Caesar dan Base64.', 'CTF{crypt0_c4es4r_b4se64}', 200),
('Network PCAP Analysis', 'hard', 'Analisa file PCAP untuk menemukan flag yang tersembunyi dalam traffic jaringan.', 'CTF{pc4p_4n4lys1s_e4sy}', 300);

INSERT INTO users (nim, team, role, password)
VALUES ('0000', 'DOSEN', 'dosen', 'admin');

INSERT INTO users (nim, team, role, password)
VALUES ('12345678', 'TIM_A', 'mahasiswa', '123');