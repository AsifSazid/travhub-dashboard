-- ═══════════════════════════════════════════════════════════════
-- TravHub — Master Data Schema   (Phase 1)
-- Tables: countries, activities
-- Run this ONCE before the seed scripts.
-- ═══════════════════════════════════════════════════════════════

-- ── Countries ────────────────────────────────────────────────────────
-- Cities are NOT a separate table.
-- They live in the `cities` JSON column of this table.
-- Each city object inside that JSON carries its own sys_id.

CREATE TABLE IF NOT EXISTS `countries` (
    `id`            INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `uuid`          VARCHAR(36)         NOT NULL,
    `sys_id`        VARCHAR(30)         NOT NULL,   -- THR-26-CNT-01
    `name`          VARCHAR(100)        NOT NULL,
    `code`          VARCHAR(3)          NOT NULL,   -- ISO 3166-1 alpha-2  e.g. AD
    `currency`      VARCHAR(50)         NOT NULL,   -- e.g. Euro
    `currency_code` VARCHAR(5)          NOT NULL,   -- ISO 4217  e.g. EUR
    `default_rate`  DECIMAL(12, 4)      NOT NULL DEFAULT 1.0000,  -- 1 local = ? BDT
    `region`        VARCHAR(50)         NOT NULL,   -- e.g. Europe
    `cities`        JSON                    NULL,   -- array of city objects (see below)
    `status`        ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`     JSON                    NULL,
    `created_at`    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_countries_uuid`   (`uuid`),
    UNIQUE KEY `uq_countries_sys_id` (`sys_id`),
    UNIQUE KEY `uq_countries_code`   (`code`),
    INDEX        `idx_countries_region` (`region`),
    INDEX        `idx_countries_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- cities JSON column structure (one element):
-- {
--   "id"             : "THR-26-CNT-01-CTS-01",   ← city sys_id
--   "name"           : "Andorra la Vella",
--   "country_id"     : 1,                         ← countries.id (DB int)
--   "country_sys_id" : "THR-26-CNT-01",
--   "type"           : ["tourism","business"],
--   "popularity"     : 3,
--   "cost_level"     : "high",
--   "visa_ease"      : "medium"
-- }


-- ── Activities ───────────────────────────────────────────────────────
-- One row per activity.
-- Linked to city and country via sys_id strings — no FK constraint.

CREATE TABLE IF NOT EXISTS `activities` (
    `id`              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `uuid`            VARCHAR(36)         NOT NULL,
    `sys_id`          VARCHAR(55)         NOT NULL,   -- THR-26-CNT-01-CTS-01-ACT-01
    `city_sys_id`     VARCHAR(40)         NOT NULL,   -- THR-26-CNT-01-CTS-01
    `country_sys_id`  VARCHAR(30)         NOT NULL,   -- THR-26-CNT-01  (for fast filtering)
    `name`            VARCHAR(150)        NOT NULL,
    `type`            VARCHAR(30)         NOT NULL DEFAULT 'tourism',
    -- types found in data: cultural, tourism, adventure, shopping, religious,
    --                      sports, nightlife, education, business, conference
    `price_range`     ENUM('free','low','medium','high') NOT NULL DEFAULT 'medium',
    `duration_hours`  DECIMAL(5, 1)       NOT NULL DEFAULT 1.0,
    `popularity`      TINYINT UNSIGNED    NOT NULL DEFAULT 3,  -- 2–5 in data
    `status`          ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`       JSON                    NULL,
    `created_at`      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_activities_uuid`   (`uuid`),
    UNIQUE KEY `uq_activities_sys_id` (`sys_id`),
    INDEX `idx_act_city_sys_id`    (`city_sys_id`),
    INDEX `idx_act_country_sys_id` (`country_sys_id`),
    INDEX `idx_act_type`           (`type`),
    INDEX `idx_act_status`         (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;