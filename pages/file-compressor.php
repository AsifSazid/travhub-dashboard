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
        .lead-card { transition: all 0.2s ease; cursor: pointer; }
        .lead-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,0.1); border-color: #3b82f6; }
        .modal-slide-in { animation: modalSlideIn 0.3s ease-out; }
        @keyframes modalSlideIn { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
        .column-scroll { scrollbar-width: thin; scrollbar-color: #cbd5e0 #f7fafc; }
        .column-scroll::-webkit-scrollbar { width: 6px; }
        .column-scroll::-webkit-scrollbar-track { background: #f7fafc; border-radius: 3px; }
        .column-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e0; border-radius: 3px; }
        .kanban-btn-move, .kanban-btn-edit { transition: all 0.2s ease; }
        .kanban-btn-move:hover, .kanban-btn-edit:hover { transform: scale(1.1); }
    </style>
    <style>
        :root {
          --bg:        #f8f9fb;
          --bg-2:      #f0f2f5;
          --bg-3:      #eaecf0;
          --surface:   #ffffff;
          --surface-2: #f4f5f7;
          --border:    rgba(0,0,0,.08);
          --border-2:  rgba(0,0,0,.13);
          --ink:       #1a1a2e;
          --ink-2:     #4a4a6a;
          --ink-3:     #8888a8;
          --accent:    #5b4ef0;
          --accent-2:  #7c6dfa;
          --green:     #00a87e;
          --green-dk:  #007a5c;
          --amber:     #d48a0a;
          --red:       #e03050;
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
        body::before { content:''; position:fixed; inset:0; pointer-events:none; z-index:0; }
        body::after { content:''; position:fixed; width:600px; height:600px; background:radial-gradient(circle, rgba(91,78,240,.06) 0%, transparent 70%); top:-200px; left:50%; transform:translateX(-50%); pointer-events:none; z-index:0; }
        .page { width:100%; max-width:560px; position:relative; z-index:1; }
        .header { display:flex; align-items:center; gap:14px; margin-bottom:28px; }
        .logo { width:46px; height:46px; background:linear-gradient(135deg, var(--accent), var(--accent-2)); border-radius:13px; display:grid; place-items:center; font-size:22px; box-shadow:0 6px 18px rgba(91,78,240,.22); flex-shrink:0; }
        .brand-name { font-family:var(--font-display); font-size:26px; font-weight:800; letter-spacing:-.5px; color:var(--ink); }
        .brand-sub { font-size:12px; color:var(--ink-3); letter-spacing:.04em; margin-top:1px; }
        .engines { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:20px; }
        .eng { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:100px; font-size:11px; font-family:var(--font-mono); background:#fff; border:1px solid var(--border-2); color:var(--ink-2); }
        .eng-dot { width:6px; height:6px; border-radius:50%; background:var(--green); box-shadow:0 0 5px rgba(0,168,126,.4); }
        .eng-dot.warn { background:var(--amber); box-shadow:0 0 5px rgba(212,138,10,.4); }
        .card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,.06), 0 1px 4px rgba(0,0,0,.04); }
        .dropzone { margin:24px; border:2px dashed var(--border-2); border-radius:var(--radius-md); padding:36px 20px; text-align:center; cursor:pointer; transition:border-color var(--t), background var(--t); position:relative; background:var(--bg-3); }
        .dropzone:hover, .dropzone.over { border-color:var(--accent); background:rgba(91,78,240,.04); }
        .dropzone input { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
        .dz-icon { font-size:36px; margin-bottom:12px; display:block; }
        .dz-title { font-family:var(--font-display); font-size:15px; font-weight:700; margin-bottom:5px; color:var(--ink); }
        .dz-sub { font-size:12px; color:var(--ink-3); }
        .dz-sub strong { color:var(--ink-2); }
        .file-info { display:none; align-items:center; gap:12px; margin:0 24px 0; padding:12px 14px; background:var(--bg-2); border:1px solid var(--border-2); border-radius:var(--radius-sm); }
        .file-info.show { display:flex; }
        .fi-icon { font-size:24px; flex-shrink:0; }
        .fi-name { font-size:13px; font-weight:600; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex:1; }
        .fi-size { font-size:11px; color:var(--ink-3); margin-top:2px; }
        .fi-remove { background:none; border:none; cursor:pointer; color:var(--ink-3); font-size:18px; transition:color var(--t); flex-shrink:0; padding:2px; }
        .fi-remove:hover { color:var(--red); }
        .controls { display:grid; grid-template-columns:1fr 1fr; gap:12px; padding:18px 24px 0; }
        .field { display:flex; flex-direction:column; gap:6px; }
        .field label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:var(--ink-3); }
        .field input, .field select { font-family:var(--font-body); font-size:14px; color:var(--ink); background:var(--bg-2); border:1.5px solid var(--border-2); border-radius:var(--radius-sm); padding:10px 12px; outline:none; transition:border-color var(--t), box-shadow var(--t); -webkit-appearance:none; }
        .field input:focus, .field select:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(91,78,240,.12); }
        .field select option { background:#fff; color:var(--ink); }
        .suggestion-box { display:none; margin:12px 24px 0; padding:12px 14px; background:var(--bg-2); border:1px solid var(--border-2); border-radius:var(--radius-sm); }
        .suggestion-title { font-weight:700; color:var(--ink); margin-bottom:8px; font-family:var(--font-display); font-size:13px; }
        .action-row { display:flex; gap:10px; padding:18px 24px 24px; }
        .btn-main { flex:1; padding:14px; background:linear-gradient(135deg, var(--accent), var(--accent-2)); color:#fff; font-family:var(--font-display); font-size:15px; font-weight:700; border:none; border-radius:var(--radius-sm); cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:opacity var(--t), transform var(--t), box-shadow var(--t); box-shadow:0 6px 18px rgba(91,78,240,.25); }
        .btn-main:hover:not(:disabled) { opacity:.9; transform:translateY(-1px); box-shadow:0 10px 28px rgba(91,78,240,.32); }
        .btn-main:disabled { opacity:.4; cursor:not-allowed; transform:none; }
        .btn-cancel { padding:14px 16px; background:transparent; color:var(--red); border:1.5px solid rgba(224,48,80,.25); border-radius:var(--radius-sm); font-family:var(--font-display); font-size:13px; font-weight:700; cursor:pointer; transition:background var(--t), border-color var(--t); display:none; white-space:nowrap; }
        .btn-cancel:hover { background:rgba(224,48,80,.07); border-color:var(--red); }
        .btn-cancel.show { display:block; }
        .progress-panel { display:none; margin:0 24px 20px; background:var(--bg-2); border:1px solid var(--border); border-radius:var(--radius-md); padding:16px; animation:fadeUp .25s ease both; }
        .progress-panel.show { display:block; }
        .pp-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .pp-label { font-size:12px; font-family:var(--font-mono); color:var(--ink-2); flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .pp-pct { font-family:var(--font-display); font-size:14px; font-weight:800; color:var(--accent); flex-shrink:0; margin-left:10px; }
        .pp-track { background:var(--bg-3); border-radius:100px; height:6px; overflow:hidden; margin-bottom:14px; }
        .pp-fill { height:100%; width:0%; background:linear-gradient(90deg, var(--accent), var(--accent-2)); border-radius:100px; transition:width .4s cubic-bezier(.4,0,.2,1); }
        .pp-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
        .pp-stat { background:#fff; border-radius:6px; padding:8px 10px; text-align:center; border:1px solid var(--border); }
        .pp-stat-l { display:block; font-size:10px; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-3); font-weight:600; margin-bottom:3px; }
        .pp-stat-v { display:block; font-family:var(--font-display); font-size:12px; font-weight:700; color:var(--ink); }
        .result { display:none; border-top:1px solid var(--border); padding:22px 24px; animation:fadeUp .3s ease both; }
        .result.show { display:block; }
        .res-head { display:flex; align-items:center; gap:12px; margin-bottom:18px; }
        .res-icon { width:40px; height:40px; border-radius:50%; display:grid; place-items:center; font-size:18px; flex-shrink:0; }
        .res-icon.ok   { background:rgba(0,168,126,.10); }
        .res-icon.warn { background:rgba(212,138,10,.10); }
        .res-icon.err  { background:rgba(224,48,80,.10); }
        .res-title { font-family:var(--font-display); font-size:15px; font-weight:700; color:var(--ink); }
        .res-sub { font-size:12px; color:var(--ink-3); margin-top:2px; line-height:1.5; }
        .stats-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:18px; }
        .stat-box { background:var(--bg-2); border-radius:var(--radius-sm); padding:12px; text-align:center; border:1px solid var(--border); }
        .stat-l { font-size:10px; text-transform:uppercase; letter-spacing:.08em; color:var(--ink-3); font-weight:600; margin-bottom:5px; }
        .stat-v { font-family:var(--font-display); font-size:15px; font-weight:800; }
        .stat-v.g { color:var(--green); }
        .stat-v.a { color:var(--accent); }
        .btn-dl { display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:13px; background:linear-gradient(135deg, var(--green), var(--green-dk)); color:#fff; font-family:var(--font-display); font-size:15px; font-weight:700; border:none; border-radius:var(--radius-sm); cursor:pointer; transition:opacity var(--t), transform var(--t), box-shadow var(--t); box-shadow:0 6px 18px rgba(0,168,126,.2); text-decoration:none; }
        .btn-dl:hover { opacity:.9; transform:translateY(-1px); box-shadow:0 10px 28px rgba(0,168,126,.28); }
        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; padding:20px 24px 24px; border-top:1px solid var(--border); }
        .info-item { background:var(--bg-2); border:1px solid var(--border); border-radius:var(--radius-sm); padding:12px 14px; display:flex; gap:10px; align-items:flex-start; }
        .ii-em { font-size:18px; flex-shrink:0; line-height:1.3; }
        .ii-type { font-size:13px; font-weight:700; color:var(--ink); }
        .ii-tech { font-size:11px; color:var(--ink-3); font-family:var(--font-mono); margin-top:2px; line-height:1.5; }
        .footer { text-align:center; font-size:11px; color:var(--ink-3); margin-top:20px; line-height:1.6; }
        .ffmpeg-overlay { display:none; position:fixed; inset:0; background:rgba(248,249,251,.92); backdrop-filter:blur(8px); z-index:100; place-items:center; }
        .ffmpeg-overlay.show { display:grid; }
        .ffmpeg-box { background:var(--surface); border:1px solid var(--border-2); border-radius:var(--radius-lg); padding:40px 48px; text-align:center; max-width:360px; box-shadow:0 20px 60px rgba(0,0,0,.12); }
        .ffmpeg-spinner { width:48px; height:48px; border:3px solid var(--border-2); border-top-color:var(--accent); border-radius:50%; animation:spin 1s linear infinite; margin:0 auto 20px; }
        .ffmpeg-title { font-family:var(--font-display); font-size:17px; font-weight:700; margin-bottom:8px; color:var(--ink); }
        .ffmpeg-sub { font-size:13px; color:var(--ink-3); line-height:1.6; }
        .ffmpeg-prog { margin-top:18px; background:var(--bg-3); border-radius:100px; height:5px; overflow:hidden; }
        .ffmpeg-prog-fill { height:100%; background:linear-gradient(90deg, var(--accent), var(--accent-2)); border-radius:100px; width:0%; transition:width .4s ease; }
        @keyframes spin { to { transform:rotate(360deg); } }
        @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        @media (max-width:480px) {
          .controls { grid-template-columns:1fr; }
          .stats-grid { grid-template-columns:1fr 1fr; }
          .info-grid { grid-template-columns:1fr; }
          .pp-stats { grid-template-columns:1fr 1fr 1fr; }
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <?php include '../elements/header.php'; ?>
    <?php include '../elements/aside.php'; ?>
    <?php include '../elements/preview-model.php'; ?>

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

                <!-- Smart Suggestion Box -->
                <div class="suggestion-box" id="suggestionBox">
                  <div class="suggestion-title">📊 Smart Suggestion</div>
                  <div id="suggestionContent"></div>
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

    <?php include '../elements/floating-menus.php'; ?>

    <script>
        const API_URL_FOR_ALL_LEADS = "<?php echo $getAllLeadsApi; ?>";
        const time = Date.now();
    </script>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    <script src="../assets/js/functional/dashboard.js"></script>

    <!-- Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>

    <script>
    'use strict';

    // ── PDF.js worker ──────────────────────────────────────────────
    if (window.pdfjsLib) {
      pdfjsLib.GlobalWorkerOptions.workerSrc =
        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }

    // ── Element refs ───────────────────────────────────────────────
    const dropzone      = document.getElementById('dropzone');
    const fileInput     = document.getElementById('fileInput');
    const fileInfo      = document.getElementById('fileInfo');
    const fiIcon        = document.getElementById('fiIcon');
    const fiName        = document.getElementById('fiName');
    const fiSize        = document.getElementById('fiSize');
    const fiRemove      = document.getElementById('fiRemove');
    const compressBtn   = document.getElementById('compressBtn');
    const cancelBtn     = document.getElementById('cancelBtn');
    const btnIcon       = document.getElementById('btnIcon');
    const btnText       = document.getElementById('btnText');
    const progressPanel = document.getElementById('progressPanel');
    const ppLabel       = document.getElementById('ppLabel');
    const ppPct         = document.getElementById('ppPct');
    const ppFill        = document.getElementById('ppFill');
    const ppOrig        = document.getElementById('ppOrig');
    const ppCurr        = document.getElementById('ppCurr');
    const ppElapsed     = document.getElementById('ppElapsed');
    const result        = document.getElementById('result');
    const resIcon       = document.getElementById('resIcon');
    const resTitle      = document.getElementById('resTitle');
    const resSub        = document.getElementById('resSub');
    const statsGrid     = document.getElementById('statsGrid');
    const stOrig        = document.getElementById('stOrig');
    const stComp        = document.getElementById('stComp');
    const stSaved       = document.getElementById('stSaved');
    const dlBtn         = document.getElementById('dlBtn');

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
    const tick    = () => new Promise(r => setTimeout(r, 0));

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
      updateSuggestion();
    }

    fiRemove.addEventListener('click', () => {
      currentFile = null;
      fileInput.value = '';
      fileInfo.classList.remove('show');
      compressBtn.disabled = true;
      hideResult();
      progressPanel.classList.remove('show');
      document.getElementById('suggestionBox').style.display = 'none';
    });

    document.getElementById('targetMB').addEventListener('input', updateSuggestion);
    document.getElementById('quality').addEventListener('change', updateSuggestion);

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

      // ── Realistic target check for PDF ────────────────────────
      if (isPDF(currentFile)) {
        const ratio    = targetBytes / currentFile.size;
        const minRatio = { 0.95:0.45, 0.85:0.28, 0.75:0.20, 0.60:0.13, 0.40:0.08 };
        const minAch   = minRatio[quality] || 0.20;

        if (ratio < minAch) {
          const minMB    = ((currentFile.size * minAch) / (1024*1024)).toFixed(1);
          const sugg     = Object.entries(minRatio).filter(([q,r]) => r <= ratio).sort((a,b) => b[0]-a[0])[0];
          const suggestedQ = sugg ? Math.round(sugg[0]*100)+'%' : '40%';

          const go = confirm(
            `⚠️ Warning!\n\n` +
            `Target: ${(targetBytes/1024/1024).toFixed(2)} MB\n` +
            `Quality: ${Math.round(quality*100)}%\n\n` +
            `এই quality তে minimum achievable size ~${minMB} MB।\n\n` +
            `এর চেয়ে ছোট করতে quality কমাতে হবে (suggested: ${suggestedQ})।\n\n` +
            `তবুও চেষ্টা করতে চাইলে OK চাপো।\n` +
            `Quality কমিয়ে retry করতে Cancel চাপো।`
          );
          if (!go) return;
        }
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
        const url      = URL.createObjectURL(blob);
        const baseName = currentFile.name.replace(/\.[^/.]+$/, '');
        const dlName   = baseName + '_compressed.' + ext;

        showSuccess({ origSize, compSize, saved, url, dlName, method, fallback: compSize >= origSize });

      } catch (err) {
        stopElapsedTimer();
        setLoading(false);
        progressPanel.classList.remove('show');
        if (!cancelled) showError(err.message || 'Compression failed.');
      }
    });

    // ==============================================================
    //  SMART SUGGESTION ENGINE
    // ==============================================================
    function updateSuggestion() {
      const box     = document.getElementById('suggestionBox');
      const content = document.getElementById('suggestionContent');
      if (!box || !content) return;
      if (!currentFile) { box.style.display = 'none'; return; }

      const targetMBVal = parseFloat(document.getElementById('targetMB').value);
      if (isNaN(targetMBVal) || targetMBVal <= 0) { box.style.display = 'none'; return; }

      const targetBytes = targetMBVal * 1024 * 1024;
      const ratio       = targetBytes / currentFile.size;
      const fileMB      = (currentFile.size / 1024 / 1024).toFixed(1);

      if (isImage(currentFile)) {
        const estimates = [
          { q:0.95, label:'Maximum (95%)', ratio:0.75 },
          { q:0.85, label:'High (85%)',    ratio:0.55 },
          { q:0.75, label:'Good (75%)',    ratio:0.40 },
          { q:0.60, label:'Medium (60%)',  ratio:0.28 },
          { q:0.40, label:'Low (40%)',     ratio:0.18 },
        ];
        renderSuggestion(content, box, currentFile.size, targetBytes, ratio, estimates, fileMB, targetMBVal);
      } else if (isPDF(currentFile)) {
        const estimates = [
          { q:0.95, label:'Maximum (95%)', ratio:0.45 },
          { q:0.85, label:'High (85%)',    ratio:0.28 },
          { q:0.75, label:'Good (75%)',    ratio:0.20 },
          { q:0.60, label:'Medium (60%)',  ratio:0.13 },
          { q:0.40, label:'Low (40%)',     ratio:0.08 },
        ];
        renderSuggestion(content, box, currentFile.size, targetBytes, ratio, estimates, fileMB, targetMBVal);
      } else if (isVideo(currentFile)) {
        box.style.display = 'block';
        content.innerHTML = `<span style="color:var(--ink-2);font-size:12px;">🎬 Video compression — quality setting এখানে প্রযোজ্য নয়। FFmpeg CRF mode use হবে।</span>`;
      } else {
        box.style.display = 'block';
        content.innerHTML = `<span style="color:var(--ink-2);font-size:12px;">📦 ZIP compression — quality setting এখানে প্রযোজ্য নয়।</span>`;
      }
    }

    function renderSuggestion(content, box, fileSize, targetBytes, ratio, estimates, fileMB, targetMBVal) {
      let html = `<div style="color:var(--ink-3);margin-bottom:8px;font-size:12px;">`;
      html += `📁 <b style="color:var(--ink)">${fileMB} MB</b> &rarr; 🎯 <b style="color:var(--ink)">${targetMBVal} MB</b> &nbsp;`;
      html += `<span>(${Math.round((1-ratio)*100)}% reduction needed)</span></div>`;

      let bestMatch = null;

      for (const est of estimates) {
        const estBytes = fileSize * est.ratio;
        const estMB    = (estBytes / 1024 / 1024).toFixed(1);
        let icon, color, note;

        if (estBytes <= targetBytes * 1.05) {
          if (!bestMatch) bestMatch = est;
          if (est === bestMatch) { icon='✅'; color='var(--green)'; note='<b>Recommended</b>'; }
          else                   { icon='✅'; color='var(--ink-3)'; note='Also feasible'; }
        } else if (estBytes <= targetBytes * 1.6) {
          icon='⚠️'; color='var(--amber)'; note='Close — might work';
        } else {
          icon='❌'; color='var(--red)'; note='Not feasible';
        }

        html += `<div style="display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid var(--border);">`;
        html += `<span style="width:18px;font-size:13px;">${icon}</span>`;
        html += `<span style="flex:1;color:var(--ink-2);font-size:12px;">${est.label}</span>`;
        html += `<span style="color:${color};font-family:var(--font-mono);font-size:11px;min-width:55px;text-align:right;">~${estMB} MB</span>`;
        html += `<span style="color:${color};font-size:11px;min-width:120px;text-align:right;">${note}</span>`;
        html += `</div>`;
      }

      if (!bestMatch) {
        html += `<div style="margin-top:8px;color:var(--red);font-size:11px;padding:6px 8px;background:rgba(224,48,80,.06);border-radius:6px;">`;
        html += `⚠️ এই target size টা যেকোনো quality তেই অর্জন করা কঠিন। Target একটু বাড়ান।</div>`;
      } else {
        html += `<div style="margin-top:8px;padding:6px 8px;background:rgba(0,168,126,.08);border-radius:6px;color:var(--green);font-size:11px;">`;
        html += `💡 Best choice: <b>${bestMatch.label}</b> — উপরের dropdown থেকে select করুন।`;
        // document.getElementById('quality').value = String(bestMatch.q);
      }

      box.style.display = 'block';
      content.innerHTML = html;
    }

    // ==============================================================
    //  IMAGE COMPRESSION
    //  Phase 1: Quality fixed, scale কমাও (aspect ratio intact)
    //  Phase 2: Scale limit এলে quality একটু কমাও
    // ==============================================================
    async function compressImage(file, targetBytes, quality) {
      setProgress('Loading image…', 5);

      const bitmap = await createImageBitmap(file);
      const { width, height } = bitmap;

      setProgress('Compressing — keeping quality, reducing scale…', 10);

      const scaleSteps = [];
      for (let s = 1.0; s >= 0.10; s = parseFloat((s - 0.05).toFixed(2))) scaleSteps.push(s);

      for (let i = 0; i < scaleSteps.length; i++) {
        if (cancelled) throw new Error('Cancelled');
        const sc   = scaleSteps[i];
        const newW = Math.max(60, Math.round(width  * sc));
        const newH = Math.max(60, Math.round(height * sc));
        const canvas = new OffscreenCanvas(newW, newH);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, newW, newH);
        const blob = await canvas.convertToBlob({ type:'image/jpeg', quality });
        setProgress(`Scale ${Math.round(sc*100)}% (${newW}×${newH}) — q=${Math.round(quality*100)}% — ${fmtBytes(blob.size)}`, 10+(i/scaleSteps.length)*75, blob.size);
        if (blob.size <= targetBytes) { bitmap.close(); return { blob, ext:'jpg', method:`Scale ${Math.round(sc*100)}% at q=${Math.round(quality*100)}% (aspect ratio preserved)` }; }
        await tick();
      }

      setProgress('Scale limit reached — slightly reducing quality…', 87);
      const qualSteps = [];
      for (let q = quality-0.05; q >= 0.15; q = parseFloat((q-0.05).toFixed(2))) qualSteps.push(q);

      for (let i = 0; i < qualSteps.length; i++) {
        if (cancelled) throw new Error('Cancelled');
        const q    = qualSteps[i];
        const newW = Math.max(60, Math.round(width  * 0.10));
        const newH = Math.max(60, Math.round(height * 0.10));
        const canvas = new OffscreenCanvas(newW, newH);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, newW, newH);
        const blob = await canvas.convertToBlob({ type:'image/jpeg', quality:q });
        setProgress(`Min scale 10% — q=${Math.round(q*100)}% — ${fmtBytes(blob.size)}`, 87+(i/qualSteps.length)*10, blob.size);
        if (blob.size <= targetBytes) { bitmap.close(); return { blob, ext:'jpg', method:`Scale 10%, q=${Math.round(q*100)}% (aspect ratio preserved)` }; }
        await tick();
      }

      const canvas = new OffscreenCanvas(Math.max(60,Math.round(width*0.10)), Math.max(60,Math.round(height*0.10)));
      canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
      const blob = await canvas.convertToBlob({ type:'image/jpeg', quality:0.15 });
      bitmap.close();
      return { blob, ext:'jpg', method:'Minimum possible (10% scale, q=15%)' };
    }

    // ==============================================================
    //  PDF COMPRESSION
    // ==============================================================
    async function compressPDF(file, targetBytes, quality) {
      if (window.PDFLib) {
        try { return await compressPDFWithPdfLib(file, targetBytes, quality); }
        catch (e) { console.warn('pdf-lib failed, fallback:', e.message); }
      }
      return await compressPDFWithRender(file, targetBytes, quality);
    }

    async function compressPDFWithPdfLib(file, targetBytes, quality) {
      const { PDFDocument } = PDFLib;
      setProgress('Parsing PDF structure…', 5); await tick();

      const srcBytes  = new Uint8Array(await file.arrayBuffer());
      const pdfDoc    = await PDFDocument.load(srcBytes, { ignoreEncryption:true });
      const pages     = pdfDoc.getPages();
      const pageCount = pages.length;

      setProgress(`PDF parsed — ${pageCount} pages — extracting images…`, 12); await tick();

      let imagesFound = 0, imagesReplaced = 0;

      for (let pi = 0; pi < pageCount; pi++) {
        if (cancelled) throw new Error('Cancelled');
        const page      = pages[pi];
        const resources = page.node.Resources();
        if (!resources) continue;
        const xObjects  = resources.lookup(PDFLib.PDFName.of('XObject'), PDFLib.PDFDict);
        if (!xObjects) continue;

        for (const key of xObjects.keys()) {
          if (cancelled) throw new Error('Cancelled');
          const xObj = xObjects.lookup(key);
          if (!xObj || !(xObj instanceof PDFLib.PDFRawStream)) continue;
          const subtype = xObj.dict.lookup(PDFLib.PDFName.of('Subtype'));
          if (!subtype || subtype.toString() !== '/Image') continue;
          imagesFound++;
          const imgW = xObj.dict.lookup(PDFLib.PDFName.of('Width'));
          const imgH = xObj.dict.lookup(PDFLib.PDFName.of('Height'));
          if (!imgW || !imgH) continue;
          const filter     = xObj.dict.lookup(PDFLib.PDFName.of('Filter'));
          const filterName = filter ? filter.toString() : '';
          if (!filterName.includes('DCTDecode') && !filterName.includes('DCT')) continue;
          const imgBytes = xObj.contents;
          setProgress(`Page ${pi+1}/${pageCount} — image ${imagesFound} (${imgW.value()}×${imgH.value()}) JPEG…`, 12+((pi/pageCount)*72)); await tick();
          try {
            const jpegBlob = new Blob([imgBytes], { type:'image/jpeg' });
            const bmp      = await createImageBitmap(jpegBlob);
            const canvas   = new OffscreenCanvas(bmp.width, bmp.height);
            canvas.getContext('2d').drawImage(bmp, 0, 0); bmp.close();
            const reBlob = await canvas.convertToBlob({ type:'image/jpeg', quality });
            if (reBlob.size >= imgBytes.byteLength * 0.90) continue;
            const newBytes = new Uint8Array(await reBlob.arrayBuffer());
            xObj.contents = newBytes;
            xObj.dict.set(PDFLib.PDFName.of('Filter'),  PDFLib.PDFName.of('DCTDecode'));
            xObj.dict.set(PDFLib.PDFName.of('Length'),   PDFLib.PDFNumber.of(newBytes.byteLength));
            xObj.dict.delete(PDFLib.PDFName.of('DecodeParms'));
            imagesReplaced++;
          } catch { continue; }
        }
        setProgress(`Page ${pi+1}/${pageCount} — ${imagesReplaced} images replaced`, 12+((pi+1)/pageCount*72)); await tick();
      }

      setProgress(`Saving PDF — ${imagesReplaced}/${imagesFound} images compressed…`, 88); await tick();
      const outBytes = await pdfDoc.save({ useObjectStreams:true });
      const blob     = new Blob([outBytes], { type:'application/pdf' });

      if (blob.size > file.size * 0.95 && imagesReplaced === 0)
        throw new Error('pdf-lib: no compressible images found, switching to render mode');

      return { blob, ext:'pdf', method: imagesReplaced > 0
        ? `pdf-lib — ${imagesReplaced} JPEG images re-compressed, text/fonts intact`
        : `pdf-lib — structure only (no JPEG images found)` };
    }

    async function compressPDFWithRender(file, targetBytes, quality) {
      if (!window.pdfjsLib) throw new Error('PDF.js not loaded. Please refresh.');

      setProgress('Loading PDF…', 5);
      const arrayBuffer = await file.arrayBuffer();
      const pdf         = await pdfjsLib.getDocument({ data:arrayBuffer }).promise;
      const totalPages  = pdf.numPages;
      setProgress(`${totalPages} pages found — starting compression…`, 10);

      // Phase 1 — scale কমাও, quality ছুঁবো না
      const scaleSteps = [];
      for (let s = 1.5; s >= 0.20; s = parseFloat((s-0.05).toFixed(2))) scaleSteps.push(s);

      for (let si = 0; si < scaleSteps.length; si++) {
        if (cancelled) throw new Error('Cancelled');
        const sc      = scaleSteps[si];
        const blobs   = [];
        let totalSize = 0, earlyExit = false;

        for (let p = 1; p <= totalPages; p++) {
          if (cancelled) throw new Error('Cancelled');
          const page     = await pdf.getPage(p);
          const viewport = page.getViewport({ scale:sc });
          const canvas   = document.createElement('canvas');
          canvas.width   = Math.round(viewport.width);
          canvas.height  = Math.round(viewport.height);
          const ctx      = canvas.getContext('2d');
          await page.render({ canvasContext:ctx, viewport }).promise;

          const isImgHeavy  = detectImageHeavyPage(ctx, canvas.width, canvas.height);
          const pageQuality = isImgHeavy ? Math.min(quality + 0.10, 0.95) : quality;

          const pb = await new Promise(r => canvas.toBlob(r, 'image/jpeg', pageQuality));
          blobs.push(pb); totalSize += pb.size;
          canvas.width = 1; canvas.height = 1;

          const pct = 10 + (si/scaleSteps.length)*75 + (p/totalPages)*(75/scaleSteps.length);
          setProgress(`Scale ${Math.round(sc*100)}% — ${isImgHeavy?'🖼 img':'📝 txt'} q=${Math.round(pageQuality*100)}% — Page ${p}/${totalPages} — ${fmtBytes(totalSize)}`, pct, totalSize);
          await tick();

          if (p >= 3 && (totalSize/p)*totalPages > targetBytes*1.6) { earlyExit = true; break; }
        }

        if (!earlyExit && totalSize <= targetBytes * 1.05) {
          setProgress('Assembling PDF…', 88);
          const pdfBytes = await buildSimplePDF(blobs);
          const blob     = new Blob([pdfBytes], { type:'application/pdf' });
          return { blob, ext:'pdf', method:`Smart per-page — scale ${Math.round(sc*100)}%, q=${Math.round(quality*100)}%+ (${totalPages}p, aspect ratio preserved)` };
        }
      }

      // Phase 2 — scale limit, quality একটু কমাও
      setProgress('Scale limit reached — slightly reducing quality…', 87);
      const minScale  = 0.20;
      const qualSteps = [];
      for (let q = quality-0.05; q >= 0.15; q = parseFloat((q-0.05).toFixed(2))) qualSteps.push(q);

      for (let qi = 0; qi < qualSteps.length; qi++) {
        if (cancelled) throw new Error('Cancelled');
        const q = qualSteps[qi];
        const blobs = []; let totalSize = 0;

        for (let p = 1; p <= totalPages; p++) {
          if (cancelled) throw new Error('Cancelled');
          const page     = await pdf.getPage(p);
          const viewport = page.getViewport({ scale:minScale });
          const canvas   = document.createElement('canvas');
          canvas.width   = Math.round(viewport.width);
          canvas.height  = Math.round(viewport.height);
          const ctx      = canvas.getContext('2d');
          await page.render({ canvasContext:ctx, viewport }).promise;
          const isImgHeavy  = detectImageHeavyPage(ctx, canvas.width, canvas.height);
          const pageQuality = isImgHeavy ? Math.min(q+0.10, 0.92) : q;
          const pb = await new Promise(r => canvas.toBlob(r, 'image/jpeg', pageQuality));
          blobs.push(pb); totalSize += pb.size;
          canvas.width = 1; canvas.height = 1;
          setProgress(`Min scale — ${isImgHeavy?'🖼':'📝'} q=${Math.round(pageQuality*100)}% — Page ${p}/${totalPages} — ${fmtBytes(totalSize)}`, 87+(qi/qualSteps.length)*10, totalSize);
          await tick();
        }

        if (totalSize <= targetBytes * 1.05) {
          setProgress('Assembling PDF…', 98);
          const pdfBytes = await buildSimplePDF(blobs);
          const blob     = new Blob([pdfBytes], { type:'application/pdf' });
          return { blob, ext:'pdf', method:`Smart per-page min scale, q=${Math.round(q*100)}%+ (${totalPages}p, aspect ratio preserved)` };
        }
      }

      // Last resort
      setProgress('Final attempt — minimum quality…', 97);
      const blobs = [];
      for (let p = 1; p <= totalPages; p++) {
        const page     = await pdf.getPage(p);
        const viewport = page.getViewport({ scale:0.20 });
        const canvas   = document.createElement('canvas');
        canvas.width   = Math.round(viewport.width);
        canvas.height  = Math.round(viewport.height);
        const ctx      = canvas.getContext('2d');
        await page.render({ canvasContext:ctx, viewport }).promise;
        const isImgHeavy = detectImageHeavyPage(ctx, canvas.width, canvas.height);
        const pb = await new Promise(r => canvas.toBlob(r, 'image/jpeg', isImgHeavy?0.35:0.20));
        blobs.push(pb); canvas.width = 1; canvas.height = 1;
      }
      const pdfBytes = await buildSimplePDF(blobs);
      const blob     = new Blob([pdfBytes], { type:'application/pdf' });
      return { blob, ext:'pdf', method:`Minimum — smart per-page (${totalPages}p)` };
    }

    function detectImageHeavyPage(ctx, width, height) {
      const sampleSize = 200;
      const data       = ctx.getImageData(0, 0, width, height).data;
      const totalPx    = width * height;
      let colorVariance = 0, prevR = 0, prevG = 0, prevB = 0;
      for (let i = 0; i < sampleSize; i++) {
        const idx = Math.floor(Math.random() * totalPx) * 4;
        const r = data[idx], g = data[idx+1], b = data[idx+2];
        if (r>240 && g>240 && b>240) continue;
        colorVariance += Math.abs(r-prevR) + Math.abs(g-prevG) + Math.abs(b-prevB);
        prevR=r; prevG=g; prevB=b;
      }
      return colorVariance > 4000;
    }

    async function buildSimplePDF(jpegBlobs) {
      const enc = new TextEncoder(), parts = [], offsets = [];
      const push      = str   => parts.push(enc.encode(str));
      const pushBytes = bytes => parts.push(bytes instanceof Uint8Array ? bytes : new Uint8Array(bytes));
      const calcOffset = ()   => parts.reduce((s, p) => s + p.byteLength, 0);

      push('%PDF-1.4\n%\xFF\xFF\xFF\xFF\n');
      const pageCount   = jpegBlobs.length, pageSizes = [];
      const catId=1, pagesId=2, firstPageId=3, firstImgId=3+pageCount;

      for (let i = 0; i < pageCount; i++) {
        const jpegBytes = new Uint8Array(await jpegBlobs[i].arrayBuffer());
        const { w, h }  = getJpegDimensions(jpegBytes);
        pageSizes.push({ w, h });
        const imgId=firstImgId+i, pageId=firstPageId+i;
        offsets[imgId] = calcOffset();
        push(`${imgId} 0 obj\n<< /Type /XObject /Subtype /Image /Width ${w} /Height ${h} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ${jpegBytes.byteLength} >>\nstream\n`);
        pushBytes(jpegBytes); push('\nendstream\nendobj\n');
        offsets[pageId] = calcOffset();
        push(`${pageId} 0 obj\n<< /Type /Page /Parent ${pagesId} 0 R /MediaBox [0 0 ${w} ${h}] /Resources << /XObject << /Im${i} ${imgId} 0 R >> >> /Contents ${imgId+pageCount} 0 R >>\nendobj\n`);
      }

      for (let i = 0; i < pageCount; i++) {
        const { w, h } = pageSizes[i], stream=`q ${w} 0 0 ${h} 0 0 cm /Im${i} Do Q`, strmId=firstImgId+pageCount+i;
        offsets[strmId] = calcOffset();
        push(`${strmId} 0 obj\n<< /Length ${stream.length} >>\nstream\n`); push(stream); push('\nendstream\nendobj\n');
      }

      offsets[pagesId] = calcOffset();
      push(`${pagesId} 0 obj\n<< /Type /Pages /Kids [${Array.from({length:pageCount},(_,i)=>`${firstPageId+i} 0 R`).join(' ')}] /Count ${pageCount} >>\nendobj\n`);
      offsets[catId] = calcOffset();
      push(`${catId} 0 obj\n<< /Type /Catalog /Pages ${pagesId} 0 R >>\nendobj\n`);

      const xrefOffset = calcOffset(), maxId = Math.max(...Object.keys(offsets).map(Number));
      push(`xref\n0 ${maxId+1}\n0000000000 65535 f \n`);
      for (let id=1; id<=maxId; id++) push(offsets[id]!==undefined ? String(offsets[id]).padStart(10,'0')+' 00000 n \n' : '0000000000 65535 f \n');
      push(`trailer\n<< /Size ${maxId+1} /Root ${catId} 0 R >>\nstartxref\n${xrefOffset}\n%%EOF\n`);

      const totalLen = parts.reduce((s,p)=>s+p.byteLength,0), out=new Uint8Array(totalLen); let pos=0;
      for (const p of parts) { out.set(p,pos); pos+=p.byteLength; }
      return out;
    }

    function getJpegDimensions(bytes) {
      let i = 2;
      while (i < bytes.length) {
        if (bytes[i] !== 0xFF) break;
        const marker = bytes[i+1], len=(bytes[i+2]<<8)|bytes[i+3];
        if (marker>=0xC0 && marker<=0xC3) return { w:(bytes[i+7]<<8)|bytes[i+8], h:(bytes[i+5]<<8)|bytes[i+6] };
        i += 2+len;
      }
      return { w:595, h:842 };
    }

    // ==============================================================
    //  VIDEO COMPRESSION — FFmpeg.wasm
    // ==============================================================
    async function compressVideo(file, targetBytes, quality) {
      if (!ffmpegLoaded) await loadFFmpeg();
      const { FFmpeg } = ffmpegInstance;
      const ffmpeg     = new FFmpeg();
      const crfStart   = { 0.95:18, 0.85:20, 0.75:23, 0.60:26, 0.40:30 };
      const startCRF   = crfStart[quality] || 20;

      ffmpeg.on('progress', ({ progress }) => setProgress(`Encoding video… ${Math.round(progress*100)}%`, Math.min(95, 10+progress*85)));
      await ffmpeg.load({
        coreURL: 'https://unpkg.com/@ffmpeg/core@0.12.6/dist/esm/ffmpeg-core.js',
        wasmURL: 'https://unpkg.com/@ffmpeg/core@0.12.6/dist/esm/ffmpeg-core.wasm',
      });

      const inputExt = file.name.split('.').pop(), inputName='input.'+inputExt, outName='output.mp4';
      setProgress('Loading video into memory…', 5);
      await ffmpeg.writeFile(inputName, new Uint8Array(await file.arrayBuffer()));

      const crfSteps = [];
      for (let c=startCRF; c<=40; c+=3) crfSteps.push(c);
      let bestBlob = null;

      for (let i=0; i<crfSteps.length; i++) {
        if (cancelled) throw new Error('Cancelled');
        const crf = crfSteps[i];
        setProgress(`Encoding — CRF ${crf} (original resolution)…`, 10+(i/crfSteps.length)*80);
        try {
          await ffmpeg.exec(['-i',inputName,'-c:v','libx264','-crf',String(crf),'-preset','fast','-c:a','aac','-b:a','128k','-movflags','+faststart','-y',outName]);
          const data = await ffmpeg.readFile(outName);
          const blob = new Blob([data.buffer], { type:'video/mp4' });
          bestBlob = blob;
          setProgress(`CRF ${crf} → ${fmtBytes(blob.size)}`, 10+((i+1)/crfSteps.length)*80, blob.size);
          if (blob.size <= targetBytes) {
            await ffmpeg.deleteFile(inputName);
            try { await ffmpeg.deleteFile(outName); } catch(e) {}
            return { blob, ext:'mp4', method:`FFmpeg H.264 CRF=${crf} (original resolution)` };
          }
        } catch(e) {}
      }

      await ffmpeg.deleteFile(inputName);
      try { await ffmpeg.deleteFile(outName); } catch(e) {}
      if (bestBlob) return { blob:bestBlob, ext:'mp4', method:`FFmpeg H.264 CRF=40 (best effort)` };
      throw new Error('Video compression failed on all attempts.');
    }

    async function loadFFmpeg() {
      const overlay = document.getElementById('ffmpegOverlay');
      const fill    = document.getElementById('ffmpegProgFill');
      const lbl     = document.getElementById('ffmpegProgLabel');
      overlay.classList.add('show');
      try {
        const module = await import('https://unpkg.com/@ffmpeg/ffmpeg@0.12.10/dist/esm/index.js');
        ffmpegInstance = module; ffmpegLoaded = true;
        fill.style.width = '100%'; lbl.textContent = '100% — Ready!';
        await new Promise(r => setTimeout(r, 600));
      } finally { overlay.classList.remove('show'); }
    }

    // ==============================================================
    //  OTHER FILES — JSZip
    // ==============================================================
    async function compressOther(file, targetBytes) {
      setProgress('Creating ZIP archive…', 20); await tick();
      if (!window.JSZip) throw new Error('JSZip failed to load.');
      const zip = new JSZip();
      zip.file(file.name, file, { compression:'DEFLATE', compressionOptions:{ level:9 } });
      setProgress('Compressing…', 50); await tick();
      const blob = await zip.generateAsync(
        { type:'blob', compression:'DEFLATE', compressionOptions:{ level:9 } },
        meta => setProgress(`Compressing… ${meta.percent.toFixed(0)}%`, meta.percent)
      );
      return { blob, ext:'zip', method:'JSZip DEFLATE level 9' };
    }

    // ==============================================================
    //  UI HELPERS
    // ==============================================================
    function setLoading(on) {
      compressBtn.disabled = on;
      if (on) { btnIcon.textContent='⏳'; btnText.textContent='Compressing…'; cancelBtn.classList.add('show'); }
      else    { btnIcon.textContent='🗜️'; btnText.textContent='Compress File'; cancelBtn.classList.remove('show'); }
    }

    function showSuccess({ origSize, compSize, saved, url, dlName, method, fallback }) {
      result.classList.add('show');
      if (fallback || compSize >= origSize) {
        resIcon.className='res-icon warn'; resIcon.textContent='⚠️'; resTitle.textContent='Minimal Compression';
      } else {
        resIcon.className='res-icon ok'; resIcon.textContent='✅'; resTitle.textContent='Compressed Successfully';
      }
      resSub.textContent      = method;
      statsGrid.style.display = 'grid';
      stOrig.textContent      = fmtBytes(origSize);
      stComp.textContent      = fmtBytes(compSize);
      stSaved.textContent     = saved + '%';
      dlBtn.style.display     = 'flex';
      dlBtn.href              = url;
      dlBtn.download          = dlName;
      dlBtn.innerHTML         = '📥 Download — ' + dlName;
    }

    function showError(msg) {
      result.classList.add('show');
      resIcon.className       = 'res-icon err';
      resIcon.textContent     = '❌';
      resTitle.textContent    = 'Failed';
      resSub.textContent      = msg;
      statsGrid.style.display = 'none';
      dlBtn.style.display     = 'none';
    }

    function hideResult() {
      result.classList.remove('show');
      statsGrid.style.display = 'none';
      dlBtn.style.display     = 'none';
    }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            DashboardApp.init();
            setInterval(() => { DashboardApp.refreshLeads(); }, 30000);
        });
    </script>
</body>
</html>