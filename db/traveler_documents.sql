-- ============================================================================
-- TravHub Smart Upload v3 — Database Migration
-- ============================================================================
-- Run this ONCE on travhub_dev via phpMyAdmin.
--
-- Creates:
--   1. New columns on `travelers` for living summary + dirty flag
--   2. New table `traveler_documents` for per-document storage
--   3. New table `doc_type_registry` (config) mapping doc types to your
--      9 existing SMB folders
--   4. Seed data for 18 doc types
--
-- Does NOT touch:
--   - Existing travelers columns (passport_info, nid_info, meta_data, etc.)
--   - Any existing tables
--   - Any existing data
--
-- Safe to run on production after testing on dev.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- STEP 1: Extend `travelers` table with summary system columns
-- ----------------------------------------------------------------------------

ALTER TABLE `travelers`
  ADD COLUMN `summary` LONGTEXT NULL
    COMMENT 'Current AI-generated traveler summary (JSON object with summary_count, date, summary_text, meta_data)'
    AFTER `meta_data`,

  ADD COLUMN `previous_summary` LONGTEXT NULL
    COMMENT 'Array of historical summaries, newest first. Entries >6 months keep only headline.'
    AFTER `summary`,

  ADD COLUMN `summary_dirty` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Set to 1 when documents change; regenerate-summary resets to 0'
    AFTER `previous_summary`,

  ADD COLUMN `summary_pending_trigger` VARCHAR(255) NULL
    COMMENT 'Human-readable description of what triggered the pending regen (e.g., "Uploaded passport")'
    AFTER `summary_dirty`,

  ADD COLUMN `summary_updated_at` DATETIME NULL
    COMMENT 'Last summary regeneration timestamp'
    AFTER `summary_pending_trigger`,

  ADD INDEX `idx_summary_dirty` (`summary_dirty`);


-- ----------------------------------------------------------------------------
-- STEP 2: Create `traveler_documents` table
-- One row per logical document. Multi-page passport = ONE row with N pages
-- in the `pages` JSON array.
-- ----------------------------------------------------------------------------

