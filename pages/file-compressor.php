<?php
include_once('./authenticate.php');

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$getAllLeadsApi = $ip_port . "api/leads/all-leads.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="../assets/tailwind/script.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Additional styles for lead cards and modal */
        .lead-card {
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .lead-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
        }

        .modal-slide-in {
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .column-scroll {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f7fafc;
        }

        .column-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .column-scroll::-webkit-scrollbar-track {
            background: #f7fafc;
            border-radius: 3px;
        }

        .column-scroll::-webkit-scrollbar-thumb {
            background-color: #cbd5e0;
            border-radius: 3px;
        }

        .kanban-btn-move,
        .kanban-btn-edit {
            transition: all 0.2s ease;
        }

        .kanban-btn-move:hover,
        .kanban-btn-edit:hover {
            transform: scale(1.1);
        }
    </style>
    <style>
        :root {
          --bg:        #0a0a0f;
          --bg-2:      #111118;
          --bg-3:      #1a1a24;
          --surface:   #1e1e2a;
          --surface-2: #252535;
          --border:    rgba(255,255,255,.07);
          --border-2:  rgba(255,255,255,.12);
          --ink:       #f0f0f8;
          --ink-2:     #a0a0b8;
          --ink-3:     #606078;
          --accent:    #7c6dfa;
          --accent-2:  #a594ff;
          --green:     #00d4a0;
          --green-dk:  #00a87e;
          --amber:     #f5a623;
          --red:       #ff4d6a;
          --radius-sm: 8px;
          --radius-md: 14px;
          --radius-lg: 22px;
          --font-display: 'Syne', sans-serif;
          --font-body:    'DM Sans', sans-serif;
          --font-mono:    'DM Mono', monospace;
          --t: .2s cubic-bezier(.4,0,.2,1);
        }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        
        body {
          font-family: var(--font-body);
          background: var(--bg);
          color: var(--ink);
          min-height: 100vh;
          display: grid;
          place-items: start center;
          padding: 40px 20px 60px;
        }
        
        /* ── Noise texture overlay ── */
        body::before {
          content: '';
          position: fixed;
          inset: 0;
          background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
          pointer-events: none;
          z-index: 0;
        }
        
        /* ── Glow blobs ── */
        body::after {
          content: '';
          position: fixed;
          width: 600px; height: 600px;
          background: radial-gradient(circle, rgba(124,109,250,.08) 0%, transparent 70%);
          top: -200px; left: 50%;
          transform: translateX(-50%);
          pointer-events: none;
          z-index: 0;
        }
        
        .page {
          width: 100%;
          max-width: 560px;
          position: relative;
          z-index: 1;
        }
        
        /* ── Header ── */
        .header {
          display: flex;
          align-items: center;
          gap: 14px;
          margin-bottom: 28px;
        }
        .logo {
          width: 46px; height: 46px;
          background: linear-gradient(135deg, var(--accent), var(--accent-2));
          border-radius: 13px;
          display: grid;
          place-items: center;
          font-size: 22px;
          box-shadow: 0 8px 24px rgba(124,109,250,.35);
          flex-shrink: 0;
        }
        .brand-name {
          font-family: var(--font-display);
          font-size: 26px;
          font-weight: 800;
          letter-spacing: -.5px;
          background: linear-gradient(135deg, #fff 30%, var(--accent-2));
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
        }
        .brand-sub {
          font-size: 12px;
          color: var(--ink-3);
          letter-spacing: .04em;
          margin-top: 1px;
        }
        
        /* ── Engine badges ── */
        .engines {
          display: flex;
          flex-wrap: wrap;
          gap: 6px;
          margin-bottom: 20px;
        }
        .eng {
          display: inline-flex;
          align-items: center;
          gap: 5px;
          padding: 4px 10px;
          border-radius: 100px;
          font-size: 11px;
          font-family: var(--font-mono);
          background: var(--surface);
          border: 1px solid var(--border-2);
          color: var(--ink-2);
        }
        .eng-dot {
          width: 6px; height: 6px;
          border-radius: 50%;
          background: var(--green);
          box-shadow: 0 0 6px var(--green);
        }
        .eng-dot.warn { background: var(--amber); box-shadow: 0 0 6px var(--amber); }
        
        /* ── Card ── */
        .card {
          background: var(--surface);
          border: 1px solid var(--border);
          border-radius: var(--radius-lg);
          overflow: hidden;
          box-shadow: 0 24px 80px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.04);
        }
        
        /* ── Drop zone ── */
        .dropzone {
          margin: 24px;
          border: 2px dashed var(--border-2);
          border-radius: var(--radius-md);
          padding: 36px 20px;
          text-align: center;
          cursor: pointer;
          transition: border-color var(--t), background var(--t);
          position: relative;
          background: var(--bg-3);
        }
        .dropzone:hover, .dropzone.over {
          border-color: var(--accent);
          background: rgba(124,109,250,.05);
        }
        .dropzone input {
          position: absolute;
          inset: 0;
          opacity: 0;
          cursor: pointer;
          width: 100%;
          height: 100%;
        }
        .dz-icon {
          font-size: 36px;
          margin-bottom: 12px;
          display: block;
          filter: grayscale(.3);
        }
        .dz-title {
          font-family: var(--font-display);
          font-size: 15px;
          font-weight: 700;
          margin-bottom: 5px;
        }
        .dz-sub { font-size: 12px; color: var(--ink-3); }
        .dz-sub strong { color: var(--ink-2); }
        
        /* ── File chosen ── */
        .file-info {
          display: none;
          align-items: center;
          gap: 12px;
          margin: 0 24px 0;
          padding: 12px 14px;
          background: var(--bg-3);
          border: 1px solid var(--border-2);
          border-radius: var(--radius-sm);
        }
        .file-info.show { display: flex; }
        .fi-icon { font-size: 24px; flex-shrink: 0; }
        .fi-name {
          font-size: 13px;
          font-weight: 600;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          flex: 1;
        }
        .fi-size { font-size: 11px; color: var(--ink-3); margin-top: 2px; }
        .fi-remove {
          background: none; border: none; cursor: pointer;
          color: var(--ink-3); font-size: 18px;
          transition: color var(--t); flex-shrink: 0; padding: 2px;
        }
        .fi-remove:hover { color: var(--red); }
        
        /* ── Controls ── */
        .controls {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 12px;
          padding: 18px 24px 0;
        }
        .field { display: flex; flex-direction: column; gap: 6px; }
        .field label {
          font-size: 11px;
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: .08em;
          color: var(--ink-3);
        }
        .field input, .field select {
          font-family: var(--font-body);
          font-size: 14px;
          color: var(--ink);
          background: var(--bg-3);
          border: 1.5px solid var(--border-2);
          border-radius: var(--radius-sm);
          padding: 10px 12px;
          outline: none;
          transition: border-color var(--t), box-shadow var(--t);
          -webkit-appearance: none;
        }
        .field input:focus, .field select:focus {
          border-color: var(--accent);
          box-shadow: 0 0 0 3px rgba(124,109,250,.18);
        }
        .field select option { background: var(--bg-2); }
        
        /* ── Action row ── */
        .action-row {
          display: flex;
          gap: 10px;
          padding: 18px 24px 24px;
        }
        .btn-main {
          flex: 1;
          padding: 14px;
          background: linear-gradient(135deg, var(--accent), var(--accent-2));
          color: #fff;
          font-family: var(--font-display);
          font-size: 15px;
          font-weight: 700;
          border: none;
          border-radius: var(--radius-sm);
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          transition: opacity var(--t), transform var(--t), box-shadow var(--t);
          box-shadow: 0 8px 24px rgba(124,109,250,.3);
        }
        .btn-main:hover:not(:disabled) {
          opacity: .9;
          transform: translateY(-1px);
          box-shadow: 0 12px 32px rgba(124,109,250,.4);
        }
        .btn-main:disabled { opacity: .45; cursor: not-allowed; transform: none; }
        .btn-cancel {
          padding: 14px 16px;
          background: transparent;
          color: var(--red);
          border: 1.5px solid rgba(255,77,106,.3);
          border-radius: var(--radius-sm);
          font-family: var(--font-display);
          font-size: 13px;
          font-weight: 700;
          cursor: pointer;
          transition: background var(--t), border-color var(--t);
          display: none;
          white-space: nowrap;
        }
        .btn-cancel:hover { background: rgba(255,77,106,.1); border-color: var(--red); }
        .btn-cancel.show { display: block; }
        
        /* ── Progress panel ── */
        .progress-panel {
          display: none;
          margin: 0 24px 20px;
          background: var(--bg-3);
          border: 1px solid var(--border);
          border-radius: var(--radius-md);
          padding: 16px;
          animation: fadeUp .25s ease both;
        }
        .progress-panel.show { display: block; }
        
        .pp-head {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 10px;
        }
        .pp-label {
          font-size: 12px;
          font-family: var(--font-mono);
          color: var(--ink-2);
          flex: 1;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
        .pp-pct {
          font-family: var(--font-display);
          font-size: 14px;
          font-weight: 800;
          color: var(--accent-2);
          flex-shrink: 0;
          margin-left: 10px;
        }
        .pp-track {
          background: var(--surface-2);
          border-radius: 100px;
          height: 6px;
          overflow: hidden;
          margin-bottom: 14px;
        }
        .pp-fill {
          height: 100%;
          width: 0%;
          background: linear-gradient(90deg, var(--accent), var(--accent-2));
          border-radius: 100px;
          transition: width .4s cubic-bezier(.4,0,.2,1);
        }
        .pp-stats {
          display: grid;
          grid-template-columns: repeat(3,1fr);
          gap: 8px;
        }
        .pp-stat {
          background: var(--surface);
          border-radius: 6px;
          padding: 8px 10px;
          text-align: center;
        }
        .pp-stat-l {
          display: block;
          font-size: 10px;
          text-transform: uppercase;
          letter-spacing: .07em;
          color: var(--ink-3);
          font-weight: 600;
          margin-bottom: 3px;
        }
        .pp-stat-v {
          display: block;
          font-family: var(--font-display);
          font-size: 12px;
          font-weight: 700;
          color: var(--ink);
        }
        
        /* ── Result ── */
        .result {
          display: none;
          border-top: 1px solid var(--border);
          padding: 22px 24px;
          animation: fadeUp .3s ease both;
        }
        .result.show { display: block; }
        
        .res-head {
          display: flex;
          align-items: center;
          gap: 12px;
          margin-bottom: 18px;
        }
        .res-icon {
          width: 40px; height: 40px;
          border-radius: 50%;
          display: grid;
          place-items: center;
          font-size: 18px;
          flex-shrink: 0;
        }
        .res-icon.ok   { background: rgba(0,212,160,.12); }
        .res-icon.warn { background: rgba(245,166,35,.12); }
        .res-icon.err  { background: rgba(255,77,106,.12); }
        .res-title {
          font-family: var(--font-display);
          font-size: 15px;
          font-weight: 700;
        }
        .res-sub { font-size: 12px; color: var(--ink-3); margin-top: 2px; line-height: 1.5; }
        
        .stats-grid {
          display: grid;
          grid-template-columns: repeat(3,1fr);
          gap: 10px;
          margin-bottom: 18px;
        }
        .stat-box {
          background: var(--bg-3);
          border-radius: var(--radius-sm);
          padding: 12px;
          text-align: center;
          border: 1px solid var(--border);
        }
        .stat-l {
          font-size: 10px;
          text-transform: uppercase;
          letter-spacing: .08em;
          color: var(--ink-3);
          font-weight: 600;
          margin-bottom: 5px;
        }
        .stat-v {
          font-family: var(--font-display);
          font-size: 15px;
          font-weight: 800;
        }
        .stat-v.g { color: var(--green); }
        .stat-v.a { color: var(--accent-2); }
        
        .btn-dl {
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          width: 100%;
          padding: 13px;
          background: linear-gradient(135deg, var(--green), var(--green-dk));
          color: #fff;
          font-family: var(--font-display);
          font-size: 15px;
          font-weight: 700;
          border: none;
          border-radius: var(--radius-sm);
          cursor: pointer;
          transition: opacity var(--t), transform var(--t), box-shadow var(--t);
          box-shadow: 0 8px 24px rgba(0,212,160,.25);
          text-decoration: none;
        }
        .btn-dl:hover {
          opacity: .9;
          transform: translateY(-1px);
          box-shadow: 0 12px 32px rgba(0,212,160,.35);
        }
        
        /* ── Info grid ── */
        .info-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 10px;
          padding: 20px 24px 24px;
          border-top: 1px solid var(--border);
        }
        .info-item {
          background: var(--bg-3);
          border: 1px solid var(--border);
          border-radius: var(--radius-sm);
          padding: 12px 14px;
          display: flex;
          gap: 10px;
          align-items: flex-start;
        }
        .ii-em { font-size: 18px; flex-shrink: 0; line-height: 1.3; }
        .ii-type { font-size: 13px; font-weight: 700; }
        .ii-tech { font-size: 11px; color: var(--ink-3); font-family: var(--font-mono); margin-top: 2px; line-height: 1.5; }
        
        /* ── Footer ── */
        .footer {
          text-align: center;
          font-size: 11px;
          color: var(--ink-3);
          margin-top: 20px;
          line-height: 1.6;
        }
        
        /* ── FFmpeg loading overlay ── */
        .ffmpeg-overlay {
          display: none;
          position: fixed;
          inset: 0;
          background: rgba(10,10,15,.92);
          backdrop-filter: blur(8px);
          z-index: 100;
          place-items: center;
        }
        .ffmpeg-overlay.show { display: grid; }
        .ffmpeg-box {
          background: var(--surface);
          border: 1px solid var(--border-2);
          border-radius: var(--radius-lg);
          padding: 40px 48px;
          text-align: center;
          max-width: 360px;
          box-shadow: 0 40px 100px rgba(0,0,0,.6);
        }
        .ffmpeg-spinner {
          width: 48px; height: 48px;
          border: 3px solid var(--border-2);
          border-top-color: var(--accent);
          border-radius: 50%;
          animation: spin 1s linear infinite;
          margin: 0 auto 20px;
        }
        .ffmpeg-title {
          font-family: var(--font-display);
          font-size: 17px;
          font-weight: 700;
          margin-bottom: 8px;
        }
        .ffmpeg-sub { font-size: 13px; color: var(--ink-3); line-height: 1.6; }
        .ffmpeg-prog {
          margin-top: 18px;
          background: var(--bg-3);
          border-radius: 100px;
          height: 5px;
          overflow: hidden;
        }
        .ffmpeg-prog-fill {
          height: 100%;
          background: linear-gradient(90deg, var(--accent), var(--accent-2));
          border-radius: 100px;
          width: 0%;
          transition: width .4s ease;
        }
        
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes fadeUp {
          from { opacity: 0; transform: translateY(8px); }
          to   { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 480px) {
          body { padding: 20px 14px 40px; }
          .controls { grid-template-columns: 1fr; }
          .stats-grid { grid-template-columns: 1fr 1fr; }
          .info-grid { grid-template-columns: 1fr; }
          .pp-stats { grid-template-columns: 1fr 1fr 1fr; }
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>
    
    <!-- Preview Modal -->
    <?php include '../elements/preview-model.php'; ?>

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pb-16 pl-64 md:pb-0 md:pl-16 lg:pl-64 transition-all duration-300">
        <div class="p-3 md:p-6">
            <!-- FFmpeg loading overlay -->
            <div class="ffmpeg-overlay" id="ffmpegOverlay">
              <div class="ffmpeg-box">
                <div class="ffmpeg-spinner"></div>
                <div class="ffmpeg-title">Loading Video Engine</div>
                <div class="ffmpeg-sub">FFmpeg.wasm (~31MB) একবার load হলে<br>browser cache-এ থাকবে।</div>
                <div class="ffmpeg-prog"><div class="ffmpeg-prog-fill" id="ffmpegProgFill"></div></div>
                <div style="margin-top:10px; font-size:12px; color:var(--ink-3); font-family:var(--font-mono)" id="ffmpegProgLabel">0%</div>
              </div>
            </div>
            
            <div class="page">
            
              <!-- Header -->
              <div class="header">
                <div class="logo">🗜️</div>
                <div>
                  <div class="brand-name">FileForge</div>
                  <div class="brand-sub">100% Browser-Side · No Upload · No Timeout</div>
                </div>
              </div>
            
              <!-- Engine badges -->
              <div class="engines">
                <span class="eng"><span class="eng-dot"></span>Canvas API</span>
                <span class="eng"><span class="eng-dot"></span>PDF.js</span>
                <span class="eng"><span class="eng-dot"></span>FFmpeg.wasm</span>
                <span class="eng"><span class="eng-dot"></span>JSZip</span>
                <span class="eng"><span class="eng-dot warn"></span>No Server Needed</span>
              </div>
            
              <!-- Main card -->
              <div class="card">
            
                <!-- Drop zone -->
                <div class="dropzone" id="dropzone">
                  <input type="file" id="fileInput"
                    accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.mp4,.avi,.mov,.mkv,.webm,.zip,.docx,.doc,.pptx,.xlsx,.txt,.csv,.png">
                  <span class="dz-icon">📂</span>
                  <div class="dz-title">Drop file here or click to browse</div>
                  <div class="dz-sub">Images · PDF · Video · Archives &nbsp;·&nbsp; <strong>No size limit</strong></div>
                </div>
            
                <!-- File info -->
                <div class="file-info" id="fileInfo">
                  <span class="fi-icon" id="fiIcon">📄</span>
                  <div style="flex:1;min-width:0">
                    <div class="fi-name" id="fiName">—</div>
                    <div class="fi-size" id="fiSize">—</div>
                  </div>
                  <button class="fi-remove" id="fiRemove">✕</button>
                </div>
            
                <!-- Controls -->
                <div class="controls">
                  <div class="field">
                    <label>Target Size (MB)</label>
                    <input type="number" id="targetMB" value="2" min="0.1" max="500" step="0.5">
                  </div>
                  <div class="field">
                    <label>Quality</label>
                    <select id="quality">
                      <option value="0.95">Maximum (95%)</option>
                      <option value="0.85" selected>High (85%) ✦</option>
                      <option value="0.75">Good (75%)</option>
                      <option value="0.60">Medium (60%)</option>
                      <option value="0.40">Low (40%)</option>
                    </select>
                  </div>
                </div>
            
                <!-- Action row -->
                <div class="action-row">
                  <button class="btn-main" id="compressBtn" disabled>
                    <span id="btnIcon">🗜️</span>
                    <span id="btnText">Compress File</span>
                  </button>
                  <button class="btn-cancel" id="cancelBtn">✕ Stop</button>
                </div>
            
                <!-- Progress panel -->
                <div class="progress-panel" id="progressPanel">
                  <div class="pp-head">
                    <span class="pp-label" id="ppLabel">Processing…</span>
                    <span class="pp-pct" id="ppPct">0%</span>
                  </div>
                  <div class="pp-track"><div class="pp-fill" id="ppFill"></div></div>
                  <div class="pp-stats">
                    <div class="pp-stat">
                      <span class="pp-stat-l">Original</span>
                      <span class="pp-stat-v" id="ppOrig">—</span>
                    </div>
                    <div class="pp-stat">
                      <span class="pp-stat-l">Current</span>
                      <span class="pp-stat-v" id="ppCurr">—</span>
                    </div>
                    <div class="pp-stat">
                      <span class="pp-stat-l">Elapsed</span>
                      <span class="pp-stat-v" id="ppElapsed">0s</span>
                    </div>
                  </div>
                </div>
            
                <!-- Result -->
                <div class="result" id="result">
                  <div class="res-head">
                    <div class="res-icon" id="resIcon">✅</div>
                    <div>
                      <div class="res-title" id="resTitle">Compressed Successfully</div>
                      <div class="res-sub" id="resSub">—</div>
                    </div>
                  </div>
                  <div class="stats-grid" id="statsGrid" style="display:none">
                    <div class="stat-box">
                      <div class="stat-l">Original</div>
                      <div class="stat-v" id="stOrig">—</div>
                    </div>
                    <div class="stat-box">
                      <div class="stat-l">Compressed</div>
                      <div class="stat-v g" id="stComp">—</div>
                    </div>
                    <div class="stat-box">
                      <div class="stat-l">Saved</div>
                      <div class="stat-v a" id="stSaved">—</div>
                    </div>
                  </div>
                  <a class="btn-dl" id="dlBtn" style="display:none">
                    📥 Download Compressed File
                  </a>
                </div>
            
                <!-- Info grid -->
                <div class="info-grid">
                  <div class="info-item">
                    <span class="ii-em">🖼️</span>
                    <div>
                      <div class="ii-type">Images</div>
                      <div class="ii-tech">JPG · PNG · GIF · WEBP<br>Canvas API re-encode</div>
                    </div>
                  </div>
                  <div class="info-item">
                    <span class="ii-em">📄</span>
                    <div>
                      <div class="ii-type">PDF</div>
                      <div class="ii-tech">PDF.js render<br>Canvas re-export</div>
                    </div>
                  </div>
                  <div class="info-item">
                    <span class="ii-em">🎬</span>
                    <div>
                      <div class="ii-type">Video</div>
                      <div class="ii-tech">MP4 · WebM · MOV<br>FFmpeg.wasm H.264</div>
                    </div>
                  </div>
                  <div class="info-item">
                    <span class="ii-em">📦</span>
                    <div>
                      <div class="ii-type">Other Files</div>
                      <div class="ii-tech">ZIP · DOCX · CSV · TXT<br>JSZip compression</div>
                    </div>
                  </div>
                </div>
            
              </div><!-- .card -->
            
              <div class="footer">
                সব compression তোমার browser-এ হয় — কোনো file server-এ upload হয় না।<br>
                FileForge Client Edition · No timeout · No server needed
              </div>
            
            </div><!-- .page -->
        </div>
    </main>

    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>


    <!-- Custom JavaScript Library -->
    <script>
        const API_URL_FOR_ALL_LEADS  = "<?php echo $getAllLeadsApi; ?>";         
        const time = Date.now();
    </script>
        
    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    <script src="../assets/js/functional/dashboard.js"></script>
    
    <!-- Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <!-- pdf-lib: PDF structure-intact compression (text/vector preserved) -->
    <script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
    
    <script>
        'use strict';
        
        // ── PDF.js worker ──────────────────────────────────────────────
        if (window.pdfjsLib) {
          pdfjsLib.GlobalWorkerOptions.workerSrc =
            'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
        
        // ── Element refs ───────────────────────────────────────────────
        const dropzone     = document.getElementById('dropzone');
        const fileInput    = document.getElementById('fileInput');
        const fileInfo     = document.getElementById('fileInfo');
        const fiIcon       = document.getElementById('fiIcon');
        const fiName       = document.getElementById('fiName');
        const fiSize       = document.getElementById('fiSize');
        const fiRemove     = document.getElementById('fiRemove');
        const compressBtn  = document.getElementById('compressBtn');
        const cancelBtn    = document.getElementById('cancelBtn');
        const btnIcon      = document.getElementById('btnIcon');
        const btnText      = document.getElementById('btnText');
        const progressPanel= document.getElementById('progressPanel');
        const ppLabel      = document.getElementById('ppLabel');
        const ppPct        = document.getElementById('ppPct');
        const ppFill       = document.getElementById('ppFill');
        const ppOrig       = document.getElementById('ppOrig');
        const ppCurr       = document.getElementById('ppCurr');
        const ppElapsed    = document.getElementById('ppElapsed');
        const result       = document.getElementById('result');
        const resIcon      = document.getElementById('resIcon');
        const resTitle     = document.getElementById('resTitle');
        const resSub       = document.getElementById('resSub');
        const statsGrid    = document.getElementById('statsGrid');
        const stOrig       = document.getElementById('stOrig');
        const stComp       = document.getElementById('stComp');
        const stSaved      = document.getElementById('stSaved');
        const dlBtn        = document.getElementById('dlBtn');
        
        // ── State ──────────────────────────────────────────────────────
        let currentFile    = null;
        let cancelled      = false;
        let startTime      = 0;
        let elapsedTimer   = null;
        let ffmpegInstance = null;
        let ffmpegLoaded   = false;
        
        // ── Helpers ────────────────────────────────────────────────────
        const fmtBytes = b => {
          if (!b || b <= 0) return '0 B';
          const u = ['B','KB','MB','GB'];
          const i = Math.floor(Math.log(b) / Math.log(1024));
          return (b / Math.pow(1024, Math.min(i,3))).toFixed(2) + ' ' + u[Math.min(i,3)];
        };
        
        const iconMap = {
          pdf:'📄', jpg:'🖼️', jpeg:'🖼️', png:'🖼️', gif:'🖼️', webp:'🖼️',
          mp4:'🎬', avi:'🎬', mov:'🎬', mkv:'🎬', webm:'🎬',
          zip:'📦', docx:'📝', doc:'📝', pptx:'📊', xlsx:'📊',
          txt:'📋', csv:'📋',
        };
        const getIcon = name => iconMap[name.split('.').pop().toLowerCase()] || '📄';
        
        const isImage = f => /^image\//.test(f.type) || /\.(jpg|jpeg|png|gif|webp)$/i.test(f.name);
        const isPDF   = f => f.type === 'application/pdf' || /\.pdf$/i.test(f.name);
        const isVideo = f => /^video\//.test(f.type) || /\.(mp4|avi|mov|mkv|webm|flv)$/i.test(f.name);
        
        function setProgress(label, pct, currentBytes) {
          ppLabel.textContent = label;
          ppPct.textContent   = Math.round(pct) + '%';
          ppFill.style.width  = Math.min(pct, 100) + '%';
          if (currentBytes !== undefined) ppCurr.textContent = fmtBytes(currentBytes);
          const secs = Math.round((Date.now() - startTime) / 1000);
          const mins = Math.floor(secs / 60);
          ppElapsed.textContent = mins > 0 ? `${mins}m${secs%60}s` : `${secs}s`;
        }
        
        function startElapsedTimer() {
          startTime = Date.now();
          clearInterval(elapsedTimer);
          elapsedTimer = setInterval(() => {
            const s = Math.round((Date.now() - startTime) / 1000);
            const m = Math.floor(s / 60);
            ppElapsed.textContent = m > 0 ? `${m}m${s%60}s` : `${s}s`;
          }, 1000);
        }
        
        function stopElapsedTimer() { clearInterval(elapsedTimer); }
        
        // ── Drag & drop ────────────────────────────────────────────────
        dropzone.addEventListener('dragover',  e => { e.preventDefault(); dropzone.classList.add('over'); });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('over'));
        dropzone.addEventListener('drop', e => {
          e.preventDefault();
          dropzone.classList.remove('over');
          if (e.dataTransfer.files[0]) selectFile(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', () => {
          if (fileInput.files[0]) selectFile(fileInput.files[0]);
        });
        
        function selectFile(file) {
          currentFile = file;
          fiIcon.textContent = getIcon(file.name);
          fiName.textContent = file.name;
          fiSize.textContent = fmtBytes(file.size);
          fileInfo.classList.add('show');
          compressBtn.disabled = false;
          hideResult();
        }
        
        fiRemove.addEventListener('click', () => {
          currentFile = null;
          fileInput.value = '';
          fileInfo.classList.remove('show');
          compressBtn.disabled = true;
          hideResult();
          progressPanel.classList.remove('show');
        });
        
        // ── Cancel ─────────────────────────────────────────────────────
        cancelBtn.addEventListener('click', () => {
          cancelled = true;
          cancelBtn.classList.remove('show');
          stopElapsedTimer();
          setLoading(false);
          showError('Compression cancelled.');
        });
        
        // ── Main compress ──────────────────────────────────────────────
        compressBtn.addEventListener('click', async () => {
          if (!currentFile) return;
          cancelled = false;
        
          const targetBytes = parseFloat(document.getElementById('targetMB').value) * 1024 * 1024;
          const quality     = parseFloat(document.getElementById('quality').value);
        
          if (isNaN(targetBytes) || targetBytes < 0.1 * 1024 * 1024) {
            showError('Minimum target size is 0.1 MB.'); return;
          }
        
          setLoading(true);
          hideResult();
          progressPanel.classList.add('show');
          ppOrig.textContent = fmtBytes(currentFile.size);
          ppCurr.textContent = fmtBytes(currentFile.size);
          startElapsedTimer();
        
          try {
            let blob, ext, method;
        
            if (isImage(currentFile)) {
              ({ blob, ext, method } = await compressImage(currentFile, targetBytes, quality));
            } else if (isPDF(currentFile)) {
              ({ blob, ext, method } = await compressPDF(currentFile, targetBytes, quality));
            } else if (isVideo(currentFile)) {
              ({ blob, ext, method } = await compressVideo(currentFile, targetBytes, quality));
            } else {
              ({ blob, ext, method } = await compressOther(currentFile, targetBytes));
            }
        
            if (cancelled) return;
        
            stopElapsedTimer();
            setLoading(false);
            progressPanel.classList.remove('show');
        
            const origSize = currentFile.size;
            const compSize = blob.size;
            const saved    = origSize > 0 ? ((1 - compSize / origSize) * 100).toFixed(1) : 0;
        
            // Create download URL
            const url      = URL.createObjectURL(blob);
            const baseName = currentFile.name.replace(/\.[^/.]+$/, '');
            const dlName   = baseName + '_compressed.' + ext;
        
            showSuccess({
              origSize, compSize, saved, url, dlName, method,
              fallback: compSize >= origSize,
            });
        
          } catch (err) {
            stopElapsedTimer();
            setLoading(false);
            progressPanel.classList.remove('show');
            if (!cancelled) showError(err.message || 'Compression failed.');
          }
        });
        
        // ==============================================================
        //  IMAGE COMPRESSION — Canvas API (Quality-First)
        //
        //  Phase 1 — Full resolution, quality কমাই শুধু (0.96 → 0.30)
        //             Resolution একদম ছোঁব না।
        //  Phase 2 — Target না পেলে তখন scale কমাব (5% steps)
        // ==============================================================
        async function compressImage(file, targetBytes, quality) {
          setProgress('Loading image…', 5);
        
          const bitmap = await createImageBitmap(file);
          const { width, height } = bitmap;
          let blob;
        
          // Phase 1 — full resolution, শুধু quality কমাই
          const qualitySteps = [0.96, 0.92, 0.88, 0.84, 0.80, 0.76, 0.72,
                                0.68, 0.64, 0.60, 0.55, 0.50, 0.45, 0.40, 0.35, 0.30];
          // User-selected quality থেকে শুরু
          const startIdx = Math.max(0, qualitySteps.findIndex(q => q <= quality));
          const steps    = qualitySteps.slice(startIdx);
        
          setProgress('Compressing — full resolution…', 10);
        
          for (let i = 0; i < steps.length; i++) {
            if (cancelled) throw new Error('Cancelled');
            const q      = steps[i];
            const canvas = new OffscreenCanvas(width, height); // scale সবসময় 1.0
            const ctx    = canvas.getContext('2d');
            ctx.drawImage(bitmap, 0, 0, width, height);
            blob = await canvas.convertToBlob({ type: 'image/jpeg', quality: q });
        
            const pct = 10 + (i / steps.length) * 70;
            setProgress(`Quality ${Math.round(q*100)}% — Full Res — ${fmtBytes(blob.size)}`, pct, blob.size);
        
            if (blob.size <= targetBytes) {
              bitmap.close();
              return { blob, ext: 'jpg', method: `Full resolution, q=${Math.round(q*100)}%` };
            }
            await tick();
          }
        
          // Phase 2 — এখানে এলে মানে target পাইনি, তখনই scale কমাব
          setProgress('Reducing scale to reach target…', 82);
          let scale = 0.95;
        
          for (let i = 0; i < 18; i++) {
            if (cancelled) throw new Error('Cancelled');
            const newW   = Math.max(60, Math.round(width  * scale));
            const newH   = Math.max(60, Math.round(height * scale));
            const canvas = new OffscreenCanvas(newW, newH);
            const ctx    = canvas.getContext('2d');
            ctx.drawImage(bitmap, 0, 0, newW, newH);
            blob = await canvas.convertToBlob({ type: 'image/jpeg', quality: 0.82 });
        
            const pct = 82 + (i / 18) * 15;
            setProgress(`Scale ${Math.round(scale*100)}% (${newW}×${newH}) — ${fmtBytes(blob.size)}`, pct, blob.size);
        
            if (blob.size <= targetBytes) break;
            scale -= 0.05;
            if (scale < 0.10) break;
            await tick();
          }
        
          bitmap.close();
          return { blob, ext: 'jpg', method: `Scale ${Math.round(scale*100)}%, q=82%` };
        }
        
        // ==============================================================
        //  PDF COMPRESSION — pdf-lib (Structure-Intact)
        //
        //  এই approach-এ:
        //  ✅ Text সম্পূর্ণ intact থাকে (searchable, selectable)
        //  ✅ Vector graphics intact থাকে
        //  ✅ Fonts intact থাকে
        //  ✅ শুধু embedded JPEG/PNG images re-compress হয়
        //  ✅ Resolution drop হয় না
        //
        //  কীভাবে কাজ করে:
        //  1. pdf-lib দিয়ে PDF parse করা হয়
        //  2. প্রতিটা embedded image extract করা হয়
        //  3. Canvas দিয়ে re-encode করা হয় lower quality-তে
        //  4. Re-compressed image PDF-এ replace করা হয়
        //  5. Structure, text, fonts — সব same থাকে
        //
        //  Fallback: যদি pdf-lib load না হয় বা কাজ না করে,
        //  তাহলে PDF.js high-DPI render করবে
        // ==============================================================
        async function compressPDF(file, targetBytes, quality) {
        
          // ── Try pdf-lib first (structure-intact) ──────────────────
          if (window.PDFLib) {
            try {
              return await compressPDFWithPdfLib(file, targetBytes, quality);
            } catch (e) {
              console.warn('pdf-lib failed, falling back to PDF.js render:', e.message);
              // fall through to PDF.js fallback
            }
          }
        
          // ── Fallback: PDF.js high-DPI render ──────────────────────
          return await compressPDFWithRender(file, targetBytes, quality);
        }
        
        // ── pdf-lib: extract images → re-compress → re-embed ─────────
        async function compressPDFWithPdfLib(file, targetBytes, quality) {
          const { PDFDocument } = PDFLib;
        
          setProgress('Parsing PDF structure…', 5);
          await tick();
        
          const srcBytes  = new Uint8Array(await file.arrayBuffer());
          const pdfDoc    = await PDFDocument.load(srcBytes, { ignoreEncryption: true });
          const pages     = pdfDoc.getPages();
          const pageCount = pages.length;
        
          setProgress(`PDF parsed — ${pageCount} pages — extracting images…`, 12);
          await tick();
        
          // ── Collect all image XObjects across all pages ──────────
          // pdf-lib stores images in the document's XObject resources
          let imagesFound   = 0;
          let imagesReplaced = 0;
        
          // Walk every page's resources
          for (let pi = 0; pi < pageCount; pi++) {
            if (cancelled) throw new Error('Cancelled');
        
            const page      = pages[pi];
            const resources = page.node.Resources();
            if (!resources) continue;
        
            const xObjects = resources.lookup(PDFLib.PDFName.of('XObject'), PDFLib.PDFDict);
            if (!xObjects) continue;
        
            const xObjKeys = xObjects.keys();
        
            for (const key of xObjKeys) {
              if (cancelled) throw new Error('Cancelled');
        
              const xObj = xObjects.lookup(key);
              if (!xObj || !(xObj instanceof PDFLib.PDFRawStream)) continue;
        
              const subtype = xObj.dict.lookup(PDFLib.PDFName.of('Subtype'));
              if (!subtype || subtype.toString() !== '/Image') continue;
        
              imagesFound++;
        
              // Get image dimensions
              const imgW = xObj.dict.lookup(PDFLib.PDFName.of('Width'));
              const imgH = xObj.dict.lookup(PDFLib.PDFName.of('Height'));
              if (!imgW || !imgH) continue;
        
              const w = imgW.value();
              const h = imgH.value();
        
              // Get filter (JPEG = DCTDecode, PNG-like = FlateDecode)
              const filter = xObj.dict.lookup(PDFLib.PDFName.of('Filter'));
              const filterName = filter ? filter.toString() : '';
        
              // Get raw image bytes
              const imgBytes = xObj.contents;
        
              setProgress(
                `Page ${pi+1}/${pageCount} — re-compressing image ${imagesFound} (${w}×${h})…`,
                12 + ((pi * xObjKeys.length + imagesFound) / Math.max(pageCount * 3, 1)) * 70,
                undefined
              );
              await tick();
        
              try {
                // Decode the image onto a canvas, then re-encode as JPEG
                let blob;
                if (filterName.includes('DCTDecode') || filterName.includes('DCT')) {
                  // Already JPEG — re-encode at lower quality
                  const jpegBlob = new Blob([imgBytes], { type: 'image/jpeg' });
                  const bmp      = await createImageBitmap(jpegBlob);
                  const canvas   = new OffscreenCanvas(bmp.width, bmp.height);
                  canvas.getContext('2d').drawImage(bmp, 0, 0);
                  blob = await canvas.convertToBlob({ type: 'image/jpeg', quality });
                  bmp.close();
                } else if (filterName.includes('FlateDecode') || filterName === '') {
                  // PNG/raw — try to decode via ImageBitmap
                  const rawBlob = new Blob([imgBytes]);
                  try {
                    const bmp    = await createImageBitmap(rawBlob);
                    const canvas = new OffscreenCanvas(bmp.width, bmp.height);
                    canvas.getContext('2d').drawImage(bmp, 0, 0);
                    blob = await canvas.convertToBlob({ type: 'image/jpeg', quality });
                    bmp.close();
                  } catch {
                    continue; // can't decode, skip this image
                  }
                } else {
                  continue; // unknown filter, skip
                }
        
                // Only replace if actually smaller
                if (blob.size >= imgBytes.byteLength) continue;
        
                const newBytes = new Uint8Array(await blob.arrayBuffer());
        
                // Replace image stream bytes in the PDF
                xObj.contents = newBytes;
                xObj.dict.set(PDFLib.PDFName.of('Filter'), PDFLib.PDFName.of('DCTDecode'));
                xObj.dict.set(PDFLib.PDFName.of('Length'),  PDFLib.PDFNumber.of(newBytes.byteLength));
                // Remove any DecodeParams that were for the old filter
                xObj.dict.delete(PDFLib.PDFName.of('DecodeParms'));
        
                imagesReplaced++;
        
              } catch (imgErr) {
                // This image failed — skip it, keep original
                continue;
              }
            }
        
            const pct = 12 + ((pi + 1) / pageCount) * 72;
            setProgress(`Page ${pi+1}/${pageCount} done — ${imagesReplaced} images compressed`, pct);
            await tick();
          }
        
          setProgress(`Saving PDF — ${imagesReplaced}/${imagesFound} images compressed…`, 88);
          await tick();
        
          // Save the modified PDF
          const outBytes = await pdfDoc.save({ useObjectStreams: true });
          const blob     = new Blob([outBytes], { type: 'application/pdf' });
        
          const method = imagesReplaced > 0
            ? `pdf-lib — ${imagesReplaced} images re-compressed, text/fonts intact`
            : `pdf-lib — structure compressed (no embedded images found)`;
        
          return { blob, ext: 'pdf', method };
        }
        
        // ── Fallback: PDF.js high-DPI render (when pdf-lib fails) ────
        // Used only if pdf-lib is unavailable or throws.
        // scale 2.0x = 144 DPI — still high quality render.
        async function compressPDFWithRender(file, targetBytes, quality) {
          if (!window.pdfjsLib) throw new Error('PDF.js not loaded. Please refresh.');
        
          setProgress('Loading PDF (render mode)…', 5);
          const arrayBuffer = await file.arrayBuffer();
          const pdf         = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
          const totalPages  = pdf.numPages;
          setProgress(`${totalPages} pages — rendering at 2x DPI…`, 10);
        
          // Try quality steps at scale 2.0 first, then lower scale only if needed
          const qualitySteps = [0.92, 0.86, 0.80, 0.74, 0.68, 0.62, 0.56, 0.50];
          const scaleSteps   = [2.0, 1.5, 1.2, 1.0];
        
          for (const sc of scaleSteps) {
            for (const q of qualitySteps) {
              if (cancelled) throw new Error('Cancelled');
        
              const blobs = [];
              let totalSize = 0;
        
              for (let p = 1; p <= totalPages; p++) {
                if (cancelled) throw new Error('Cancelled');
                const page     = await pdf.getPage(p);
                const viewport = page.getViewport({ scale: sc });
                const canvas   = document.createElement('canvas');
                canvas.width   = Math.round(viewport.width);
                canvas.height  = Math.round(viewport.height);
                await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
                const pb = await new Promise(r => canvas.toBlob(r, 'image/jpeg', q));
                blobs.push(pb);
                totalSize += pb.size;
                canvas.width = 1; canvas.height = 1;
        
                const pct = 10 + (p / totalPages) * 75;
                setProgress(`Render ${sc}x q=${Math.round(q*100)}% — Page ${p}/${totalPages} — ${fmtBytes(totalSize)}`, pct, totalSize);
                await tick();
              }
        
              if (totalSize <= targetBytes * 1.08) {
                setProgress('Assembling PDF…', 90);
                const pdfBytes = await buildSimplePDF(blobs);
                const blob     = new Blob([pdfBytes], { type: 'application/pdf' });
                return { blob, ext: 'pdf', method: `Render fallback ${sc}x q=${Math.round(q*100)}% (${totalPages}p)` };
              }
            }
          }
        
          // Last resort — return whatever we have
          setProgress('Assembling best result…', 97);
          const blobs = [];
          for (let p = 1; p <= totalPages; p++) {
            const page     = await pdf.getPage(p);
            const viewport = page.getViewport({ scale: 1.0 });
            const canvas   = document.createElement('canvas');
            canvas.width   = Math.round(viewport.width);
            canvas.height  = Math.round(viewport.height);
            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
            const pb = await new Promise(r => canvas.toBlob(r, 'image/jpeg', 0.72));
            blobs.push(pb);
            canvas.width = 1; canvas.height = 1;
          }
          const pdfBytes = await buildSimplePDF(blobs);
          const blob     = new Blob([pdfBytes], { type: 'application/pdf' });
          return { blob, ext: 'pdf', method: `Render fallback best effort (${totalPages}p)` };
        }
        
        /**
         * Build a minimal valid PDF from an array of JPEG Blobs (one per page).
         * Uses raw PDF syntax — no external library needed.
         */
        async function buildSimplePDF(jpegBlobs) {
          const enc     = new TextEncoder();
          const parts   = [];
          const offsets = [];
        
          const push = str => parts.push(enc.encode(str));
          const pushBytes = bytes => parts.push(bytes instanceof Uint8Array ? bytes : new Uint8Array(bytes));
        
          // Header
          push('%PDF-1.4\n%\xFF\xFF\xFF\xFF\n');
        
          const pageCount  = jpegBlobs.length;
          const pageObjIds = [];    // object IDs for page objects
          const imgObjIds  = [];    // object IDs for image XObjects
          const pageSizes  = [];    // [{w,h}]
        
          // We'll write: header, then images+pages, then catalog, then xref+trailer
          // Object numbering: 1=catalog, 2=pages, 3...(2+n)=page objects, (3+n)...(2+2n)=images
        
          const catId   = 1;
          const pagesId = 2;
          const firstPageId = 3;
          const firstImgId  = 3 + pageCount;
        
          let byteOffset = 0;
          const calcOffset = () => parts.reduce((s, p) => s + p.byteLength, 0);
        
          // ── Write image XObjects + page objects ──────────────────────
          for (let i = 0; i < pageCount; i++) {
            const jpegBytes = new Uint8Array(await jpegBlobs[i].arrayBuffer());
        
            // Read JPEG dimensions from SOF marker
            const { w, h } = getJpegDimensions(jpegBytes);
            pageSizes.push({ w, h });
        
            const imgId  = firstImgId + i;
            const pageId = firstPageId + i;
        
            // Image stream object
            offsets[imgId] = calcOffset();
            push(`${imgId} 0 obj\n`);
            push(`<< /Type /XObject /Subtype /Image /Width ${w} /Height ${h}`);
            push(` /ColorSpace /DeviceRGB /BitsPerComponent 8`);
            push(` /Filter /DCTDecode /Length ${jpegBytes.byteLength} >>\n`);
            push('stream\n');
            pushBytes(jpegBytes);
            push('\nendstream\nendobj\n');
        
            // Page object
            offsets[pageId] = calcOffset();
            push(`${pageId} 0 obj\n`);
            push(`<< /Type /Page /Parent ${pagesId} 0 R`);
            push(` /MediaBox [0 0 ${w} ${h}]`);
            push(` /Resources << /XObject << /Im${i} ${imgId} 0 R >> >>`);
            push(` /Contents ${imgId + pageCount} 0 R >>\nendobj\n`);
          }
        
          // ── Content streams (draw each image) ────────────────────────
          for (let i = 0; i < pageCount; i++) {
            const { w, h } = pageSizes[i];
            const stream   = `q ${w} 0 0 ${h} 0 0 cm /Im${i} Do Q`;
            const strmId   = firstImgId + pageCount + i;
        
            offsets[strmId] = calcOffset();
            push(`${strmId} 0 obj\n`);
            push(`<< /Length ${stream.length} >>\nstream\n`);
            push(stream);
            push('\nendstream\nendobj\n');
          }
        
          // ── Pages object ──────────────────────────────────────────────
          offsets[pagesId] = calcOffset();
          const kidsStr = Array.from({length: pageCount}, (_, i) => `${firstPageId+i} 0 R`).join(' ');
          push(`${pagesId} 0 obj\n<< /Type /Pages /Kids [${kidsStr}] /Count ${pageCount} >>\nendobj\n`);
        
          // ── Catalog ───────────────────────────────────────────────────
          offsets[catId] = calcOffset();
          push(`${catId} 0 obj\n<< /Type /Catalog /Pages ${pagesId} 0 R >>\nendobj\n`);
        
          // ── Cross-reference table ─────────────────────────────────────
          const xrefOffset = calcOffset();
          const totalObjs  = 1 + pageCount * 3 + 2; // catalog + pages + (page+img+content)*n
          const maxId      = Math.max(...Object.keys(offsets).map(Number));
        
          push(`xref\n0 ${maxId + 1}\n`);
          push('0000000000 65535 f \n');
          for (let id = 1; id <= maxId; id++) {
            if (offsets[id] !== undefined) {
              push(String(offsets[id]).padStart(10, '0') + ' 00000 n \n');
            } else {
              push('0000000000 65535 f \n');
            }
          }
        
          push(`trailer\n<< /Size ${maxId + 1} /Root ${catId} 0 R >>\nstartxref\n${xrefOffset}\n%%EOF\n`);
        
          // Combine all parts
          const totalLen = parts.reduce((s, p) => s + p.byteLength, 0);
          const out      = new Uint8Array(totalLen);
          let pos        = 0;
          for (const p of parts) {
            out.set(p, pos);
            pos += p.byteLength;
          }
          return out;
        }
        
        /** Extract width & height from JPEG SOF marker. */
        function getJpegDimensions(bytes) {
          let i = 2;
          while (i < bytes.length) {
            if (bytes[i] !== 0xFF) break;
            const marker = bytes[i + 1];
            const len    = (bytes[i+2] << 8) | bytes[i+3];
            if (marker >= 0xC0 && marker <= 0xC3) {
              const h = (bytes[i+5] << 8) | bytes[i+6];
              const w = (bytes[i+7] << 8) | bytes[i+8];
              return { w, h };
            }
            i += 2 + len;
          }
          return { w: 595, h: 842 }; // A4 fallback
        }
        
        // ==============================================================
        //  VIDEO COMPRESSION — FFmpeg.wasm (Quality-First)
        //
        //  CRF mode ব্যবহার করব — এটা quality-based encoding।
        //  CRF 18 = near-lossless (অনেক ভালো)
        //  CRF 23 = default quality (ভালো)
        //  CRF 28 = acceptable
        //  CRF 35 = low
        //
        //  প্রথমে CRF 18 try করব। Target না পেলে CRF বাড়াব।
        //  Resolution কখনো কমাব না।
        // ==============================================================
        async function compressVideo(file, targetBytes, quality) {
          if (!ffmpegLoaded) await loadFFmpeg();
        
          const { FFmpeg } = ffmpegInstance;
          const ffmpeg = new FFmpeg();
        
          // Quality → starting CRF
          const crfStart = { 0.95: 18, 0.85: 20, 0.75: 23, 0.60: 26, 0.40: 30 };
          const startCRF = crfStart[quality] || 20;
        
          ffmpeg.on('progress', ({ progress }) => {
            const pct = Math.min(95, 10 + progress * 85);
            setProgress(`Encoding video… ${Math.round(progress * 100)}%`, pct);
          });
        
          await ffmpeg.load({
            coreURL: 'https://unpkg.com/@ffmpeg/core@0.12.6/dist/esm/ffmpeg-core.js',
            wasmURL: 'https://unpkg.com/@ffmpeg/core@0.12.6/dist/esm/ffmpeg-core.wasm',
          });
        
          const inputExt  = file.name.split('.').pop();
          const inputName = 'input.' + inputExt;
          const outName   = 'output.mp4';
        
          setProgress('Loading video into memory…', 5);
          await ffmpeg.writeFile(inputName, new Uint8Array(await file.arrayBuffer()));
        
          // CRF steps — try from startCRF upward
          // Resolution একদম ছোঁব না (-vf scale এর কোনো কিছু নেই)
          const crfSteps = [];
          for (let c = startCRF; c <= 40; c += 3) crfSteps.push(c);
        
          let bestBlob = null;
        
          for (let i = 0; i < crfSteps.length; i++) {
            if (cancelled) throw new Error('Cancelled');
        
            const crf = crfSteps[i];
            setProgress(`Encoding — CRF ${crf} (original resolution)…`, 10 + (i / crfSteps.length) * 80);
        
            try {
              await ffmpeg.exec([
                '-i', inputName,
                '-c:v', 'libx264',
                '-crf', String(crf),
                '-preset', 'fast',
                '-c:a', 'aac',
                '-b:a', '128k',
                '-movflags', '+faststart',
                '-y', outName,
              ]);
        
              const data = await ffmpeg.readFile(outName);
              const blob = new Blob([data.buffer], { type: 'video/mp4' });
              bestBlob   = blob;
        
              setProgress(`CRF ${crf} → ${fmtBytes(blob.size)}`, 10 + ((i+1) / crfSteps.length) * 80, blob.size);
        
              if (blob.size <= targetBytes) {
                await ffmpeg.deleteFile(inputName);
                try { await ffmpeg.deleteFile(outName); } catch(e) {}
                return { blob, ext: 'mp4', method: `FFmpeg H.264 CRF=${crf} (original resolution)` };
              }
            } catch (e) {
              // This CRF step failed, try next
            }
          }
        
          // সব CRF try করেও target পাইনি — best result return করব
          await ffmpeg.deleteFile(inputName);
          try { await ffmpeg.deleteFile(outName); } catch(e) {}
        
          if (bestBlob) {
            return { blob: bestBlob, ext: 'mp4', method: `FFmpeg H.264 CRF=40 (best effort)` };
          }
          throw new Error('Video compression failed on all attempts.');
        }
        
        async function loadFFmpeg() {
          const overlay  = document.getElementById('ffmpegOverlay');
          const fill     = document.getElementById('ffmpegProgFill');
          const lbl      = document.getElementById('ffmpegProgLabel');
        
          overlay.classList.add('show');
        
          try {
            // Dynamically import @ffmpeg/ffmpeg from CDN
            const module = await import('https://unpkg.com/@ffmpeg/ffmpeg@0.12.10/dist/esm/index.js');
            ffmpegInstance = module;
            ffmpegLoaded   = true;
        
            fill.style.width = '100%';
            lbl.textContent  = '100% — Ready!';
            await new Promise(r => setTimeout(r, 600));
          } finally {
            overlay.classList.remove('show');
          }
        }
        
        // ==============================================================
        //  OTHER FILES COMPRESSION — JSZip
        // ==============================================================
        async function compressOther(file, targetBytes) {
          setProgress('Creating ZIP archive…', 20);
          await tick();
        
          if (!window.JSZip) throw new Error('JSZip failed to load.');
        
          const zip = new JSZip();
          zip.file(file.name, file, { compression: 'DEFLATE', compressionOptions: { level: 9 } });
        
          setProgress('Compressing…', 50);
          await tick();
        
          const blob = await zip.generateAsync(
            { type: 'blob', compression: 'DEFLATE', compressionOptions: { level: 9 } },
            meta => setProgress(`Compressing… ${meta.percent.toFixed(0)}%`, meta.percent, undefined)
          );
        
          return { blob, ext: 'zip', method: 'JSZip DEFLATE level 9' };
        }
        
        // ==============================================================
        //  UI HELPERS
        // ==============================================================
        function setLoading(on) {
          compressBtn.disabled = on;
          if (on) {
            btnIcon.textContent = '⏳';
            btnText.textContent = 'Compressing…';
            cancelBtn.classList.add('show');
          } else {
            btnIcon.textContent = '🗜️';
            btnText.textContent = 'Compress File';
            cancelBtn.classList.remove('show');
          }
        }
        
        function showSuccess({ origSize, compSize, saved, url, dlName, method, fallback }) {
          result.classList.add('show');
        
          if (fallback || compSize >= origSize) {
            resIcon.className = 'res-icon warn';
            resIcon.textContent = '⚠️';
            resTitle.textContent = 'Minimal Compression';
          } else {
            resIcon.className = 'res-icon ok';
            resIcon.textContent = '✅';
            resTitle.textContent = 'Compressed Successfully';
          }
        
          resSub.textContent  = method;
          statsGrid.style.display = 'grid';
          stOrig.textContent  = fmtBytes(origSize);
          stComp.textContent  = fmtBytes(compSize);
          stSaved.textContent = saved + '%';
        
          dlBtn.style.display = 'flex';
          dlBtn.href          = url;
          dlBtn.download      = dlName;
          dlBtn.textContent   = '';
          dlBtn.innerHTML     = '📥 Download — ' + dlName;
        }
        
        function showError(msg) {
          result.classList.add('show');
          resIcon.className   = 'res-icon err';
          resIcon.textContent = '❌';
          resTitle.textContent = 'Failed';
          resSub.textContent  = msg;
          statsGrid.style.display = 'none';
          dlBtn.style.display = 'none';
        }
        
        function hideResult() {
          result.classList.remove('show');
          statsGrid.style.display = 'none';
          dlBtn.style.display = 'none';
        }
        
        // Yield to browser so UI can update
        const tick = () => new Promise(r => setTimeout(r, 0));

    </script>

    <script>
        // Initialize both when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // UIInteractions.init();
            DashboardApp.init();

            // Optional: Auto-refresh leads every 30 seconds
            setInterval(() => {
                DashboardApp.refreshLeads();
            }, 30000);
        });
    </script>
</body>

</html>