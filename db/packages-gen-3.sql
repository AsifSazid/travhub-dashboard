-- ═══════════════════════════════════════════════════════════════════════════
-- TravHub Global Limited — Complete Database Schema
-- MODULE 01 — All Tables  (CLEAN / v2.1)
-- ───────────────────────────────────────────────────────────────────────────
-- Convention (enforced throughout):
--   • Every entity table has: id (PK), uuid, sys_id, meta_data JSON
--   • meta_data carries full audit trail: created_by, created_at,
--     updated_by, updated_at  — NO separate created_at/updated_at columns
--   • sys_id formats:
--       Flat       → THR-{SHORT}-{YY}-{BLOCK}K{SERIAL}  e.g. THR-PK-26-00K001
--       Hierarchical → {PARENT_SYS_ID}-{SHORT}-{BASE36}  e.g. THR-26-CNT-01-ACT-01
--   • NO foreign key constraints — relationships via sys_id strings only
--   • DECIMAL for all money (never FLOAT)
--   • status ENUM('active','inactive','deleted') on every entity table
--   • Soft-delete via status = 'deleted'
--   • utf8mb4 / utf8mb4_unicode_ci throughout
-- ───────────────────────────────────────────────────────────────────────────
-- DROPPED vs original Gen-2:
--   ✗ users      — exists in main TravHub system
--   ✗ suppliers  — exists as vendors in main TravHub system
--   ✗ travelers  — exists in main TravHub system
-- ───────────────────────────────────────────────────────────────────────────
-- CREATION ORDER (dependency safe):
--   01 countries           (cities embedded as JSON)
--   02 currencies
--   03 fx_rates            (X → BDT only, daily rates)
--   04 hotels
--   05 room_types
--   06 room_rates
--   07 cars
--   08 transport_services
--   09 transport_variants
--   10 activities
--   11 activity_variants
--   12 activity_tags
--   13 components
--   14 component_variants
--   15 packages
--   16 package_days
--   17 package_items
--   18 package_travelers
--   19 quotes
--   20 quote_lines
--   21 client_payments
--   22 supplier_payments
--   23 vouchers
-- ═══════════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- ─────────────────────────────────────────────────────────────────────────
-- 01. COUNTRIES  (cities embedded as JSON column)
--     sys_id format: THR-26-CNT-01
--     city sys_id inside JSON: THR-26-CNT-01-CTS-01
-- ─────────────────────────────────────────────────────────────────────────
-- cities JSON element shape:
-- {
--   "sys_id"         : "THR-26-CNT-01-CTS-01",
--   "name"           : "Bangkok",
--   "country_sys_id" : "THR-26-CNT-01",
--   "type"           : ["tourism","business"],
--   "popularity"     : 4,
--   "cost_level"     : "medium",
--   "visa_ease"      : "easy"
-- }
CREATE TABLE IF NOT EXISTS `countries` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`          VARCHAR(36)     NOT NULL,
    `sys_id`        VARCHAR(30)     NOT NULL,   -- THR-26-CNT-01
    `name`          VARCHAR(100)    NOT NULL,
    `code`          VARCHAR(3)      NOT NULL,   -- ISO 3166-1 alpha-2  e.g. TH
    `currency`      VARCHAR(50)     NOT NULL,   -- e.g. Thai Baht
    `currency_code` VARCHAR(5)      NOT NULL,   -- ISO 4217  e.g. THB
    `default_rate`  DECIMAL(12,4)   NOT NULL DEFAULT 1.0000,  -- 1 local = ? BDT
    `region`        VARCHAR(50)     NOT NULL,
    `for_package`   TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = available for package builder selection',
    `cities`        JSON                NULL,
    `status`        ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`     JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_countries_uuid`   (`uuid`),
    UNIQUE KEY `uq_countries_sys_id` (`sys_id`),
    UNIQUE KEY `uq_countries_code`   (`code`),
    INDEX `idx_countries_region`     (`region`),
    INDEX `idx_countries_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 02. CURRENCIES  (system-wide master list of foreign currencies)