CREATE TABLE `traveler_documents` (
  `id`                  BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`                VARCHAR(36)         NOT NULL,
  `sys_id`              VARCHAR(16)         NOT NULL          COMMENT 'TD-XXXXXX format',
  `traveler_id`         BIGINT(20) UNSIGNED NOT NULL,
  `traveler_sys_id`     VARCHAR(16)         NOT NULL          COMMENT 'Denormalized for fast lookups',

  -- ── Classification ──────────────────────────────────────────────────────
  `doc_type`            VARCHAR(64)         NOT NULL          COMMENT 'passport, nid, visa, air_ticket, etc',
  `doc_subtype`         VARCHAR(64)         NULL              COMMENT 'bio_page, nid_front, tourist_visa, etc',
  `doc_number`          VARCHAR(128)        NULL              COMMENT 'Passport no, NID no, PNR, visa no, etc',
  `confidence`          DECIMAL(4,3)        NULL              COMMENT '0.000-1.000 from Gemini',
  `classification_mode` ENUM('auto','manual','overridden') NOT NULL DEFAULT 'auto',

  -- Passport-specific (NULL for non-passport doc types)
  `passport_status`     ENUM('current','previous','unknown') NULL
                        COMMENT 'Only set when doc_type=passport',

  -- ── Source file (original upload — for audit trail only) ────────────────
  `original_filename`   VARCHAR(255)        NOT NULL,
  `original_ext`        VARCHAR(10)         NOT NULL          COMMENT 'pdf, jpg, png, webp',
  `file_hash`           CHAR(64)            NOT NULL          COMMENT 'SHA-256 of original upload',
  `file_size`           BIGINT(20) UNSIGNED NULL,
  `mime_type`           VARCHAR(100)        NULL,

  -- ── Stored images (always JPG in SMB; no PDFs survive) ──────────────────
  -- pages JSON shape:
  -- [
  --   {"page_no":1,"filename":"passport_BX0123456_AsifMSazid_A3F7K2_p1.jpg",
  --    "phash":"a1b2c3d4e5f60718","page_type":"bio_page","country":null,
  --    "summary":"Bio page with applicant photo and personal details"},
  --   {"page_no":2,"filename":"...p2.jpg","phash":"...","page_type":"visa_stamp",
  --    "country":"UAE","summary":"UAE tourist visa stamp dated 2024-03-15"},
  --   ...
  -- ]
  `pages`               LONGTEXT            NULL              COMMENT 'JSON array of page metadata',
  `page_count`          INT UNSIGNED        NOT NULL DEFAULT 1,
  `smb_folder`          VARCHAR(64)         NOT NULL          COMMENT 'One of 9 fixed folders from store.php',
  `smb_path`            TEXT                NULL              COMMENT 'Root SMB folder path',
  `server_path`         TEXT                NULL              COMMENT 'Root local folder path',

  -- ── AI-generated content (document-level, NOT traveler-level) ───────────
  `suggested_name`      VARCHAR(255)        NULL              COMMENT 'Gemini original suggestion',
  `stored_basename`     VARCHAR(255)        NOT NULL          COMMENT 'Final filename stem (without _pN.jpg)',
  `summary`             TEXT                NULL              COMMENT 'Document-level summary, 2-3 sentences',
  `ocr_text`            LONGTEXT            NULL              COMMENT 'Full OCR for FULLTEXT search',
  `language`            VARCHAR(20)         NULL              COMMENT 'en, bn, mixed',

  -- ── Structured data ─────────────────────────────────────────────────────
  -- doc_data: per-doc-type structured JSON following the schema in
  -- doc_type_registry.extraction_schema_json. Different shape per doc_type.
  `doc_data`            LONGTEXT            NULL              COMMENT 'Per-doc-type structured extraction (JSON)',
  `key_fields`          LONGTEXT            NULL              COMMENT 'Free-form fallback for doc types without a schema (JSON)',

  -- ── Hot fields (indexed, pulled from doc_data on commit) ────────────────
  `country`                   VARCHAR(50)   NULL              COMMENT 'Indexed: country this doc relates to (visas, hotels, etc)',
  `validity_from`             DATE          NULL              COMMENT 'Indexed: doc validity start',
  `validity_to`               DATE          NULL              COMMENT 'Indexed: doc validity end',
  `linked_passport_number`    VARCHAR(50)   NULL              COMMENT 'Indexed: links visas to their passport',
  `issue_date`                DATE          NULL,
  `expiry_date`               DATE          NULL              COMMENT 'For expiry alerts',
  `expiry_alert_days`         INT           NOT NULL DEFAULT 30,

  -- ── Verification + lifecycle ────────────────────────────────────────────
  `verification_status` ENUM('unverified','verified','disputed') NOT NULL DEFAULT 'unverified',
  `verified_at`         DATETIME            NULL,
  `verified_by`         VARCHAR(100)        NULL,
  `supersedes_id`       BIGINT(20) UNSIGNED NULL              COMMENT 'Self-FK: this doc replaces an older one',
  `is_primary`          TINYINT(1)          NOT NULL DEFAULT 0,
  `status`              ENUM('pending','active','expired','archived','deleted') NOT NULL DEFAULT 'active',

  -- ── Audit ───────────────────────────────────────────────────────────────
  `meta_data`           LONGTEXT            NULL              COMMENT 'created_by_date + updated_by_date[] (your established pattern)',
  `created_at`          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- ── Keys + indexes ──────────────────────────────────────────────────────
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_uuid`           (`uuid`),
  UNIQUE KEY `uniq_sys_id`         (`sys_id`),
  UNIQUE KEY `uniq_traveler_hash`  (`traveler_id`, `file_hash`)
                                   COMMENT 'Layer 1 dedup: exact byte-level',

  KEY `idx_traveler`         (`traveler_id`),
  KEY `idx_traveler_sys`     (`traveler_sys_id`),
  KEY `idx_doc_type`         (`doc_type`),
  KEY `idx_doc_number`       (`doc_number`),
  KEY `idx_passport_status`  (`traveler_id`, `doc_type`, `passport_status`),
  KEY `idx_doc_lookup`       (`traveler_id`, `doc_type`, `doc_number`, `status`)
                             COMMENT 'Layer 2 dedup: identity-based merge lookup',
  KEY `idx_country`          (`country`, `status`),
  KEY `idx_validity`         (`validity_to`, `status`),
  KEY `idx_expiry`           (`expiry_date`, `status`),
  KEY `idx_linked_passport`  (`linked_passport_number`),
  KEY `idx_supersedes`       (`supersedes_id`),
  KEY `idx_status`           (`status`),

  FULLTEXT KEY `ft_ocr_summary` (`ocr_text`, `summary`),

  CONSTRAINT `fk_td_traveler` FOREIGN KEY (`traveler_id`)
    REFERENCES `travelers` (`id`) ON DELETE CASCADE,

  CONSTRAINT `fk_td_supersedes` FOREIGN KEY (`supersedes_id`)
    REFERENCES `traveler_documents` (`id`) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------------------------------------------------------
-- STEP 3: Create `doc_type_registry` — config table mapping doc types
-- to your 9 existing SMB folders + extraction behavior
-- ----------------------------------------------------------------------------
-- Your 9 fixed SMB folders (from store.php):
--   all_documents, passport_identity, personal_documents, professional_documents,
--   financial_documents, travel_history, photos_signature, countries_documents, nid

CREATE TABLE `doc_type_registry` (
  `id`                      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `doc_type`                VARCHAR(64)   NOT NULL,
  `display_name`            VARCHAR(128)  NOT NULL,
  `smb_folder`              VARCHAR(64)   NOT NULL          COMMENT 'Must be one of the 9 fixed folders',

  -- Behavior flags
  `tracks_expiry`           TINYINT(1)    NOT NULL DEFAULT 0,
  `tracks_validity`         TINYINT(1)    NOT NULL DEFAULT 0  COMMENT 'Populate validity_from/to from doc_data',
  `has_structured_schema`   TINYINT(1)    NOT NULL DEFAULT 0  COMMENT '1 = use doc_data; 0 = use key_fields',
  `updates_traveler_column` VARCHAR(64)   NULL                COMMENT 'e.g. passport_info, nid_info; NULL for no mirror',

  -- Display
  `display_order`           INT           NOT NULL DEFAULT 0,
  `description`             TEXT          NULL,
  `is_active`               TINYINT(1)    NOT NULL DEFAULT 1,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_doc_type` (`doc_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ----------------------------------------------------------------------------
-- STEP 4: Seed `doc_type_registry` with 18 doc types
-- ----------------------------------------------------------------------------

INSERT INTO `doc_type_registry`
  (`doc_type`, `display_name`, `smb_folder`, `tracks_expiry`, `tracks_validity`,
   `has_structured_schema`, `updates_traveler_column`, `display_order`)
VALUES
-- ── Identity (structured schemas, mirror to travelers table) ───────────────
('passport',              'Passport',                'passport_identity',      1, 0, 1, 'passport_info', 1),
('nid',                   'National ID',             'nid',                    0, 0, 1, 'nid_info',      2),

-- ── Visa & travel (structured schemas) ─────────────────────────────────────
('visa',                  'Visa',                    'countries_documents',    1, 1, 1, NULL,            3),
('visa_stamp',            'Visa Stamp / Entry-Exit', 'countries_documents',    0, 0, 0, NULL,            4),
('air_ticket',            'Air Ticket',              'travel_history',         0, 0, 1, NULL,            5),
('hotel_voucher',         'Hotel Voucher',           'travel_history',         0, 0, 1, NULL,            6),
('invitation_letter',     'Invitation Letter',       'travel_history',         0, 0, 0, NULL,            7),

-- ── Financial ──────────────────────────────────────────────────────────────
('bank_statement',        'Bank Statement',          'financial_documents',    0, 0, 1, NULL,            8),
('sponsor_letter',        'Sponsor Letter',          'financial_documents',    0, 0, 0, NULL,            9),

-- ── Professional ───────────────────────────────────────────────────────────
('employment_letter',     'Employment Letter',       'professional_documents', 0, 0, 0, NULL,           10),
('education_certificate', 'Education Certificate',   'professional_documents', 0, 0, 0, NULL,           11),

-- ── Personal ───────────────────────────────────────────────────────────────
('medical_report',        'Medical Report',          'personal_documents',     0, 0, 0, NULL,           12),
('vaccination_card',      'Vaccination Card',        'personal_documents',     0, 0, 0, NULL,           13),
('marriage_certificate',  'Marriage Certificate',    'personal_documents',     0, 0, 0, NULL,           14),
('birth_certificate',     'Birth Certificate',       'personal_documents',     0, 0, 0, NULL,           15),

-- ── Photos / signatures ────────────────────────────────────────────────────
('photo',                 'Photograph',              'photos_signature',       0, 0, 0, NULL,           16),
('signature',             'Signature',               'photos_signature',       0, 0, 0, NULL,           17),

-- ── Catch-all ──────────────────────────────────────────────────────────────
('other',                 'Other Document',          'all_documents',          0, 0, 0, NULL,           99);


-- ============================================================================
-- VERIFICATION QUERIES (run these after migration to confirm success)
-- ============================================================================

-- Confirm travelers got the new columns:
-- SHOW COLUMNS FROM `travelers` LIKE 'summary%';
-- Expected: summary, summary_dirty, summary_pending_trigger, summary_updated_at + previous_summary

-- Confirm tables exist:
-- SHOW TABLES LIKE 'traveler_documents';
-- SHOW TABLES LIKE 'doc_type_registry';

-- Confirm registry seeded:
-- SELECT doc_type, smb_folder, has_structured_schema FROM doc_type_registry ORDER BY display_order;
-- Expected: 18 rows

-- Confirm FK works:
-- SHOW CREATE TABLE traveler_documents;
-- Expected: fk_td_traveler ON DELETE CASCADE

-- ============================================================================
-- ROLLBACK (only if migration must be undone)
-- ============================================================================
-- DROP TABLE IF EXISTS `traveler_documents`;
-- DROP TABLE IF EXISTS `doc_type_registry`;
-- ALTER TABLE `travelers`
--   DROP COLUMN `summary`,
--   DROP COLUMN `previous_summary`,
--   DROP COLUMN `summary_dirty`,
--   DROP COLUMN `summary_pending_trigger`,
--   DROP COLUMN `summary_updated_at`;