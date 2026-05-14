CREATE TABLE air_ticket_quatations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uuid VARCHAR(100) NOT NULL,
  sys_id VARCHAR(100) NOT NULL,
  informations LONGTEXT NOT NULL,
  quotations LONGTEXT NULL,
  percentage DECIMAL(10,2) NULL,
  ve_fixed_price DECIMAL(12,2) NULL DEFAULT 0,
  form_data JSON NULL,
  meta_data JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- If table already exists, run:
-- ALTER TABLE air_ticket_quatations
--   ADD COLUMN ve_fixed_price DECIMAL(12,2) NULL DEFAULT 0 AFTER percentage;

-- ───────────────────────────────────────────────────────────────────
-- meta_data JSON shape (stored under form_data.segments[i]):
--
-- {
--   "heading": "Outbound Flight",
--   "segment_title": "DAC-MNL",
--   "date": "2025-05-14",
--   "airline": "SQ",
--   "flight_no": "SQ988",
--   "dep_airport": "DAC",
--   "dep_time": "23:55",
--   "arr_airport": "SIN",
--   "arr_time": "06:00",
--   "arr_day_indicator": true,
--   "has_transit": true,
--   "transit_time": "Tr 3 Hours",
--   "connections": [
--     {
--       "dep_airport": "SIN",
--       "dep_time": "09:00",
--       "arr_airport": "MNL",
--       "arr_time": "14:00",
--       "arr_day_indicator": false
--     }
--   ]
-- }
--
-- has_transit = false   → transit_time and connections are ignored at render time
-- has_transit = true    → transit_time shown; connections[] flights rendered after it
-- connections[] can hold multiple legs (each with its own next-day +1 flag)
-- ───────────────────────────────────────────────────────────────────