-- ========================================
-- Development Reset Script
-- ========================================
-- 개발 환경에서만 사용하세요.
-- 기존 테이블을 외래키 역순으로 삭제한 뒤 schema.sql을 다시 실행합니다.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS playlist_tracks;
DROP TABLE IF EXISTS calendar_entries;
DROP TABLE IF EXISTS palette_logs;
DROP TABLE IF EXISTS playlist_likes;
DROP TABLE IF EXISTS playlists;
DROP TABLE IF EXISTS tracks;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;
