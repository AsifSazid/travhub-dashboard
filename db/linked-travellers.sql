-- ═══════════════════════════════════════════════════════════════════
-- Linked Travellers — family relation links + named travel groups
-- ═══════════════════════════════════════════════════════════════════

-- ১. Pairwise family-relation link (spouse/parent/child/sibling/other/group_member)
-- একটা row-ই দুই দিক থেকে দেখানো হয় — duplicate row বসানো হয় না।
-- 'parent' relation_type-এ traveler_a_id সবসময় parent, traveler_b_id child
-- ধরা হয়; UI/API সেই direction অনুযায়ী সঠিক label দেখায়
-- (traveler_a এর প্রোফাইলে "Child: b", traveler_b এর প্রোফাইলে "Parent: a")।
-- 'group_member' — Travel Group-এ কেউ join করলে group-এর প্রতিটা existing
-- member-এর সাথে automatically এই relation_type দিয়ে link তৈরি হয়।
CREATE TABLE IF NOT EXISTS traveler_links (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    uuid           VARCHAR(64)  NOT NULL,
    sys_id         VARCHAR(32)  NOT NULL UNIQUE,
    traveler_a_id  VARCHAR(32)  NOT NULL,
    traveler_b_id  VARCHAR(32)  NOT NULL,
    relation_type  ENUM('spouse','parent','sibling','other','group_member') NOT NULL,
    meta_data      JSON DEFAULT NULL, -- project convention: {created_by_date, updated_by_date[]}
    KEY idx_traveler_a (traveler_a_id),
    KEY idx_traveler_b (traveler_b_id),
    -- একই জোড়ার মধ্যে duplicate link আটকাতে (দুই দিক আলাদা করে না ধরে,
    -- অ্যাপ-লেভেলে normalize করে ছোট sys_id-টাই সবসময় a হিসেবে বসানো হবে)
    UNIQUE KEY uniq_pair (traveler_a_id, traveler_b_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ২. Named travel group (e.g. "Dubai Trip 2026") — many-to-many membership
CREATE TABLE IF NOT EXISTS traveler_groups (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    uuid         VARCHAR(64)  NOT NULL,
    sys_id       VARCHAR(32)  NOT NULL UNIQUE,
    group_name   VARCHAR(255) NOT NULL,
    description  TEXT         DEFAULT NULL,
    meta_data    JSON DEFAULT NULL -- project convention: {created_by_date, updated_by_date[]}
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS traveler_group_members (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    uuid         VARCHAR(64)  NOT NULL,
    sys_id       VARCHAR(32)  NOT NULL UNIQUE,
    group_id     VARCHAR(32)  NOT NULL,   -- traveler_groups.sys_id
    traveler_id  VARCHAR(32)  NOT NULL,   -- travelers.sys_id
    meta_data    JSON DEFAULT NULL, -- project convention: {created_by_date, updated_by_date[]}
    KEY idx_group (group_id),
    KEY idx_traveler (traveler_id),
    UNIQUE KEY uniq_membership (group_id, traveler_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════════════
-- Follow-up: works.work_name + Work↔TravelGroup linkage
-- ═══════════════════════════════════════════════════════════════════

-- works টেবিলে readable নাম রাখার জন্য (আগে কোনো single title column ছিল
-- না, client_info/service_type থেকে assemble করতে হতো)
ALTER TABLE works ADD COLUMN IF NOT EXISTS work_name VARCHAR(255) DEFAULT NULL AFTER sys_id;

-- traveler_groups-কে ঐচ্ছিকভাবে একটা work-এর সাথে bind করার জন্য —
-- Work create হলে automatically একটা group তৈরি হবে (work_name = group_name),
-- traveler link/unlink হলে সেই group-এও sync হবে
ALTER TABLE traveler_groups ADD COLUMN IF NOT EXISTS linked_work_id VARCHAR(32) DEFAULT NULL;
ALTER TABLE traveler_groups ADD UNIQUE KEY IF NOT EXISTS uniq_linked_work (linked_work_id);