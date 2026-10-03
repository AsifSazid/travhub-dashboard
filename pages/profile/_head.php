<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* ── Sidebar nav ─────────────────────────────────── */
        .mp-sidenav { width: 220px; flex-shrink: 0; }
        @media (max-width: 767px) { .mp-sidenav { width: 100%; } }

        .mp-nav-btn {
            display: flex; align-items: center; gap: 10px;
            width: 100%; padding: 10px 14px; border-radius: 10px;
            font-size: .875rem; font-weight: 500; color: #475569;
            background: transparent; border: none; cursor: pointer;
            text-align: left; transition: background .15s, color .15s;
        }
        .mp-nav-btn:hover { background: #f1f5f9; color: #1e293b; }
        .mp-nav-btn.mp-active { background: #eff6ff; color: #2563eb; font-weight: 600; }
        .mp-nav-btn .mp-nav-icon { width: 32px; height: 32px; border-radius: 8px;
            display:flex; align-items:center; justify-content:center;
            background:#f1f5f9; font-size:.85rem; flex-shrink:0; }
        .mp-nav-btn.mp-active .mp-nav-icon { background:#dbeafe; color:#2563eb; }
        .mp-nav-badge { margin-left:auto; background:#ef4444; color:#fff;
            font-size:.65rem; font-weight:700; padding:1px 6px; border-radius:99px; }
        .mp-soon-lbl { margin-left:auto; font-size:.65rem; color:#94a3b8;
            background:#f1f5f9; padding:1px 6px; border-radius:99px; }

        /* ── Content sections ────────────────────────────── */
        .mp-section { animation: mpFadeUp .18s ease; }
        @keyframes mpFadeUp {
            from { opacity:0; transform:translateY(6px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ── Inner info-tabs (My Info section) ───────────── */
        .mp-itab-btn {
            padding: 8px 14px; font-size:.8rem; font-weight:500;
            border-bottom: 2px solid transparent; color:#64748b;
            background:transparent; border-top:none; border-left:none; border-right:none;
            cursor:pointer; white-space:nowrap; transition:color .15s,border-color .15s;
        }
        .mp-itab-btn:hover { color:#1e293b; border-bottom-color:#e2e8f0; }
        .mp-itab-btn.mp-active { color:#2563eb; border-bottom-color:#2563eb; }

        /* ── Info rows ───────────────────────────────────── */
        .mp-info-row {
            display:flex; flex-direction:column; gap:2px;
            padding:10px 0; border-bottom:1px solid #f1f5f9;
        }
        .mp-info-row:last-child { border:none; }
        .mp-lbl { font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; font-weight:600; }
        .mp-val { font-size:.95rem; color:#1e293b; font-weight:500; }
        .mp-val.empty { color:#cbd5e1; font-style:italic; }

        /* ── Photo ring ──────────────────────────────────── */
        .mp-photo-ring {
            width:110px; height:110px; border-radius:50%;
            border:4px solid #3b82f6; overflow:hidden; background:#e2e8f0;
            position:relative; cursor:pointer; flex-shrink:0;
        }
        .mp-photo-ring img { width:100%; height:100%; object-fit:cover; display:block; }
        .mp-photo-overlay {
            position:absolute; inset:0; background:rgba(0,0,0,.45);
            display:flex; align-items:center; justify-content:center;
            opacity:0; transition:opacity .2s; border-radius:50%;
        }
        .mp-photo-ring:hover .mp-photo-overlay { opacity:1; }

        /* ── Password strength ───────────────────────────── */
        .mp-pw-bar { height:4px; border-radius:2px; transition:width .3s,background .3s; }

        /* ── Calendar ────────────────────────────────────── */
        .mp-cal-grid {
            display:grid; grid-template-columns:repeat(7,1fr); gap:3px;
        }
        .mp-cal-day {
            aspect-ratio:1; border-radius:8px; display:flex; flex-direction:column;
            align-items:center; justify-content:center; font-size:.72rem;
            font-weight:500; cursor:default; position:relative;
            transition:transform .1s;
        }
        .mp-cal-day:hover { transform:scale(1.05); z-index:2; }
        .mp-cal-day .mp-day-num { font-size:.82rem; font-weight:700; line-height:1; }
        .mp-cal-day .mp-day-code { font-size:.6rem; margin-top:1px; opacity:.8; }

        /* Day status colours */
        .mp-d-present   { background:#d1fae5; color:#065f46; }
        .mp-d-absent    { background:#fee2e2; color:#991b1b; }
        .mp-d-late      { background:#fef3c7; color:#92400e; }
        .mp-d-half_day  { background:#e0e7ff; color:#3730a3; }
        .mp-d-on_leave  { background:#fce7f3; color:#9d174d; }
        .mp-d-weekend   { background:#f8fafc; color:#cbd5e1; }
        .mp-d-holiday   { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
        .mp-d-future    { background:#f8fafc; color:#94a3b8; }
        .mp-d-not_marked{ background:#fafafa; color:#94a3b8; border:1px dashed #e2e8f0; }
        .mp-d-empty     { background:transparent; }
        /* Selected day highlight — overrides status colour */
        .mp-d-selected  { outline:3px solid #3b82f6; outline-offset:-3px; z-index:3; transform:scale(1.08); }

        /* ── Leave type pill ─────────────────────────────── */
        .mp-lt-pill {
            display:inline-flex; align-items:center; gap:5px;
            padding:3px 10px; border-radius:99px; font-size:.75rem; font-weight:600;
        }

        /* ── Modal ───────────────────────────────────────── */
        .mp-modal-bg {
            position:fixed; inset:0; background:rgba(0,0,0,.45);
            display:flex; align-items:center; justify-content:center;
            z-index:9999; padding:16px;
        }
        .mp-modal {
            background:#fff; border-radius:16px; width:100%; max-width:480px;
            max-height:90vh; overflow-y:auto; padding:24px;
            box-shadow:0 20px 60px rgba(0,0,0,.2);
        }
    </style>
</head>
