-- init_open_encrypt_pg.sql

-- ------------------------------
-- Table: login_info
-- ------------------------------
CREATE TABLE IF NOT EXISTS login_info (
  username VARCHAR(14),
  password VARCHAR(60),
  token VARCHAR(32)
);

-- ------------------------------
-- Table: messages
-- ------------------------------
CREATE TABLE IF NOT EXISTS messages (
  id SERIAL PRIMARY KEY,
  sender VARCHAR(14),
  recipient VARCHAR(14),
  message TEXT,
  method VARCHAR(16),
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------
-- Table: public_keys
-- ------------------------------
CREATE TABLE IF NOT EXISTS public_keys (
  username VARCHAR(14),
  public_key TEXT,
  method VARCHAR(16)
);
