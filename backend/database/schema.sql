CREATE TABLE IF NOT EXISTS users (
  user_uuid CHAR(36) NOT NULL,
  id VARCHAR(50) DEFAULT NULL,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) DEFAULT NULL,
  nickname VARCHAR(5) NOT NULL,
  profile_color CHAR(7) NOT NULL DEFAULT '#B7AEA6',
  provider VARCHAR(20) NOT NULL DEFAULT 'local',
  provider_id VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_uuid),
  UNIQUE KEY uq_users_id (id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_provider_provider_id (provider, provider_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