--     sys_id format: THR-CCY-26-00K001
--     BDT is the implicit base — only foreign currencies are stored here
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `currencies` (
    `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`           VARCHAR(36)     NOT NULL,
    `sys_id`         VARCHAR(30)     NOT NULL,   -- THR-CCY-26-00K001
    `currency_code`  VARCHAR(5)      NOT NULL,   -- ISO 4217  e.g. THB
    `name`           VARCHAR(60)     NOT NULL,   -- Thai Baht
    `symbol`         VARCHAR(8)          NULL,   -- ฿
    `decimal_places` TINYINT         NOT NULL DEFAULT 2,
    `status`         ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`      JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_currencies_uuid`          (`uuid`),
    UNIQUE KEY `uq_currencies_sys_id`        (`sys_id`),
    UNIQUE KEY `uq_currencies_currency_code` (`currency_code`),
    INDEX `idx_currencies_status`            (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 03. FX_RATES  (X → BDT daily rates)
--     sys_id format: THR-FXR-26-00K001
--     Always: 1 unit of currency_code = `rate` BDT
--     One row per currency per effective_date
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `fx_rates` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`             VARCHAR(36)     NOT NULL,
    `sys_id`           VARCHAR(30)     NOT NULL,   -- THR-FXR-26-00K001
    `currency_sys_id`  VARCHAR(30)     NOT NULL,   -- currencies.sys_id
    `currency_code`    VARCHAR(5)      NOT NULL,   -- denormalized  e.g. THB
    `rate`             DECIMAL(18,8)   NOT NULL,   -- 1 THB = ? BDT  (mid-market)
    `buffer_pct`       DECIMAL(6,4)    NOT NULL DEFAULT 0.0000,  -- operational margin %
    `effective_date`   DATE            NOT NULL,   -- daily update key
    `source`           ENUM('manual','api') NOT NULL DEFAULT 'manual',
    `status`           ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`        JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_fx_uuid`          (`uuid`),
    UNIQUE KEY `uq_fx_sys_id`        (`sys_id`),
    UNIQUE KEY `uq_fx_ccy_date`      (`currency_code`, `effective_date`),
    INDEX `idx_fx_currency`          (`currency_sys_id`),
    INDEX `idx_fx_date`              (`effective_date`),
    INDEX `idx_fx_status`            (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 04. HOTELS  (concept level — the matchable property)
--     sys_id format: THR-HTL-26-00K001
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `hotels` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`            VARCHAR(36)     NOT NULL,
    `sys_id`          VARCHAR(30)     NOT NULL,   -- THR-HTL-26-00K001
    `country_sys_id`  VARCHAR(30)     NOT NULL,   -- countries.sys_id
    `country_name`    VARCHAR(100)        NULL,   -- denormalized
    `city_sys_id`     VARCHAR(50)         NULL,   -- city sys_id from countries.cities JSON
    `city_name`       VARCHAR(100)        NULL,   -- denormalized
    `vendor_sys_id`   VARCHAR(30)         NULL,   -- vendors.sys_id (from main system)
    `name`            VARCHAR(160)    NOT NULL,
    `search_terms`    TEXT                NULL,   -- aliases for matching
    `star_rating`     TINYINT UNSIGNED    NULL,   -- 1–5
    `address`         VARCHAR(255)        NULL,
    `phone`           VARCHAR(40)         NULL,
    `email`           VARCHAR(120)        NULL,
    `description`     TEXT                NULL,
    `amenities`       JSON                NULL,   -- ["wifi","pool","gym"]
    `images`          JSON                NULL,   -- [{url, caption}]
    `check_in_time`   TIME                NULL,
    `check_out_time`  TIME                NULL,
    `usage_count`     INT UNSIGNED    NOT NULL DEFAULT 0,
    `source`          ENUM('manual','imported','ai_added') NOT NULL DEFAULT 'manual',
    `status`          ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`       JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_hotels_uuid`   (`uuid`),
    UNIQUE KEY `uq_hotels_sys_id` (`sys_id`),
    FULLTEXT KEY `ft_hotels`      (`name`, `search_terms`),
    INDEX `idx_hotels_country`    (`country_sys_id`),
    INDEX `idx_hotels_city`       (`city_sys_id`),
    INDEX `idx_hotels_vendor`     (`vendor_sys_id`),
    INDEX `idx_hotels_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 05. ROOM_TYPES  (durable room product — no price here)
--     sys_id format: THR-HTL-26-00K001-RMT-01
--     hotel_sys_id = hotels.sys_id
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `room_types` (
    `id`                 INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`               VARCHAR(36)     NOT NULL,
    `sys_id`             VARCHAR(50)     NOT NULL,   -- THR-HTL-26-00K001-RMT-01
    `hotel_sys_id`       VARCHAR(30)     NOT NULL,   -- hotels.sys_id
    `hotel_name`         VARCHAR(160)        NULL,   -- denormalized
    `room_name`          VARCHAR(120)    NOT NULL,   -- e.g. Deluxe Twin
    `description`        VARCHAR(400)        NULL,
    `max_adults`         TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `max_children`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `standard_occupancy` TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `bed_config`         VARCHAR(80)         NULL,   -- e.g. 1 King Bed
    `size_sqm`           SMALLINT UNSIGNED   NULL,
    `status`             ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`          JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_room_types_uuid`   (`uuid`),
    UNIQUE KEY `uq_room_types_sys_id` (`sys_id`),
    INDEX `idx_rt_hotel`              (`hotel_sys_id`),
    INDEX `idx_rt_status`             (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 06. ROOM_RATES  (time-bound prices per room type)
--     sys_id format: THR-HTL-26-00K001-RMT-01-RMR-01
--     room_type_sys_id = room_types.sys_id
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `room_rates` (
    `id`                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`                VARCHAR(36)     NOT NULL,
    `sys_id`              VARCHAR(70)     NOT NULL,   -- THR-HTL-26-00K001-RMT-01-RMR-01
    `room_type_sys_id`    VARCHAR(50)     NOT NULL,   -- room_types.sys_id
    `hotel_sys_id`        VARCHAR(30)     NOT NULL,   -- hotels.sys_id (denormalized)
    `vendor_sys_id`       VARCHAR(30)         NULL,   -- vendors.sys_id (from main system)
    `meal_plan`           ENUM('room_only','bb','hb','fb','ai') NOT NULL DEFAULT 'bb',
    `occupancy_basis`     ENUM('per_room','single','double','triple','extra_bed')
                                          NOT NULL DEFAULT 'per_room',
    `valid_from`          DATE            NOT NULL,
    `valid_to`            DATE            NOT NULL,
    `currency_code`       VARCHAR(5)      NOT NULL,
    `net_cost`            DECIMAL(12,2)   NOT NULL,   -- per room/night supplier cost
    `markup_type`         ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    `markup_value`        DECIMAL(12,4)   NOT NULL DEFAULT 0.0000,
    `sell_price`          DECIMAL(12,2)   NOT NULL,   -- reference sell per night
    `tax_basis`           ENUM('inclusive','plus_plus') NOT NULL DEFAULT 'plus_plus',
    `tax_service_pct`     DECIMAL(5,2)        NULL,
    `tax_vat_pct`         DECIMAL(5,2)        NULL,
    `cancellation_policy` TEXT                NULL,
    `status`              ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`           JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_room_rates_uuid`   (`uuid`),
    UNIQUE KEY `uq_room_rates_sys_id` (`sys_id`),
    INDEX `idx_rr_room_type`          (`room_type_sys_id`),
    INDEX `idx_rr_hotel`              (`hotel_sys_id`),
    INDEX `idx_rr_period`             (`room_type_sys_id`, `valid_from`, `valid_to`),
    INDEX `idx_rr_status`             (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 07. CARS  (vehicle catalog, scoped to country)
--     sys_id format: THR-26-CNT-01-CAR-01
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cars` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`            VARCHAR(36)     NOT NULL,
    `sys_id`          VARCHAR(60)     NOT NULL,   -- THR-26-CNT-01-CAR-01
    `country_sys_id`  VARCHAR(30)     NOT NULL,   -- countries.sys_id
    `country_name`    VARCHAR(100)        NULL,   -- denormalized
    `vendor_sys_id`   VARCHAR(30)         NULL,   -- vendors.sys_id (from main system)
    `name`            VARCHAR(150)    NOT NULL,   -- e.g. Toyota Hiace Van
    `type`            ENUM('sedan','van','suv','minibus','microbus','coaster','bus','other')
                                      NOT NULL DEFAULT 'sedan',
    `seats`           TINYINT UNSIGNED NOT NULL DEFAULT 4,
    `has_luggage`     TINYINT(1)      NOT NULL DEFAULT 1,
    `max_luggage`     VARCHAR(20)         NULL,   -- e.g. 20kg
    `status`          ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`       JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cars_uuid`    (`uuid`),
    UNIQUE KEY `uq_cars_sys_id`  (`sys_id`),
    INDEX `idx_cars_country`     (`country_sys_id`),
    INDEX `idx_cars_vendor`      (`vendor_sys_id`),
    INDEX `idx_cars_type`        (`type`),
    INDEX `idx_cars_status`      (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 08. TRANSPORT_SERVICES  (reusable route — concept level)
--     sys_id format: THR-26-CNT-01-TRS-01
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `transport_services` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`             VARCHAR(36)     NOT NULL,
    `sys_id`           VARCHAR(60)     NOT NULL,   -- THR-26-CNT-01-TRS-01
    `country_sys_id`   VARCHAR(30)     NOT NULL,   -- countries.sys_id
    `country_name`     VARCHAR(100)        NULL,   -- denormalized
    `vendor_sys_id`    VARCHAR(30)         NULL,   -- vendors.sys_id (from main system)
    `name`             VARCHAR(160)    NOT NULL,   -- e.g. BKK Airport → Pattaya
    `search_terms`     TEXT                NULL,
    `type`             ENUM('airport_transfer','intercity','ferry','shuttle','car_hire','other')
                                       NOT NULL DEFAULT 'airport_transfer',
    `from_city_sys_id` VARCHAR(50)         NULL,   -- city sys_id from countries JSON
    `from_city_name`   VARCHAR(100)        NULL,   -- denormalized
    `to_city_sys_id`   VARCHAR(50)         NULL,
    `to_city_name`     VARCHAR(100)        NULL,
    `direction`        ENUM('one_way','return') NOT NULL DEFAULT 'one_way',
    `description`      TEXT                NULL,
    `distance_km`      DECIMAL(8,2)        NULL,
    `duration_typical` VARCHAR(40)         NULL,   -- e.g. 2 hours
    `usage_count`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `source`           ENUM('manual','imported','ai_added') NOT NULL DEFAULT 'manual',
    `status`           ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`        JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ts_uuid`   (`uuid`),
    UNIQUE KEY `uq_ts_sys_id` (`sys_id`),
    FULLTEXT KEY `ft_ts`      (`name`, `search_terms`),
    INDEX `idx_ts_country`    (`country_sys_id`),
    INDEX `idx_ts_vendor`     (`vendor_sys_id`),
    INDEX `idx_ts_type`       (`type`),
    INDEX `idx_ts_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 09. TRANSPORT_VARIANTS  (priced vehicle-class options of a route)
--     sys_id format: THR-26-CNT-01-TRS-01-TRV-01
--     service_sys_id = transport_services.sys_id
-- ─────────────────────────────────────────────────────────────────────────
-- extra_charges JSON shape:
-- [{"label":"Night surcharge","amount":200,"currency_code":"THB"}]
CREATE TABLE IF NOT EXISTS `transport_variants` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`             VARCHAR(36)     NOT NULL,
    `sys_id`           VARCHAR(75)     NOT NULL,   -- THR-26-CNT-01-TRS-01-TRV-01
    `service_sys_id`   VARCHAR(60)     NOT NULL,   -- transport_services.sys_id
    `country_sys_id`   VARCHAR(30)     NOT NULL,   -- denormalized
    `car_sys_id`       VARCHAR(60)         NULL,   -- cars.sys_id
    `vendor_sys_id`    VARCHAR(30)         NULL,   -- vendors.sys_id (from main system)
    `variant_name`     VARCHAR(160)    NOT NULL,   -- e.g. Private Van (max 6 pax)
    `vehicle_class`    ENUM('sedan','suv','van','minibus','coach','boat','train','other')
                                       NOT NULL DEFAULT 'van',
    `capacity_max`     INT UNSIGNED    NOT NULL DEFAULT 1,
    `luggage_capacity` VARCHAR(40)         NULL,
    `price_basis`      ENUM('per_vehicle','per_person','per_day','per_km','per_hour')
                                       NOT NULL DEFAULT 'per_vehicle',
    `transfer_type`    ENUM('sic','private') NOT NULL DEFAULT 'private',
    `meet_and_greet`   TINYINT(1)      NOT NULL DEFAULT 0,
    `currency_code`    VARCHAR(5)      NOT NULL,
    `net_cost`         DECIMAL(12,2)   NOT NULL,
    `markup_type`      ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    `markup_value`     DECIMAL(12,4)   NOT NULL DEFAULT 0.0000,
    `sell_price`       DECIMAL(12,2)   NOT NULL,
    `child_price`      DECIMAL(12,2)       NULL,
    `extra_charges`    JSON                NULL,
    `usage_count`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `status`           ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`        JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tv_uuid`   (`uuid`),
    UNIQUE KEY `uq_tv_sys_id` (`sys_id`),
    INDEX `idx_tv_service`    (`service_sys_id`),
    INDEX `idx_tv_country`    (`country_sys_id`),
    INDEX `idx_tv_car`        (`car_sys_id`),
    INDEX `idx_tv_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 10. ACTIVITIES  (concept level — reusable across packages)
--     sys_id format: THR-26-CNT-01-ACT-01
-- ─────────────────────────────────────────────────────────────────────────
-- pickup_from_city / dropoff_city JSON shape:
-- [{"country_sys_id":"THR-26-CNT-01","country_name":"Thailand",
--   "city_sys_id":"THR-26-CNT-01-CTS-01","city_name":"Bangkok"}]
--
-- itineraries / inclusions / exclusions JSON shape:
-- [{"title":"Airport pickup","description":"Meet & greet","icon":"plane-arrival"}]
CREATE TABLE IF NOT EXISTS `activities` (
    `id`                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`                VARCHAR(36)     NOT NULL,
    `sys_id`              VARCHAR(55)     NOT NULL,   -- THR-26-CNT-01-ACT-01
    `country_sys_id`      VARCHAR(30)     NOT NULL,   -- countries.sys_id
    `country_name`        VARCHAR(100)        NULL,   -- denormalized
    `city_sys_id`         VARCHAR(50)         NULL,   -- city sys_id from countries JSON
    `city_name`           VARCHAR(100)        NULL,   -- denormalized
    `vendor_sys_id`       VARCHAR(30)         NULL,   -- vendors.sys_id (from main system)
    `name`                VARCHAR(150)    NOT NULL,
    `search_terms`        TEXT                NULL,
    `type`                ENUM('tour','transfer','both') NOT NULL DEFAULT 'tour',
    `category`            VARCHAR(50)         NULL,   -- sightseeing, cultural, etc.
    `location`            VARCHAR(200)        NULL,   -- venue / landmark
    `short_description`   VARCHAR(400)        NULL,
    `long_description`    TEXT                NULL,
    `highlights`          TEXT                NULL,
    `start_time`          TIME                NULL,
    `end_time`            TIME                NULL,
    `duration_hours`      DECIMAL(5,1)    NOT NULL DEFAULT 0.0,
    `duration_typical`    VARCHAR(40)         NULL,   -- e.g. half day
    `operating_days`      VARCHAR(60)         NULL,
    `season_from`         DATE                NULL,
    `season_to`           DATE                NULL,
    `min_pax`             INT UNSIGNED        NULL,
    `max_pax`             INT UNSIGNED        NULL,
    `age_min`             TINYINT UNSIGNED    NULL,
    `languages`           VARCHAR(120)        NULL,
    `meeting_point`       VARCHAR(200)        NULL,
    `booking_lead_days`   SMALLINT            NULL,
    `cancellation_policy` TEXT                NULL,
    `popularity`          TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `usage_count`         INT UNSIGNED    NOT NULL DEFAULT 0,
    `source`              ENUM('manual','imported','ai_added') NOT NULL DEFAULT 'manual',
    -- JSON columns
    `pickup_from_city`    JSON                NULL,
    `dropoff_city`        JSON                NULL,
    `itineraries`         JSON                NULL,
    `inclusions`          JSON                NULL,
    `exclusions`          JSON                NULL,
    `images`              JSON                NULL,
    -- Package override (copy tied to one package)
    `package_sys_id`      VARCHAR(30)         NULL,
    `is_package_override` TINYINT(1)      NOT NULL DEFAULT 0,
    `status`              ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`           JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_activities_uuid`   (`uuid`),
    UNIQUE KEY `uq_activities_sys_id` (`sys_id`),
    FULLTEXT KEY `ft_activities`      (`name`, `search_terms`),
    INDEX `idx_act_country`           (`country_sys_id`),
    INDEX `idx_act_city`              (`city_sys_id`),
    INDEX `idx_act_vendor`            (`vendor_sys_id`),
    INDEX `idx_act_type`              (`type`),
    INDEX `idx_act_package`           (`package_sys_id`),
    INDEX `idx_act_status`            (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 11. ACTIVITY_VARIANTS  (priced sellable options of an activity)
--     sys_id format: THR-26-CNT-01-ACT-01-AV-01
--     activity_sys_id = activities.sys_id
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `activity_variants` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`             VARCHAR(36)     NOT NULL,
    `sys_id`           VARCHAR(70)     NOT NULL,   -- THR-26-CNT-01-ACT-01-AV-01
    `activity_sys_id`  VARCHAR(55)     NOT NULL,   -- activities.sys_id
    `country_sys_id`   VARCHAR(30)     NOT NULL,   -- denormalized
    `vendor_sys_id`    VARCHAR(30)         NULL,   -- vendors.sys_id (from main system)
    `variant_name`     VARCHAR(160)    NOT NULL,   -- e.g. SIC with Lunch
    `transport_mode`   ENUM('none','sic','sedan','suv','van','minibus','coach','boat')
                                       NOT NULL DEFAULT 'none',
    `meal_breakfast`   TINYINT(1)      NOT NULL DEFAULT 0,
    `meal_lunch`       TINYINT(1)      NOT NULL DEFAULT 0,
    `meal_dinner`      TINYINT(1)      NOT NULL DEFAULT 0,
    `ticket_included`  TINYINT(1)      NOT NULL DEFAULT 1,
    `guide_included`   TINYINT(1)      NOT NULL DEFAULT 0,
    `guide_language`   VARCHAR(60)         NULL,
    `inclusions`       TEXT                NULL,
    `exclusions`       TEXT                NULL,
    `capacity_min`     INT UNSIGNED        NULL,
    `capacity_max`     INT UNSIGNED        NULL,
    `price_basis`      ENUM('per_pax','per_group') NOT NULL DEFAULT 'per_pax',
    `currency_code`    VARCHAR(5)      NOT NULL,
    `net_cost`         DECIMAL(12,2)   NOT NULL,
    `markup_type`      ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    `markup_value`     DECIMAL(12,4)   NOT NULL DEFAULT 0.0000,
    `sell_price`       DECIMAL(12,2)   NOT NULL,
    `child_price`      DECIMAL(12,2)       NULL,
    `usage_count`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `status`           ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`        JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_av_uuid`   (`uuid`),
    UNIQUE KEY `uq_av_sys_id` (`sys_id`),
    INDEX `idx_av_activity`   (`activity_sys_id`),
    INDEX `idx_av_country`    (`country_sys_id`),
    INDEX `idx_av_vendor`     (`vendor_sys_id`),
    INDEX `idx_av_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 12. ACTIVITY_TAGS  (soft tags for filtering — beach, family, honeymoon)
--     sys_id format: THR-26-CNT-01-ACT-01-AT-01  (scoped to activity)
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `activity_tags` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`             VARCHAR(36)     NOT NULL,
    `sys_id`           VARCHAR(80)     NOT NULL,   -- THR-26-CNT-01-ACT-01-AT-01
    `activity_sys_id`  VARCHAR(55)     NOT NULL,   -- activities.sys_id
    `tag`              VARCHAR(50)     NOT NULL,
    `status`           ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`        JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_acttag_uuid`    (`uuid`),
    UNIQUE KEY `uq_acttag_sys_id`  (`sys_id`),
    UNIQUE KEY `uq_acttag_act_tag` (`activity_sys_id`, `tag`),
    INDEX `idx_acttag_activity`    (`activity_sys_id`),
    INDEX `idx_acttag_tag`         (`tag`),
    INDEX `idx_acttag_status`      (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 13. COMPONENTS  (extras: insurance, visa, SIM, tips, fees)
--     sys_id format: THR-CMP-26-00K001
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `components` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`            VARCHAR(36)     NOT NULL,
    `sys_id`          VARCHAR(30)     NOT NULL,   -- THR-CMP-26-00K001
    `vendor_sys_id`   VARCHAR(30)         NULL,   -- vendors.sys_id (from main system)
    `name`            VARCHAR(160)    NOT NULL,
    `search_terms`    TEXT                NULL,
    `category`        ENUM('insurance','visa','guide','sim','tip','porterage',
                           'meal','ticket','fee','misc')
                                      NOT NULL DEFAULT 'misc',
    `description`     TEXT                NULL,
    `usage_count`     INT UNSIGNED    NOT NULL DEFAULT 0,
    `source`          ENUM('manual','imported','ai_added') NOT NULL DEFAULT 'manual',
    `status`          ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`       JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_components_uuid`   (`uuid`),
    UNIQUE KEY `uq_components_sys_id` (`sys_id`),
    FULLTEXT KEY `ft_components`      (`name`, `search_terms`),
    INDEX `idx_comp_vendor`           (`vendor_sys_id`),
    INDEX `idx_comp_category`         (`category`),
    INDEX `idx_comp_status`           (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 14. COMPONENT_VARIANTS  (priced tier of a component)
--     sys_id format: THR-CMP-26-00K001-CMV-01
--     component_sys_id = components.sys_id
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `component_variants` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`             VARCHAR(36)     NOT NULL,
    `sys_id`           VARCHAR(45)     NOT NULL,   -- THR-CMP-26-00K001-CMV-01
    `component_sys_id` VARCHAR(30)     NOT NULL,   -- components.sys_id
    `vendor_sys_id`    VARCHAR(30)         NULL,   -- vendors.sys_id (from main system)
    `variant_name`     VARCHAR(160)    NOT NULL,   -- e.g. Basic Insurance
    `unit_basis`       ENUM('per_pax','per_group','per_day','flat')
                                       NOT NULL DEFAULT 'per_pax',
    `currency_code`    VARCHAR(5)      NOT NULL,
    `net_cost`         DECIMAL(12,2)   NOT NULL,
    `markup_type`      ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    `markup_value`     DECIMAL(12,4)   NOT NULL DEFAULT 0.0000,
    `sell_price`       DECIMAL(12,2)   NOT NULL,
    `attributes`       JSON                NULL,
    `status`           ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`        JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cv_uuid`   (`uuid`),
    UNIQUE KEY `uq_cv_sys_id` (`sys_id`),
    INDEX `idx_cv_component`  (`component_sys_id`),
    INDEX `idx_cv_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 15. PACKAGES  (trip header / spine root)
--     sys_id format: THR-PK-26-00K001
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `packages` (
    `id`                   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`                 VARCHAR(36)     NOT NULL,
    `sys_id`               VARCHAR(30)     NOT NULL,   -- THR-PK-26-00K001
    -- Basic info
    `booking_ref`          VARCHAR(30)         NULL,   -- set on confirm e.g. TH-2026-0001
    `title`                VARCHAR(200)    NOT NULL,
    `description`          TEXT                NULL,
    `package_type`         ENUM('group','fit','corporate','factory_tour','custom','umrah')
                                           NOT NULL DEFAULT 'custom',
    -- Client (from main TravHub system)
    `client_sys_id`        VARCHAR(30)         NULL,   -- clients.sys_id
    `client_name`          VARCHAR(160)        NULL,   -- denormalized
    -- Pax
    `adults`               INT UNSIGNED    NOT NULL DEFAULT 1,
    `children`             INT UNSIGNED    NOT NULL DEFAULT 0,
    `infants`              INT UNSIGNED    NOT NULL DEFAULT 0,
    -- Dates
    `start_date`           DATE                NULL,
    `end_date`             DATE                NULL,
    `duration`             SMALLINT UNSIGNED   NULL,   -- number of nights
    -- Currency
    `sell_currency_code`   VARCHAR(5)      NOT NULL DEFAULT 'BDT',
    `sell_currency_title`  VARCHAR(60)         NULL,
    `sell_currency_symbol` VARCHAR(8)          NULL,
    -- Pricing snapshot
    `overall_price`        DECIMAL(14,2)       NULL,
    `air_ticket_details`   TEXT                NULL,
    -- JSON blobs (builder wizard data)
    `countries`            JSON                NULL,
    `cities`               JSON                NULL,
    `hotels`               JSON                NULL,
    `pack_itenaries`       JSON                NULL,
    `pack_price`           JSON                NULL,
    `pack_inclusions`      JSON                NULL,
    `pack_exclusions`      JSON                NULL,
    `no_of_pax`            JSON                NULL,
    -- Images
    `image`                VARCHAR(255)        NULL,
    `cover_image`          VARCHAR(255)        NULL,
    -- Lifecycle
    `progress_step`        TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `completion_status`    ENUM('draft','saved','quoted','confirmed','in_progress',
                                'completed','cancelled') NOT NULL DEFAULT 'draft',
    `active_quote_sys_id`  VARCHAR(30)         NULL,   -- quotes.sys_id
    `rating`               TINYINT UNSIGNED    NULL DEFAULT 0,
    `full_description`     TEXT                NULL,   -- AI generated
    `assigned_to_sys_id`   VARCHAR(30)         NULL,   -- users.sys_id (from main system)
    `version`              INT UNSIGNED    NOT NULL DEFAULT 1,
    `notes`                TEXT                NULL,
    `status`               ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`            JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_packages_uuid`        (`uuid`),
    UNIQUE KEY `uq_packages_sys_id`      (`sys_id`),
    UNIQUE KEY `uq_packages_booking_ref` (`booking_ref`),
    INDEX `idx_pkg_client`               (`client_sys_id`),
    INDEX `idx_pkg_completion`           (`completion_status`),
    INDEX `idx_pkg_assigned`             (`assigned_to_sys_id`),
    INDEX `idx_pkg_status`               (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 16. PACKAGE_DAYS  (one row per day of a trip)
--     sys_id format: THR-PK-26-00K001-PD-01
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `package_days` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`                      VARCHAR(36)     NOT NULL,
    `sys_id`                    VARCHAR(50)     NOT NULL,   -- THR-PK-26-00K001-PD-01
    `package_sys_id`            VARCHAR(30)     NOT NULL,   -- packages.sys_id
    `day_number`                SMALLINT UNSIGNED NOT NULL,
    `day_date`                  DATE                NULL,
    `country_sys_id`            VARCHAR(30)         NULL,
    `country_name`              VARCHAR(100)        NULL,
    `city_sys_id`               VARCHAR(50)         NULL,
    `city_name`                 VARCHAR(100)        NULL,
    `title`                     VARCHAR(200)        NULL,
    `meal_breakfast`            TINYINT(1)      NOT NULL DEFAULT 0,
    `meal_lunch`                TINYINT(1)      NOT NULL DEFAULT 0,
    `meal_dinner`               TINYINT(1)      NOT NULL DEFAULT 0,
    `accommodation_item_sys_id` VARCHAR(60)         NULL,   -- package_items.sys_id
    `chat_input`                TEXT                NULL,   -- raw operator text (AI source)
    `notes`                     TEXT                NULL,
    `status`                    ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`                 JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pd_uuid`    (`uuid`),
    UNIQUE KEY `uq_pd_sys_id`  (`sys_id`),
    UNIQUE KEY `uq_pd_pkg_day` (`package_sys_id`, `day_number`),
    INDEX `idx_pd_package`     (`package_sys_id`),
    INDEX `idx_pd_country`     (`country_sys_id`),
    INDEX `idx_pd_status`      (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 17. PACKAGE_ITEMS  (unified timeline — every line of every day)
--     sys_id format: THR-PK-26-00K001-PI-001
-- ─────────────────────────────────────────────────────────────────────────
-- detail JSON shape examples:
-- Hotel:     {"nights":2,"meal_plan":"bb","room_label":"Deluxe Twin"}
-- Activity:  {"guide_lang":"EN","meal_lunch":true}
-- Transport: {"route":"BKK→Pattaya","transfer_type":"private"}
-- Flight:    {"carrier":"BG","flight_no":"BG-001","pnr":"XYZ123",
--             "origin":"DAC","dest":"BKK","depart":"2026-01-10 22:00"}
CREATE TABLE IF NOT EXISTS `package_items` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`              VARCHAR(36)     NOT NULL,
    `sys_id`            VARCHAR(60)     NOT NULL,   -- THR-PK-26-00K001-PI-001
    `package_sys_id`    VARCHAR(30)     NOT NULL,   -- packages.sys_id
    `day_sys_id`        VARCHAR(50)     NOT NULL,   -- package_days.sys_id
    `sequence`          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `start_time`        TIME                NULL,
    `item_type`         ENUM('activity','transport','hotel','flight','component')
                                        NOT NULL,
    `source_table`      VARCHAR(50)         NULL,   -- e.g. activity_variants
    `source_sys_id`     VARCHAR(75)         NULL,   -- the variant/rate sys_id
    `title_snapshot`    VARCHAR(200)    NOT NULL,   -- frozen display title
    `detail`            JSON                NULL,
    `qty`               INT UNSIGNED    NOT NULL DEFAULT 1,
    `nights`            SMALLINT UNSIGNED   NULL,   -- hotels only
    `currency_code`     VARCHAR(5)      NOT NULL,
    `net_cost`          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `sell_price`        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `purpose`           ENUM('business','leisure','logistics') NOT NULL DEFAULT 'leisure',
    `op_status`         ENUM('pending','requested','confirmed','paid',
                             'voucher_issued','cancelled') NOT NULL DEFAULT 'pending',
    `confirmation_no`   VARCHAR(60)         NULL,
    `vendor_sys_id`     VARCHAR(30)         NULL,   -- vendors.sys_id (from main system)
    `vendor_due_date`   DATE                NULL,
    `notes`             TEXT                NULL,
    `status`            ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`         JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pi_uuid`   (`uuid`),
    UNIQUE KEY `uq_pi_sys_id` (`sys_id`),
    INDEX `idx_pi_package`    (`package_sys_id`),
    INDEX `idx_pi_day`        (`day_sys_id`),
    INDEX `idx_pi_day_seq`    (`day_sys_id`, `sequence`),
    INDEX `idx_pi_type`       (`item_type`),
    INDEX `idx_pi_op_status`  (`op_status`),
    INDEX `idx_pi_vendor`     (`vendor_sys_id`),
    INDEX `idx_pi_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 18. PACKAGE_TRAVELERS  (join: traveler ↔ trip with rooming + visa)
--     sys_id format: THR-PK-26-00K001-PT-01
--     traveler_sys_id = travelers.sys_id  (from main TravHub system)
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `package_travelers` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`             VARCHAR(36)     NOT NULL,
    `sys_id`           VARCHAR(55)     NOT NULL,   -- THR-PK-26-00K001-PT-01
    `package_sys_id`   VARCHAR(30)     NOT NULL,   -- packages.sys_id
    `traveler_sys_id`  VARCHAR(30)     NOT NULL,   -- travelers.sys_id (main system)
    `lead_pax`         TINYINT(1)      NOT NULL DEFAULT 0,
    `pax_type`         ENUM('adult','child','infant') NOT NULL DEFAULT 'adult',
    `room_assignment`  VARCHAR(40)         NULL,
    `segment_coverage` VARCHAR(120)        NULL,
    `visa_status`      ENUM('not_required','pending','applied','approved','refused')
                                       NOT NULL DEFAULT 'not_required',
    `ticket_ref`       VARCHAR(40)         NULL,
    `notes`            TEXT                NULL,
    `status`           ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`        JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pt_uuid`     (`uuid`),
    UNIQUE KEY `uq_pt_sys_id`   (`sys_id`),
    UNIQUE KEY `uq_pt_pkg_trav` (`package_sys_id`, `traveler_sys_id`),
    INDEX `idx_pt_package`      (`package_sys_id`),
    INDEX `idx_pt_traveler`     (`traveler_sys_id`),
    INDEX `idx_pt_status`       (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 19. QUOTES  (versioned frozen price offer)
--     sys_id format: THR-QT-26-00K001
-- ─────────────────────────────────────────────────────────────────────────
-- fx_snapshot JSON shape:
-- {"THB":{"rate":3.21,"buffer_pct":2.5,"rate_with_buffer":3.29}}
CREATE TABLE IF NOT EXISTS `quotes` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`                  VARCHAR(36)     NOT NULL,
    `sys_id`                VARCHAR(30)     NOT NULL,   -- THR-QT-26-00K001
    `package_sys_id`        VARCHAR(30)     NOT NULL,   -- packages.sys_id
    `version`               INT UNSIGNED    NOT NULL DEFAULT 1,
    `quote_currency_code`   VARCHAR(5)      NOT NULL,
    `quote_currency_title`  VARCHAR(60)         NULL,
    `fx_snapshot`           JSON                NULL,   -- rates used per currency
    `subtotal_cost`         DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `subtotal_sell`         DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `markup_type`           ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    `markup_value`          DECIMAL(12,4)   NOT NULL DEFAULT 0.0000,
    `markup_amount`         DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `service_fee`           DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `discount`              DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `grand_total`           DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `per_person`            DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `rounding_rule`         VARCHAR(40)         NULL,
    `margin_amount`         DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `margin_pct`            DECIMAL(6,2)        NULL,
    `valid_until`           DATE                NULL,
    `quote_status`          ENUM('draft','sent','accepted','expired','superseded')
                                            NOT NULL DEFAULT 'draft',
    `status`                ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`             JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_quotes_uuid`    (`uuid`),
    UNIQUE KEY `uq_quotes_sys_id`  (`sys_id`),
    UNIQUE KEY `uq_quotes_pkg_ver` (`package_sys_id`, `version`),
    INDEX `idx_q_package`          (`package_sys_id`),
    INDEX `idx_q_quote_status`     (`quote_status`),
    INDEX `idx_q_status`           (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 20. QUOTE_LINES  (frozen per-item snapshot at quote time)
--     sys_id format: THR-QT-26-00K001-QL-01
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `quote_lines` (
    `id`                   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`                 VARCHAR(36)     NOT NULL,
    `sys_id`               VARCHAR(50)     NOT NULL,   -- THR-QT-26-00K001-QL-01
    `quote_sys_id`         VARCHAR(30)     NOT NULL,   -- quotes.sys_id
    `item_sys_id`          VARCHAR(60)         NULL,   -- package_items.sys_id
    `description`          VARCHAR(200)    NOT NULL,
    `qty`                  INT UNSIGNED    NOT NULL DEFAULT 1,
    `source_currency_code` VARCHAR(5)      NOT NULL,
    `source_cost`          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `source_sell`          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `fx_rate`              DECIMAL(18,8)   NOT NULL DEFAULT 1.00000000,
    `cost_quote_ccy`       DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `sell_quote_ccy`       DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    `status`               ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`            JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ql_uuid`   (`uuid`),
    UNIQUE KEY `uq_ql_sys_id` (`sys_id`),
    INDEX `idx_ql_quote`      (`quote_sys_id`),
    INDEX `idx_ql_item`       (`item_sys_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 21. CLIENT_PAYMENTS  (money IN from client)
--     sys_id format: THR-CP-26-00K001
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `client_payments` (
    `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`           VARCHAR(36)     NOT NULL,
    `sys_id`         VARCHAR(30)     NOT NULL,   -- THR-CP-26-00K001
    `package_sys_id` VARCHAR(30)     NOT NULL,   -- packages.sys_id
    `quote_sys_id`   VARCHAR(30)         NULL,   -- quotes.sys_id
    `type`           ENUM('deposit','installment','balance','refund')
                                     NOT NULL DEFAULT 'deposit',
    `amount`         DECIMAL(14,2)   NOT NULL,
    `currency_code`  VARCHAR(5)      NOT NULL,
    `method`         ENUM('bank','card','cash','cheque','online','bkash','nagad')
                                     NOT NULL DEFAULT 'bank',
    `paid_on`        DATE            NOT NULL,
    `reference`      VARCHAR(80)         NULL,
    `notes`          TEXT                NULL,
    `status`         ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`      JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cp_uuid`   (`uuid`),
    UNIQUE KEY `uq_cp_sys_id` (`sys_id`),
    INDEX `idx_cp_package`    (`package_sys_id`),
    INDEX `idx_cp_quote`      (`quote_sys_id`),
    INDEX `idx_cp_paid_on`    (`paid_on`),
    INDEX `idx_cp_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 22. SUPPLIER_PAYMENTS  (money OUT to vendors)
--     sys_id format: THR-SP-26-00K001
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `supplier_payments` (
    `id`                 INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`               VARCHAR(36)     NOT NULL,
    `sys_id`             VARCHAR(30)     NOT NULL,   -- THR-SP-26-00K001
    `package_sys_id`     VARCHAR(30)     NOT NULL,   -- packages.sys_id
    `vendor_sys_id`      VARCHAR(30)     NOT NULL,   -- vendors.sys_id (main system)
    `vendor_name`        VARCHAR(150)        NULL,   -- denormalized
    `item_sys_id`        VARCHAR(60)         NULL,   -- package_items.sys_id
    `amount`             DECIMAL(14,2)   NOT NULL,
    `currency_code`      VARCHAR(5)      NOT NULL,
    `fx_rate_used`       DECIMAL(18,8)       NULL,   -- actual X→BDT rate used
    `amount_bdt`         DECIMAL(14,2)       NULL,   -- realized cost in BDT
    `method`             ENUM('bank','card','cash','cheque','online','bkash','nagad')
                                         NOT NULL DEFAULT 'bank',
    `paid_on`            DATE            NOT NULL,
    `reference`          VARCHAR(80)         NULL,
    `notes`              TEXT                NULL,
    `status`             ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`          JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sp_uuid`   (`uuid`),
    UNIQUE KEY `uq_sp_sys_id` (`sys_id`),
    INDEX `idx_sp_package`    (`package_sys_id`),
    INDEX `idx_sp_vendor`     (`vendor_sys_id`),
    INDEX `idx_sp_item`       (`item_sys_id`),
    INDEX `idx_sp_paid_on`    (`paid_on`),
    INDEX `idx_sp_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────
-- 23. VOUCHERS  (issued service documents)
--     sys_id format: THR-VCH-26-00K001
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `vouchers` (
    `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`           VARCHAR(36)     NOT NULL,
    `sys_id`         VARCHAR(30)     NOT NULL,   -- THR-VCH-26-00K001
    `package_sys_id` VARCHAR(30)     NOT NULL,   -- packages.sys_id
    `item_sys_id`    VARCHAR(60)         NULL,   -- package_items.sys_id
    `type`           ENUM('hotel','transport','activity','component','combined','flight')
                                     NOT NULL,
    `voucher_no`     VARCHAR(40)     NOT NULL,   -- human-facing number
    `issued_on`      DATE                NULL,
    `file_path`      VARCHAR(255)        NULL,   -- generated PDF path
    `voucher_status` ENUM('draft','issued','cancelled') NOT NULL DEFAULT 'draft',
    `status`         ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
    `meta_data`      JSON                NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_vouchers_uuid`       (`uuid`),
    UNIQUE KEY `uq_vouchers_sys_id`     (`sys_id`),
    UNIQUE KEY `uq_vouchers_voucher_no` (`voucher_no`),
    INDEX `idx_v_package`               (`package_sys_id`),
    INDEX `idx_v_item`                  (`item_sys_id`),
    INDEX `idx_v_voucher_status`        (`voucher_status`),
    INDEX `idx_v_status`                (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;

-- ═══════════════════════════════════════════════════════════════════════════
-- END OF MODULE 01 — Schema  (Clean v2.1)
-- ═══════════════════════════════════════════════════════════════════════════
-- Changes from original Gen-2:
--   • DROPPED: users, suppliers, travelers
--   • DROPPED: created_at, updated_at from all tables (meta_data handles audit)
--   • SIMPLIFIED: fx_rates → X→BDT only; removed to_currency_* columns;
--                 renamed amount_in_sell_ccy → amount_bdt in supplier_payments
--   • FIXED: activity_tags now has uuid, sys_id, status, meta_data
--   • FIXED: quote_lines now has meta_data
--   • RENAMED: all supplier_sys_id → vendor_sys_id to match main TravHub system
--   • UPDATED: supplier_payments.supplier_* → vendor_* to match
--   • UPDATED: sys_id short codes aligned to MODULE_02 flat format
--              e.g. THR-CCY-26-00K001, THR-HTL-26-00K001, THR-QT-26-00K001
-- ═══════════════════════════════════════════════════════════════════════════