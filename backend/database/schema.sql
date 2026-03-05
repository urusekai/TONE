CREATE TABLE IF NOT EXISTS users (
  user_uuid CHAR(36) NOT NULL,
  id VARCHAR(50) DEFAULT NULL,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) DEFAULT NULL,
  nickname VARCHAR(5) NOT NULL,
  profile_color CHAR(7) NOT NULL DEFAULT '#B7AEA6',
  provider VARCHAR(20) NOT NULL DEFAULT 'local',
  provider_id VARCHAR(255) DEFAULT NULL,
  membership_plan ENUM('free', 'basic', 'pro') NOT NULL DEFAULT 'free',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_uuid),
  UNIQUE KEY uq_users_id (id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_provider_provider_id (provider, provider_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mood VARCHAR(30) NOT NULL,
  label VARCHAR(50) NOT NULL,
  tag1 VARCHAR(30) NOT NULL,
  tag2 VARCHAR(30) NOT NULL,
  tag3 VARCHAR(30) NOT NULL,
  grad_c1 CHAR(7) NOT NULL,
  grad_c2 CHAR(7) NOT NULL,
  grad_c3 CHAR(7) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_mood (mood)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (mood, label, tag1, tag2, tag3, grad_c1, grad_c2, grad_c3)
VALUES
  ('chill', 'Chill', 'Lo-fi', '재즈', '어쿠스틱', '#eaf4f4', '#f2f2ee', '#dceae8'),
  ('bright', 'Bright', '청량 K-POP', '썸', '드라이브 팝', '#cde7f0', '#a8e6cf', '#fff3b0'),
  ('energetic', 'Energetic', '댄스', 'EDM', '아이돌', '#ff5e5b', '#ff8e53', '#fdc830'),
  ('emotional', 'Emotional', '발라드', '슬픈 힙합', 'OST', '#c08497', '#8e7dbe', '#d6cadd'),
  ('groovy', 'Groovy', 'R&B', '시티팝', '네오소울', '#3ba7a0', '#1f6f78', '#274c77'),
  ('intense', 'Intense', '락', 'Drill', '다크 EDM', '#3a0ca3', '#8d314a', '#2b2d42')
ON DUPLICATE KEY UPDATE
  label = VALUES(label),
  tag1 = VALUES(tag1),
  tag2 = VALUES(tag2),
  tag3 = VALUES(tag3),
  grad_c1 = VALUES(grad_c1),
  grad_c2 = VALUES(grad_c2),
  grad_c3 = VALUES(grad_c3);
