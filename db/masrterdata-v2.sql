-- ═══════════════════════════════════════════════════════════════
-- TravHub — Master Data Schema
-- Tables: countries, activities, cars
-- Run ONCE on a fresh database before seeding.
-- ═══════════════════════════════════════════════════════════════


-- ── Countries ────────────────────────────────────────────────────────
-- Cities are NOT a separate table.
-- They live in the `cities` JSON column of this table.
-- Each city object inside that JSON carries its own sys_id.

CREATE TABLE IF NOT EXISTS `countries` (
    `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `uuid`          VARCHAR(36)    NOT NULL,
    `sys_id`        VARCHAR(30)    NOT NULL,   -- THR-26-CNT-01
    `name`          VARCHAR(100)   NOT NULL,
    `code`          VARCHAR(3)     NOT NULL,   -- ISO 3166-1 alpha-2  e.g. TH
    `currency`      VARCHAR(50)    NOT NULL,   -- e.g. Thai Baht
    `currency_code` VARCHAR(5)     NOT NULL,   -- ISO 4217  e.g. THB
    `default_rate`  DECIMAL(12,4)  NOT NULL DEFAULT 1.0000,  -- 1 local = ? BDT
    `region`        VARCHAR(50)    NOT NULL,   -- e.g. Southeast Asia
    `cities`        JSON               NULL,   -- array of city objects (see below)
    `status`        ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`     JSON               NULL,
    `created_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_countries_uuid`   (`uuid`),
    UNIQUE KEY `uq_countries_sys_id` (`sys_id`),
    UNIQUE KEY `uq_countries_code`   (`code`),
    INDEX `idx_countries_region`     (`region`),
    INDEX `idx_countries_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- cities JSON column structure (one element):
-- {
--   "id"             : "THR-26-CNT-01-CTS-01",   ← city sys_id
--   "name"           : "Bangkok",
--   "country_id"     : 1,                         ← countries.id (DB int)
--   "country_sys_id" : "THR-26-CNT-01",
--   "type"           : ["tourism","business"],
--   "popularity"     : 4,
--   "cost_level"     : "medium",
--   "visa_ease"      : "easy"
-- }


-- ── Cars ─────────────────────────────────────────────────────────────
-- One row per car model, scoped to a country.
-- A "Toyota Hiace Van" in Thailand is a separate record from one in Saudi Arabia.
-- sys_id format: THR-26-CNT-01-CAR-01

CREATE TABLE IF NOT EXISTS `cars` (
    `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `uuid`           VARCHAR(36)    NOT NULL,
    `sys_id`         VARCHAR(60)    NOT NULL,   -- THR-26-CNT-01-CAR-01
    `country_sys_id` VARCHAR(30)    NOT NULL,   -- THR-26-CNT-01
    `name`           VARCHAR(150)   NOT NULL,   -- e.g. Toyota Hiace Van
    `type`           ENUM('sedan','van','suv','minibus','microbus','coaster','bus','other')
                                    NOT NULL DEFAULT 'sedan',
    `seats`          TINYINT UNSIGNED NOT NULL DEFAULT 4,
    `has_luggage`    TINYINT(1)     NOT NULL DEFAULT 1,
    `max_luggage`    VARCHAR(20)        NULL,   -- e.g. 20kg
    `status`         ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`      JSON               NULL,
    `created_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cars_uuid`    (`uuid`),
    UNIQUE KEY `uq_cars_sys_id`  (`sys_id`),
    INDEX `idx_cars_country`     (`country_sys_id`),
    INDEX `idx_cars_type`        (`type`),
    INDEX `idx_cars_status`      (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── Activities ───────────────────────────────────────────────────────
-- One row per activity. Can be a Tour, a Transfer, or both.
-- Linked to country via sys_id string — no FK constraint.
-- sys_id format: THR-26-CNT-01-ACT-01

CREATE TABLE IF NOT EXISTS `activities` (
    `id`               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `uuid`             VARCHAR(36)    NOT NULL,
    `sys_id`           VARCHAR(55)    NOT NULL,   -- THR-26-CNT-01-ACT-01
    `country_sys_id`   VARCHAR(30)    NOT NULL,   -- THR-26-CNT-01
    `name`             VARCHAR(150)   NOT NULL,
    `type`             ENUM('tour','transfer','both') NOT NULL DEFAULT 'tour',
    `location`         VARCHAR(200)       NULL,   -- venue / landmark / airport name
    `start_time`       TIME               NULL,
    `end_time`         TIME               NULL,
    `duration_hours`   DECIMAL(5,1)   NOT NULL DEFAULT 0.0,
    `popularity`       TINYINT UNSIGNED NOT NULL DEFAULT 3,  -- 1–5

    -- ── JSON columns ──────────────────────────────────────────────
    `pickup_from_city` JSON               NULL,
    `dropoff_city`     JSON               NULL,
    `itineraries`      JSON               NULL,
    `inclusions`       JSON               NULL,
    `exclusions`       JSON               NULL,
    `transfers`        JSON               NULL,
    -- ─────────────────────────────────────────────────────────────

    `status`           ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`        JSON               NULL,
    `created_at`       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_activities_uuid`   (`uuid`),
    UNIQUE KEY `uq_activities_sys_id` (`sys_id`),
    INDEX `idx_act_country_sys_id`    (`country_sys_id`),
    INDEX `idx_act_type`              (`type`),
    INDEX `idx_act_status`            (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ═══════════════════════════════════════════════════════════════
-- JSON column reference comments
-- ═══════════════════════════════════════════════════════════════

-- pickup_from_city / dropoff_city — array of city objects:
-- [
--   {
--     "country_sys_id" : "THR-26-CNT-01",
--     "country_name"   : "Thailand",
--     "city_sys_id"    : "THR-26-CNT-01-CTS-01",
--     "city_name"      : "Bangkok"
--   }
-- ]

-- itineraries / inclusions / exclusions — array of items:
-- [
--   {
--     "title"       : "Airport pickup",
--     "description" : "Meet & greet at arrival hall",
--     "icon"        : "plane-arrival"    ← optional Font Awesome icon name
--   }
-- ]

-- transfers — array of transfer objects:
-- [
--   {
--     "title"  : "Airport → Hotel",
--     "type"   : "sic",                  ← sic | private
--     "notes"  : "...",                  ← optional
--     "pricing": [
--       {
--         "car_sys_id"  : "THR-26-CNT-01-CAR-01",
--         "car_name"    : "Toyota Hiace Van",    ← denormalized for display stability
--         "price_adult" : 500,    ← populated when type = sic  (child/adult per pax)
--         "price_child" : 300,    ← populated when type = sic
--         "price_full"  : null    ← populated when type = private (whole vehicle)
--       }
--     ]
--   }
-- ]