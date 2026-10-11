<?php
// PATH: /api/tasks/save-financial.php
// Thin wrapper — delegates to the canonical double-entry store.
// All vendor-payment and client-sale entries from the Task/Work view
// (Air Ticket, Hotel, Transport, Visa, Package confirmation modals)
// POST here; this file simply forwards to financial_entries_v2/store.php
// so the logic lives in exactly one place.
require_once __DIR__ . '/../financial_entries_v2/store.php';