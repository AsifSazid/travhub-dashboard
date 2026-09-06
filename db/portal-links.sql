-- ═══════════════════════════════════════════════════════════════════
-- Master Data — Portal Links
-- ═══════════════════════════════════════════════════════════════════
-- একটা portal_links row = একটা portal (e.g. "Thailand e-Visa")।
-- credentials JSON array এ একাধিক account থাকতে পারে, প্রতিটার নিজস্ব
-- visibility control (is_hide + access_user)।
--
-- credentials array এর প্রতিটা entry এই shape এ থাকে:
-- {
--   "cred_id": "uuid-string",          -- এই credential-এর নিজস্ব id (edit/delete target করতে)
--   "user_name": "...",
--   "password": "<AES-256-GCM encrypted base64>",
--   "is_hide": true|false,
--   "access_user": ["EMP-...", "EMP-..."],  -- is_hide=true হলে শুধু এরা + creator দেখবে
--   "created_by": "EMP-..." অথবা employee sys_id,
--   "created_by_name": "...",          -- display-এর জন্য snapshot, join এড়াতে
--   "created_at": "dd-mm-YYYY HH:mm"
-- }
--
-- Permission logic (backend এ enforce হয়, কোনো column না):
--   credentials array এ unique 'created_by' এর সংখ্যা 1 হলে, সেই একজনই
--   portal edit/delete করতে পারবে। 1 এর বেশি হলে শুধু admin (role='0')।

CREATE TABLE IF NOT EXISTS portal_links (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    uuid         VARCHAR(64)  NOT NULL,
    sys_id       VARCHAR(32)  NOT NULL UNIQUE,
    portal_name  VARCHAR(255) NOT NULL,
    portal_url   VARCHAR(500) DEFAULT NULL,
    portal_type  ENUM('air_ticket','hotel','package','visa','umrah','transport','other') NOT NULL DEFAULT 'other',
    credentials  JSON DEFAULT NULL,
    meta_data    JSON DEFAULT NULL, -- project convention: {created_by_date, updated_by_date[]}
    KEY idx_portal_type (portal_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;