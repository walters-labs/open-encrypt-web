-- init_open_encrypt.sql
-- Create all tables for the Open Encrypt project

-- ------------------------------
-- Table: login_info
-- ------------------------------
CREATE TABLE IF NOT EXISTS `login_info` (
  `username` char(14) DEFAULT NULL,
  `password` char(60) DEFAULT NULL,
  `token` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------
-- Table: messages
-- ------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `from` char(14) DEFAULT NULL,
  `to` char(14) DEFAULT NULL,
  `message` mediumtext,
  `method` varchar(16) DEFAULT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ------------------------------
-- Table: public_keys
-- ------------------------------
CREATE TABLE IF NOT EXISTS `public_keys` (
  `username` char(14) DEFAULT NULL,
  `public_key` mediumtext,
  `method` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
