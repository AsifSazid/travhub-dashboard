-- ============================================================
-- TABLE: sm_posts (Social Media Posts)
-- sys_id format: THR-A26-SM-0000
-- ============================================================
CREATE TABLE IF NOT EXISTS `sm_posts` (
  `id`           bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`         varchar(36)         NOT NULL,
  `sys_id`       varchar(20)         NOT NULL,
  `platform`     varchar(30)         NOT NULL,         -- facebook|instagram|linkedin|twitter|tiktok
  `tone`         varchar(30)         DEFAULT NULL,
  `language`     varchar(20)         DEFAULT NULL,
  `content_size` varchar(20)         DEFAULT NULL,     -- short|medium|long|custom
  `word_limit`   int(11)             DEFAULT 150,
  `temperature`  decimal(3,1)        DEFAULT 0.7,
  `raw_input`    text                DEFAULT NULL,     -- original user prompt
  `post_text`    longtext            DEFAULT NULL,     -- polished post
  `hook`         text                DEFAULT NULL,
  `cta`          text                DEFAULT NULL,
  `hashtags`     longtext            DEFAULT NULL      -- JSON array
                 CHECK (json_valid(`hashtags`)),
  `keywords`     longtext            DEFAULT NULL      -- JSON array
                 CHECK (json_valid(`keywords`)),
  `tips`         longtext            DEFAULT NULL      -- JSON array
                 CHECK (json_valid(`tips`)),
  `has_image`    tinyint(1)          DEFAULT 0,
  `image_url`    text                DEFAULT NULL,     -- relative path or NULL
  `image_prompt` text                DEFAULT NULL,
  `image_ratio`  varchar(10)         DEFAULT NULL,     -- 1:1|4:5|9:16|16:9|3:2
  `status`       varchar(20)         DEFAULT 'draft',  -- draft|published|archived
  `meta_data`    longtext            DEFAULT NULL
                 CHECK (json_valid(`meta_data`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid`   (`uuid`),
  UNIQUE KEY `sys_id` (`sys_id`),
  KEY `platform`  (`platform`),
  KEY `status`    (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;