-- =============================================================
-- Migration: Thêm cột hồ sơ cá nhân vào bảng `users`
-- Chạy file này trong phpMyAdmin hoặc MySQL CLI
-- =============================================================

ALTER TABLE `users`
    ADD COLUMN `avatar`  VARCHAR(255)  DEFAULT NULL      AFTER `phone`,
    ADD COLUMN `address` VARCHAR(255)  DEFAULT NULL      AFTER `avatar`,
    ADD COLUMN `bio`     TEXT          DEFAULT NULL      AFTER `address`;
