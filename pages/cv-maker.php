<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>CV Builder – Asif Mostofa Sazid</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/docx/8.5.0/docx.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--accent:#1a56db;--ink:#0f172a;--muted:#64748b;--line:#e2e8f0}
body{font-family:'Poppins',sans-serif;background:#d1d5db;min-height:100vh;display:flex;flex-direction:column}

/* ── TOPBAR ── */
#topbar{background:#0f172a;color:#f8fafc;display:flex;align-items:center;justify-content:space-between;padding:11px 22px;gap:14px;position:sticky;top:0;z-index:200;box-shadow:0 2px 14px rgba(0,0,0,.4)}
#topbar h1{font-size:14px;font-weight:700;letter-spacing:1.5px;white-space:nowrap;text-transform:uppercase}
.tbar-right{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.tbar-btn{font-family:'Poppins',sans-serif;font-size:12px;font-weight:500;padding:6px 14px;border-radius:6px;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.07);color:#f1f5f9;cursor:pointer;transition:.15s;white-space:nowrap;display:flex;align-items:center;gap:6px}
.tbar-btn:hover{background:rgba(255,255,255,.16);transform:translateY(-1px)}
.tbar-btn.primary{background:#1a56db;border-color:#1a56db}
.tbar-btn.primary:hover{background:#1d4ed8}
.tbar-btn.success{background:#16a34a;border-color:#16a34a}
.tbar-btn.success:hover{background:#15803d}
.tpl-pills{display:flex;gap:5px}
.tpl-pill{font-size:11px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;padding:5px 11px;border-radius:20px;border:1px solid rgba(255,255,255,.18);background:transparent;color:#94a3b8;cursor:pointer;transition:.15s;font-family:'Poppins',sans-serif}
.tpl-pill.active{background:#1a56db;border-color:#1a56db;color:#fff}
.tpl-pill:hover:not(.active){background:rgba(255,255,255,.1);color:#f1f5f9}

/* ── LAYOUT ── */
#app{display:flex;flex:1;min-height:0}

/* ── EDITOR ── */
#editor{width:320px;min-width:260px;background:#1e293b;overflow-y:auto;padding:18px 16px;flex-shrink:0}
#editor h2{font-size:10px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:#475569;margin:22px 0 8px;padding-bottom:5px;border-bottom:1px solid rgba(255,255,255,.07)}
#editor h2:first-child{margin-top:0}
.field-group{margin-bottom:9px}
.field-group label{display:block;font-size:10.5px;color:#94a3b8;margin-bottom:3px;font-weight:500}
.field-group input,.field-group textarea{width:100%;background:#0f172a;border:1px solid rgba(255,255,255,.08);color:#f1f5f9;font-family:'Poppins',sans-serif;font-size:12px;padding:7px 9px;border-radius:5px;outline:none;resize:vertical;transition:border-color .15s}
.field-group input:focus,.field-group textarea:focus{border-color:#1a56db;box-shadow:0 0 0 2px rgba(26,86,219,.2)}
.field-group textarea{min-height:64px;line-height:1.5}
.add-btn{width:100%;margin-top:5px;padding:7px;background:rgba(26,86,219,.12);border:1px dashed rgba(26,86,219,.4);color:#93c5fd;font-size:11.5px;font-weight:600;border-radius:5px;cursor:pointer;transition:.15s;font-family:'Poppins',sans-serif}
.add-btn:hover{background:rgba(26,86,219,.22);border-style:solid}
.remove-btn{font-size:10px;color:#ef4444;background:none;border:none;cursor:pointer;margin-left:4px;opacity:.6;font-weight:700}
.remove-btn:hover{opacity:1}
.exp-item{border:1px solid rgba(255,255,255,.06);border-radius:7px;padding:9px;margin-bottom:7px;background:rgba(255,255,255,.015)}
.exp-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
.exp-header span{font-size:11px;color:#94a3b8;font-weight:600}

/* photo upload */
.photo-upload-area{border:2px dashed rgba(26,86,219,.4);border-radius:8px;padding:14px;text-align:center;cursor:pointer;transition:.15s;margin-bottom:10px;position:relative}
.photo-upload-area:hover{border-color:#1a56db;background:rgba(26,86,219,.08)}
.photo-upload-area input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;z-index:2}
.photo-preview-img{width:64px;height:64px;border-radius:50%;object-fit:cover;margin:0 auto 7px;display:block;border:2px solid #1a56db}
.photo-no-img{width:64px;height:64px;border-radius:50%;background:#0f172a;margin:0 auto 7px;display:flex;align-items:center;justify-content:center;color:#334155;font-size:24px}
.photo-upload-area p{font-size:10.5px;color:#94a3b8}
.remove-photo-btn{position:relative;z-index:3;margin-top:7px;font-size:10px;color:#ef4444;background:none;border:none;cursor:pointer;font-family:'Poppins',sans-serif;display:none}

/* ── PREVIEW ── */
#preview-wrap{flex:1;overflow-y:auto;padding:28px;display:flex;justify-content:center;align-items:flex-start;background:#d1d5db}
#paper{width:794px;min-height:1123px;background:#fff;box-shadow:0 4px 40px rgba(0,0,0,.25);position:relative;overflow:hidden}

/* ── PDF Loading overlay ── */
#pdf-loading{display:none;position:fixed;inset:0;background:rgba(0,0,0,.78);z-index:9999;align-items:center;justify-content:center;flex-direction:column;gap:14px;color:#fff;font-family:'Poppins',sans-serif;font-size:13px}
#pdf-loading.show{display:flex}
.spinner{width:38px;height:38px;border:3px solid rgba(255,255,255,.2);border-top-color:#fff;border-radius:50%;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* scrollbar */
#editor::-webkit-scrollbar{width:4px}
#editor::-webkit-scrollbar-thumb{background:#334155;border-radius:2px}
#preview-wrap::-webkit-scrollbar{width:6px}
#preview-wrap::-webkit-scrollbar-thumb{background:#9ca3af;border-radius:3px}

/* ═══ TEMPLATE 1 — MODERN ═══ */
[data-tpl="1"] #paper{font-family:'Poppins',sans-serif}
[data-tpl="1"] .cv-sidebar{position:absolute;left:0;top:0;bottom:0;width:255px;background:#1e293b;padding:30px 22px;color:#f1f5f9}
[data-tpl="1"] .cv-main{margin-left:255px;padding:34px 28px}
[data-tpl="1"] .cv-photo{width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid #1a56db;display:block;margin:0 auto 14px}
[data-tpl="1"] .cv-photo-ph{width:88px;height:88px;border-radius:50%;background:#0f172a;border:3px solid #1a56db;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;color:#334155;font-size:30px}
[data-tpl="1"] .cv-name{font-size:20px;font-weight:700;line-height:1.2;color:#f1f5f9;margin-bottom:3px;text-align:center}
[data-tpl="1"] .cv-job-title{font-size:10px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;color:#93c5fd;margin-bottom:22px;text-align:center}
[data-tpl="1"] .side-sec{font-size:8.5px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#475569;margin:20px 0 9px;padding-bottom:5px;border-bottom:1px solid rgba(255,255,255,.09)}
[data-tpl="1"] .ci{display:flex;align-items:flex-start;gap:8px;margin-bottom:7px;font-size:11px;color:#cbd5e1;line-height:1.45;word-break:break-all}
[data-tpl="1"] .ci i{color:#93c5fd;font-size:11px;margin-top:2px;flex-shrink:0;width:13px;text-align:center}
[data-tpl="1"] .skill-tag{display:inline-block;font-size:10px;background:rgba(26,86,219,.3);color:#93c5fd;padding:2px 9px;border-radius:10px;margin:2px 2px 0 0}
[data-tpl="1"] .lang-row{display:flex;justify-content:space-between;font-size:11px;color:#cbd5e1;margin-bottom:5px}
[data-tpl="1"] .lang-lv{font-size:10px;color:#64748b}
[data-tpl="1"] .cert-item{font-size:10.5px;color:#cbd5e1;margin-bottom:5px;line-height:1.4}
[data-tpl="1"] .sec-title{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#1a56db;margin:24px 0 10px;display:flex;align-items:center;gap:8px}
[data-tpl="1"] .sec-title:first-child{margin-top:0}
[data-tpl="1"] .sec-title::after{content:'';flex:1;height:1px;background:#e2e8f0}
[data-tpl="1"] .summary-text{font-size:12px;color:#475569;line-height:1.7}
[data-tpl="1"] .exp-block{margin-bottom:18px}
[data-tpl="1"] .exp-role{font-size:13px;font-weight:600;color:#0f172a}
[data-tpl="1"] .exp-co{font-size:11.5px;color:#1a56db;font-weight:500}
[data-tpl="1"] .exp-dt{font-size:10px;color:#94a3b8;margin:2px 0 5px}
[data-tpl="1"] .exp-desc{font-size:11.5px;color:#475569;line-height:1.65}
[data-tpl="1"] .edu-block{margin-bottom:13px}
[data-tpl="1"] .edu-deg{font-size:12px;font-weight:600;color:#0f172a}
[data-tpl="1"] .edu-inst{font-size:11px;color:#64748b}
[data-tpl="1"] .edu-yr{font-size:10px;color:#94a3b8;margin-top:2px}

/* ═══ TEMPLATE 2 — CLASSIC ═══ */
[data-tpl="2"] #paper{font-family:'Poppins',sans-serif;padding:48px 54px}
[data-tpl="2"] .cv-sidebar{display:none}
[data-tpl="2"] .cv-main{margin-left:0}
[data-tpl="2"] .classic-top{display:flex;align-items:center;gap:22px;margin-bottom:4px}
[data-tpl="2"] .cv-photo{width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #0f172a;flex-shrink:0}
[data-tpl="2"] .cv-photo-ph{width:80px;height:80px;border-radius:50%;background:#f1f5f9;border:3px solid #0f172a;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#94a3b8;font-size:28px}
[data-tpl="2"] .cv-name{font-size:30px;font-weight:700;color:#0f172a;letter-spacing:-0.5px}
[data-tpl="2"] .cv-job-title{font-size:11px;color:#64748b;margin-top:3px;letter-spacing:1px;text-transform:uppercase;font-weight:400}
[data-tpl="2"] .classic-line{height:2px;background:#0f172a;margin:14px 0}
[data-tpl="2"] .classic-contacts{display:flex;flex-wrap:wrap;gap:4px 16px;font-size:11px;color:#475569;margin-bottom:24px}
[data-tpl="2"] .ci{display:flex;align-items:center;gap:5px}
[data-tpl="2"] .ci i{color:#0f172a;font-size:11px;width:12px;text-align:center}
[data-tpl="2"] .sec-title{font-size:13px;font-weight:700;color:#0f172a;margin:20px 0 9px;padding-bottom:5px;border-bottom:2px solid #0f172a}
[data-tpl="2"] .sec-title:first-child{margin-top:0}
[data-tpl="2"] .summary-text{font-size:12px;color:#475569;line-height:1.7}
[data-tpl="2"] .exp-block{margin-bottom:16px;display:grid;grid-template-columns:1fr auto;gap:0 12px}
[data-tpl="2"] .exp-role{font-size:13px;font-weight:600;color:#0f172a}
[data-tpl="2"] .exp-co{font-size:12px;color:#475569;font-style:italic}
[data-tpl="2"] .exp-dt{font-size:11px;color:#94a3b8;text-align:right;white-space:nowrap;margin-top:2px}
[data-tpl="2"] .exp-desc{font-size:11.5px;color:#475569;line-height:1.65;grid-column:1/-1;margin-top:5px}
[data-tpl="2"] .edu-block{margin-bottom:12px;display:grid;grid-template-columns:1fr auto}
[data-tpl="2"] .edu-deg{font-size:12px;font-weight:600;color:#0f172a}
[data-tpl="2"] .edu-inst{font-size:11px;color:#64748b}
[data-tpl="2"] .edu-yr{font-size:11px;color:#94a3b8;text-align:right}
[data-tpl="2"] .skill-tag{font-size:11px;color:#0f172a;border:1px solid #cbd5e1;padding:3px 11px;border-radius:4px;display:inline-block;margin:3px 3px 0 0}
[data-tpl="2"] .skills-wrap{margin-top:4px}
[data-tpl="2"] .lang-row{font-size:12px;color:#475569;margin-bottom:4px}
[data-tpl="2"] .cert-item{font-size:11.5px;color:#475569;margin-bottom:4px}

/* ═══ TEMPLATE 3 — MINIMAL ═══ */
[data-tpl="3"] #paper{font-family:'Poppins',sans-serif;padding:52px 60px}
[data-tpl="3"] .cv-sidebar{display:none}
[data-tpl="3"] .cv-main{margin-left:0}
[data-tpl="3"] .minimal-head{display:flex;align-items:center;gap:20px;margin-bottom:6px}
[data-tpl="3"] .cv-photo{width:70px;height:70px;border-radius:6px;object-fit:cover;flex-shrink:0}
[data-tpl="3"] .cv-photo-ph{width:70px;height:70px;border-radius:6px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#cbd5e1;font-size:26px}
[data-tpl="3"] .cv-name{font-size:24px;font-weight:700;color:#0f172a;letter-spacing:-0.5px}
[data-tpl="3"] .cv-job-title{font-size:11px;color:#94a3b8;margin-top:4px;letter-spacing:2px;text-transform:uppercase}
[data-tpl="3"] .min-bar{width:36px;height:3px;background:#0f172a;margin:14px 0 10px}
[data-tpl="3"] .minimal-contacts{font-size:10.5px;color:#64748b;display:flex;flex-wrap:wrap;gap:4px 16px;margin-bottom:28px}
[data-tpl="3"] .ci{display:flex;align-items:center;gap:5px}
[data-tpl="3"] .ci i{color:#94a3b8;font-size:10px;width:11px;text-align:center}
[data-tpl="3"] .sec-title{font-size:8.5px;font-weight:600;letter-spacing:4px;text-transform:uppercase;color:#94a3b8;margin:24px 0 10px}
[data-tpl="3"] .sec-title:first-child{margin-top:0}
[data-tpl="3"] .summary-text{font-size:12px;color:#475569;line-height:1.75}
[data-tpl="3"] .exp-block{margin-bottom:18px;border-left:2px solid #e2e8f0;padding-left:14px}
[data-tpl="3"] .exp-role{font-size:12.5px;font-weight:600;color:#0f172a}
[data-tpl="3"] .exp-co{font-size:11px;color:#64748b}
[data-tpl="3"] .exp-dt{font-size:10px;color:#94a3b8;margin:2px 0 5px}
[data-tpl="3"] .exp-desc{font-size:11px;color:#475569;line-height:1.7}
[data-tpl="3"] .edu-block{margin-bottom:12px;border-left:2px solid #e2e8f0;padding-left:14px}
[data-tpl="3"] .edu-deg{font-size:12px;font-weight:600;color:#0f172a}
[data-tpl="3"] .edu-inst{font-size:11px;color:#64748b}
[data-tpl="3"] .edu-yr{font-size:10px;color:#94a3b8;margin-top:2px}
[data-tpl="3"] .skill-tag{display:inline-block;font-size:10.5px;background:#f1f5f9;color:#475569;padding:2px 9px;border-radius:3px;margin:2px}
[data-tpl="3"] .lang-row{font-size:11px;color:#475569;margin-bottom:4px}
[data-tpl="3"] .cert-item{font-size:11px;color:#475569;margin-bottom:4px}

/* ═══ TEMPLATE 4 — BOLD ═══ */
[data-tpl="4"] #paper{font-family:'Poppins',sans-serif}
[data-tpl="4"] .cv-sidebar{display:none}
[data-tpl="4"] .cv-main{margin-left:0}
[data-tpl="4"] .bold-header{background:#0f172a;padding:30px 44px 24px}
[data-tpl="4"] .bold-head-inner{display:flex;align-items:center;gap:22px}
[data-tpl="4"] .cv-photo{width:84px;height:84px;border-radius:50%;object-fit:cover;border:3px solid #1a56db;flex-shrink:0}
[data-tpl="4"] .cv-photo-ph{width:84px;height:84px;border-radius:50%;background:#1e293b;border:3px solid #1a56db;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#334155;font-size:28px}
[data-tpl="4"] .cv-name{font-size:28px;font-weight:800;color:#fff;letter-spacing:-1px;line-height:1}
[data-tpl="4"] .cv-job-title{font-size:11px;letter-spacing:2.5px;text-transform:uppercase;color:#93c5fd;margin-top:6px;font-weight:500}
[data-tpl="4"] .bold-contact-row{display:flex;flex-wrap:wrap;gap:5px 20px;margin-top:16px}
[data-tpl="4"] .ci{font-size:11px;color:#94a3b8;display:flex;align-items:center;gap:5px}
[data-tpl="4"] .ci i{color:#1a56db;font-size:11px;width:12px;text-align:center}
[data-tpl="4"] .bold-body{padding:30px 44px}
[data-tpl="4"] .bold-cols{display:grid;grid-template-columns:1fr 1.7fr;gap:36px}
[data-tpl="4"] .bold-left-sec{margin-bottom:22px}
[data-tpl="4"] .sec-title{font-size:9.5px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:#0f172a;margin-bottom:12px;padding-bottom:7px;border-bottom:3px solid #1a56db;display:inline-block}
[data-tpl="4"] .summary-text{font-size:12.5px;color:#475569;line-height:1.7;margin-bottom:22px}
[data-tpl="4"] .exp-block{margin-bottom:20px}
[data-tpl="4"] .exp-role{font-size:13px;font-weight:600;color:#0f172a}
[data-tpl="4"] .exp-co{font-size:11px;color:#1a56db;font-weight:600;letter-spacing:.4px}
[data-tpl="4"] .exp-dt{font-size:10px;color:#94a3b8;margin:2px 0 5px;font-weight:500}
[data-tpl="4"] .exp-desc{font-size:11.5px;color:#475569;line-height:1.65}
[data-tpl="4"] .edu-block{margin-bottom:12px}
[data-tpl="4"] .edu-deg{font-size:12px;font-weight:600;color:#0f172a}
[data-tpl="4"] .edu-inst{font-size:11px;color:#64748b}
[data-tpl="4"] .edu-yr{font-size:10px;color:#94a3b8;margin-top:2px}
[data-tpl="4"] .skill-tag{display:inline-block;font-size:10px;font-weight:600;letter-spacing:.3px;background:#eff6ff;color:#1d4ed8;padding:3px 9px;border-radius:4px;margin:2px 2px 0 0;border:1px solid #bfdbfe}
[data-tpl="4"] .lang-row{font-size:11.5px;color:#475569;margin-bottom:5px;display:flex;justify-content:space-between;align-items:center}
[data-tpl="4"] .lang-dot{display:inline-block;width:6px;height:6px;border-radius:50%;background:#1a56db;margin-right:5px;vertical-align:middle}
[data-tpl="4"] .cert-item{font-size:11px;color:#475569;margin-bottom:5px;line-height:1.5}
</style>
</head>
<body data-tpl="1">

<div id="pdf-loading"><div class="spinner"></div><span id="pdf-msg">Generating PDF...</span></div>

<!-- TOPBAR -->
<div id="topbar">
  <h1><i class="fa-solid fa-file-lines" style="margin-right:7px"></i>CV Builder</h1>
  <div class="tbar-right">
    <div class="tpl-pills">
      <button class="tpl-pill active" onclick="setTpl(1)">Modern</button>
      <button class="tpl-pill" onclick="setTpl(2)">Classic</button>
      <button class="tpl-pill" onclick="setTpl(3)">Minimal</button>
      <button class="tpl-pill" onclick="setTpl(4)">Bold</button>
    </div>
    <button class="tbar-btn primary" onclick="downloadPDF()"><i class="fa-solid fa-file-pdf"></i> PDF</button>
    <button class="tbar-btn success" onclick="downloadDOCX()"><i class="fa-solid fa-file-word"></i> DOCX</button>
    <button class="tbar-btn" onclick="saveData()"><i class="fa-solid fa-floppy-disk"></i> <span id="save-lbl">Save</span></button>
  </div>
</div>

<div id="app">
<!-- EDITOR -->
<div id="editor">

  <h2>Photo</h2>
  <div class="photo-upload-area" id="photo-drop">
    <input type="file" accept="image/*" id="photo-input" onchange="handlePhoto(event)"/>
    <div id="photo-preview-area">
      <div class="photo-no-img"><i class="fa-solid fa-user"></i></div>
      <p>Click to upload photo</p>
    </div>
    <button class="remove-photo-btn" id="remove-photo-btn" onclick="removePhoto(event)"><i class="fa-solid fa-trash"></i> Remove photo</button>
  </div>

  <h2>Personal Info</h2>
  <div class="field-group"><label>Full Name</label><input id="f-name" oninput="render()"/></div>
  <div class="field-group"><label>Job Title / Designation</label><input id="f-title" oninput="render()"/></div>
  <div class="field-group"><label>Email</label><input id="f-email" oninput="render()"/></div>
  <div class="field-group"><label>Phone</label><input id="f-phone" oninput="render()"/></div>
  <div class="field-group"><label>Location</label><input id="f-location" oninput="render()"/></div>
  <div class="field-group"><label>Website</label><input id="f-website" oninput="render()"/></div>
  <div class="field-group"><label>LinkedIn</label><input id="f-linkedin" oninput="render()"/></div>
  <div class="field-group"><label>GitHub</label><input id="f-github" oninput="render()"/></div>

  <h2>Summary</h2>
  <div class="field-group"><textarea id="f-summary" rows="4" oninput="render()"></textarea></div>

  <h2>Experience <button class="add-btn" onclick="addExp()">+ Add Experience</button></h2>
  <div id="exp-list"></div>

  <h2>Education <button class="add-btn" onclick="addEdu()">+ Add Education</button></h2>
  <div id="edu-list"></div>

  <h2>Skills</h2>
  <div class="field-group"><label>Comma separated</label><textarea id="f-skills" rows="3" oninput="render()"></textarea></div>

  <h2>Languages <button class="add-btn" onclick="addLang()">+ Add Language</button></h2>
  <div id="lang-list"></div>

  <h2>Certifications</h2>
  <div class="field-group"><label>One per line</label><textarea id="f-certs" rows="3" oninput="render()"></textarea></div>

</div>

<!-- PREVIEW -->
<div id="preview-wrap">
  <div id="paper"></div>
</div>
</div>

<script>
// ═══ DATA ═══
const DEFAULT={
  photo:'',name:'Asif Mostofa Sazid',title:'Software Engineer & Web Developer',
  email:'asif83sazid@gmail.com',phone:'+880 1751 906 710',
  location:'Uttarkhan, Dhaka-1230, Bangladesh',website:'www.asifsazid.com',
  linkedin:'linkedin.com/in/sazidmostofa',github:'github.com/AsifSazid',
  summary:'Energetic, self-driven Software Engineer and Web Developer with 3+ years of professional experience building scalable web applications, crafting responsive UIs, and mentoring developers. Passionate about clean code, AI tooling, and delivering products that make a difference.',
  experiences:[
    {role:'Software Engineer & Web Developer',company:'TravHub Global Limited',date:'Jan 2025 – Present',desc:'Building and structuring web applications, creating optimized landing pages, managing brand layout assets, and maintaining mapped business information across digital mapping platforms.'},
    {role:'Web Developer',company:'Tappware Solutions Limited',date:'Sep 2024 – Jan 2025',desc:'Frontend development and specialized R&D across diverse web applications. Built responsive, user-friendly interfaces.'},
    {role:'Software Engineer & Technical Trainer',company:'PONDIT',date:'Mar 2022 – Aug 2024',desc:'Full-stack web development, coordinated project criteria within development teams, and trained junior developers in web technologies.'}
  ],
  education:[
    {degree:'MSc in Computer Science & Engineering',inst:'Uttara University',year:'Graduated 2024'},
    {degree:'BSc in Computer Science & Engineering',inst:'Uttara University',year:'CGPA 3.25 / 4.00'},
    {degree:'H.S.C. (Science)',inst:'Uttara Model College, Dhaka Board',year:'2017 – GPA 4.67'},
    {degree:'S.S.C. (Science)',inst:'Kanchkura High School, Dhaka Board',year:'2015 – GPA 4.83'}
  ],
  skills:'PHP, Laravel, JavaScript, React, HTML/CSS, Bootstrap 5, Tailwind CSS, jQuery, SASS, MySQL, Oracle, Git, GitHub, GitLab, Google Cloud, Agile, Waterfall, C, C++, Linux, Generative AI Prompt Engineering',
  languages:[{lang:'Bangla',level:'Native'},{lang:'English',level:'Intermediate'},{lang:'Hindi',level:'Elementary'}],
  certs:'Web Design & Development for Freelancing – Level 3 (NSDA)\nPHP with Laravel Framework – Batch 1 (SEIP/TRANCE-3)'
};

let data=JSON.parse(localStorage.getItem('cv_data3')||'null')||JSON.parse(JSON.stringify(DEFAULT));
let currentTpl=parseInt(localStorage.getItem('cv_tpl3')||'1');

// ═══ INIT ═══
function init(){
  document.body.setAttribute('data-tpl',currentTpl);
  document.querySelectorAll('.tpl-pill').forEach((p,i)=>p.classList.toggle('active',i+1===currentTpl));
  populateEditor();render();
}
function populateEditor(){
  ['name','title','email','phone','location','website','linkedin','github','summary','skills','certs'].forEach(k=>{
    const el=document.getElementById('f-'+k);if(el)el.value=data[k]||'';
  });
  if(data.photo)setPhotoPreview(data.photo);
  renderExpEditor();renderEduEditor();renderLangEditor();
}

// ═══ PHOTO ═══
function handlePhoto(e){
  const file=e.target.files[0];if(!file)return;
  const r=new FileReader();
  r.onload=ev=>{data.photo=ev.target.result;setPhotoPreview(data.photo);render();};
  r.readAsDataURL(file);
}
function setPhotoPreview(src){
  document.getElementById('photo-preview-area').innerHTML=`<img src="${src}" class="photo-preview-img"/>`;
  document.getElementById('remove-photo-btn').style.display='block';
}
function removePhoto(e){
  e.preventDefault();e.stopPropagation();
  data.photo='';
  document.getElementById('photo-preview-area').innerHTML=`<div class="photo-no-img"><i class="fa-solid fa-user"></i></div><p>Click to upload photo</p>`;
  document.getElementById('remove-photo-btn').style.display='none';
  document.getElementById('photo-input').value='';
  render();
}
function photoEl(cls,phCls){
  return data.photo?`<img src="${data.photo}" class="${cls}"/>`:`<div class="${phCls}"><i class="fa-solid fa-user"></i></div>`;
}

// ═══ COLLECT ═══
function collect(){
  ['name','title','email','phone','location','website','linkedin','github','summary','skills','certs'].forEach(k=>{
    const el=document.getElementById('f-'+k);if(el)data[k]=el.value;
  });
  data.experiences.forEach((e,i)=>{
    ['role','company','date','desc'].forEach(f=>{const el=document.getElementById(`exp-${f}-${i}`);if(el)e[f]=el.value;});
  });
  data.education.forEach((e,i)=>{
    ['degree','inst','year'].forEach(f=>{const el=document.getElementById(`edu-${f}-${i}`);if(el)e[f]=el.value;});
  });
  data.languages.forEach((l,i)=>{
    const n=document.getElementById(`lang-lang-${i}`),lv=document.getElementById(`lang-level-${i}`);
    if(n)l.lang=n.value;if(lv)l.level=lv.value;
  });
}
const esc=s=>String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
const ifv=(v,h)=>v&&String(v).trim()?h:'';
const ci=(icon,val)=>val&&val.trim()?`<div class="ci"><i class="${icon}"></i><span>${esc(val)}</span></div>`:'';

// ═══ EDITOR BUILDERS ═══
function renderExpEditor(){
  document.getElementById('exp-list').innerHTML=data.experiences.map((e,i)=>`
    <div class="exp-item">
      <div class="exp-header"><span>Experience ${i+1}</span><button class="remove-btn" onclick="removeExp(${i})">✕ Remove</button></div>
      <div class="field-group"><label>Role</label><input id="exp-role-${i}" value="${esc(e.role)}" oninput="render()"/></div>
      <div class="field-group"><label>Company</label><input id="exp-company-${i}" value="${esc(e.company)}" oninput="render()"/></div>
      <div class="field-group"><label>Date</label><input id="exp-date-${i}" value="${esc(e.date)}" oninput="render()"/></div>
      <div class="field-group"><label>Description</label><textarea id="exp-desc-${i}" oninput="render()">${esc(e.desc)}</textarea></div>
    </div>`).join('');
}
function renderEduEditor(){
  document.getElementById('edu-list').innerHTML=data.education.map((e,i)=>`
    <div class="exp-item">
      <div class="exp-header"><span>Edu ${i+1}</span><button class="remove-btn" onclick="removeEdu(${i})">✕ Remove</button></div>
      <div class="field-group"><label>Degree</label><input id="edu-degree-${i}" value="${esc(e.degree)}" oninput="render()"/></div>
      <div class="field-group"><label>Institution</label><input id="edu-inst-${i}" value="${esc(e.inst)}" oninput="render()"/></div>
      <div class="field-group"><label>Year / Result</label><input id="edu-year-${i}" value="${esc(e.year)}" oninput="render()"/></div>
    </div>`).join('');
}
function renderLangEditor(){
  document.getElementById('lang-list').innerHTML=data.languages.map((l,i)=>`
    <div class="exp-item">
      <div class="exp-header"><span>Lang ${i+1}</span><button class="remove-btn" onclick="removeLang(${i})">✕ Remove</button></div>
      <div class="field-group"><label>Language</label><input id="lang-lang-${i}" value="${esc(l.lang)}" oninput="render()"/></div>
      <div class="field-group"><label>Level</label><input id="lang-level-${i}" value="${esc(l.level)}" oninput="render()"/></div>
    </div>`).join('');
}
function addExp(){collect();data.experiences.push({role:'',company:'',date:'',desc:''});renderExpEditor();render();}
function removeExp(i){collect();data.experiences.splice(i,1);renderExpEditor();render();}
function addEdu(){collect();data.education.push({degree:'',inst:'',year:''});renderEduEditor();render();}
function removeEdu(i){collect();data.education.splice(i,1);renderEduEditor();render();}
function addLang(){collect();data.languages.push({lang:'',level:''});renderLangEditor();render();}
function removeLang(i){collect();data.languages.splice(i,1);renderLangEditor();render();}

// ═══ RENDER ═══
function render(){
  collect();
  const t=currentTpl,d=data;
  const skills=d.skills.split(',').map(s=>s.trim()).filter(Boolean);
  const certs=d.certs.split('\n').map(s=>s.trim()).filter(Boolean);
  const exps=d.experiences.filter(e=>e.role||e.company);
  const edus=d.education.filter(e=>e.degree);
  const langs=d.languages.filter(l=>l.lang);
  let html='';

  if(t===1){
    const sideC=[ci('fa-solid fa-envelope',d.email),ci('fa-solid fa-phone',d.phone),ci('fa-solid fa-location-dot',d.location),ci('fa-solid fa-globe',d.website),ci('fa-brands fa-linkedin',d.linkedin),ci('fa-brands fa-github',d.github)].filter(Boolean).join('');
    html=`
    <div class="cv-sidebar">
      ${photoEl('cv-photo','cv-photo-ph')}
      ${ifv(d.name,`<div class="cv-name">${esc(d.name)}</div>`)}
      ${ifv(d.title,`<div class="cv-job-title">${esc(d.title)}</div>`)}
      ${sideC?`<div class="side-sec">Contact</div>${sideC}`:''}
      ${skills.length?`<div class="side-sec">Skills</div>${skills.map(s=>`<span class="skill-tag">${esc(s)}</span>`).join('')}`:''}
      ${langs.length?`<div class="side-sec">Languages</div>${langs.map(l=>`<div class="lang-row"><span>${esc(l.lang)}</span>${ifv(l.level,`<span class="lang-lv">${esc(l.level)}</span>`)}</div>`).join('')}`:''}
      ${certs.length?`<div class="side-sec">Certifications</div>${certs.map(c=>`<div class="cert-item"><i class="fa-solid fa-circle-check" style="color:#93c5fd;font-size:10px;margin-right:5px"></i>${esc(c)}</div>`).join('')}`:''}
    </div>
    <div class="cv-main">
      ${ifv(d.summary,`<div class="sec-title">Profile</div><div class="summary-text">${esc(d.summary)}</div>`)}
      ${exps.length?`<div class="sec-title">Experience</div>${exps.map(e=>`<div class="exp-block">${ifv(e.role,`<div class="exp-role">${esc(e.role)}</div>`)}${ifv(e.company,`<div class="exp-co">${esc(e.company)}</div>`)}${ifv(e.date,`<div class="exp-dt"><i class="fa-regular fa-calendar" style="margin-right:4px"></i>${esc(e.date)}</div>`)}${ifv(e.desc,`<div class="exp-desc">${esc(e.desc)}</div>`)}</div>`).join('')}`:''}
      ${edus.length?`<div class="sec-title">Education</div>${edus.map(e=>`<div class="edu-block">${ifv(e.degree,`<div class="edu-deg">${esc(e.degree)}</div>`)}${ifv(e.inst,`<div class="edu-inst">${esc(e.inst)}</div>`)}${ifv(e.year,`<div class="edu-yr">${esc(e.year)}</div>`)}</div>`).join('')}`:''}
    </div>`;

  }else if(t===2){
    const cArr=[ci('fa-solid fa-envelope',d.email),ci('fa-solid fa-phone',d.phone),ci('fa-solid fa-location-dot',d.location),ci('fa-solid fa-globe',d.website),ci('fa-brands fa-linkedin',d.linkedin),ci('fa-brands fa-github',d.github)].filter(Boolean);
    html=`<div class="cv-main">
      <div class="classic-top">
        ${photoEl('cv-photo','cv-photo-ph')}
        <div>${ifv(d.name,`<div class="cv-name">${esc(d.name)}</div>`)}${ifv(d.title,`<div class="cv-job-title">${esc(d.title)}</div>`)}</div>
      </div>
      <div class="classic-line"></div>
      ${cArr.length?`<div class="classic-contacts">${cArr.join('')}</div>`:''}
      ${ifv(d.summary,`<div class="sec-title">Professional Summary</div><div class="summary-text">${esc(d.summary)}</div>`)}
      ${exps.length?`<div class="sec-title">Professional Experience</div>${exps.map(e=>`<div class="exp-block"><div>${ifv(e.role,`<div class="exp-role">${esc(e.role)}</div>`)}${ifv(e.company,`<div class="exp-co">${esc(e.company)}</div>`)}</div>${ifv(e.date,`<div class="exp-dt">${esc(e.date)}</div>`)}${ifv(e.desc,`<div class="exp-desc">${esc(e.desc)}</div>`)}</div>`).join('')}`:''}
      ${edus.length?`<div class="sec-title">Education</div>${edus.map(e=>`<div class="edu-block"><div>${ifv(e.degree,`<div class="edu-deg">${esc(e.degree)}</div>`)}${ifv(e.inst,`<div class="edu-inst">${esc(e.inst)}</div>`)}</div>${ifv(e.year,`<div class="edu-yr">${esc(e.year)}</div>`)}</div>`).join('')}`:''}
      ${skills.length?`<div class="sec-title">Skills</div><div class="skills-wrap">${skills.map(s=>`<span class="skill-tag">${esc(s)}</span>`).join('')}</div>`:''}
      ${langs.length?`<div class="sec-title">Languages</div>${langs.map(l=>`<div class="lang-row"><i class="fa-solid fa-language" style="margin-right:5px;color:#64748b;font-size:11px"></i>${esc(l.lang)}${l.level?` — <span style="color:#94a3b8">${esc(l.level)}</span>`:''}</div>`).join('')}`:''}
      ${certs.length?`<div class="sec-title">Certifications</div>${certs.map(c=>`<div class="cert-item"><i class="fa-solid fa-certificate" style="color:#0f172a;font-size:10px;margin-right:5px"></i>${esc(c)}</div>`).join('')}`:''}
    </div>`;

  }else if(t===3){
    const cArr=[ci('fa-solid fa-envelope',d.email),ci('fa-solid fa-phone',d.phone),ci('fa-solid fa-location-dot',d.location),ci('fa-solid fa-globe',d.website),ci('fa-brands fa-linkedin',d.linkedin),ci('fa-brands fa-github',d.github)].filter(Boolean);
    html=`<div class="cv-main">
      <div class="minimal-head">
        ${photoEl('cv-photo','cv-photo-ph')}
        <div>${ifv(d.name,`<div class="cv-name">${esc(d.name)}</div>`)}${ifv(d.title,`<div class="cv-job-title">${esc(d.title)}</div>`)}</div>
      </div>
      <div class="min-bar"></div>
      ${cArr.length?`<div class="minimal-contacts">${cArr.join('')}</div>`:''}
      ${ifv(d.summary,`<div class="sec-title">— Profile</div><div class="summary-text">${esc(d.summary)}</div>`)}
      ${exps.length?`<div class="sec-title">— Experience</div>${exps.map(e=>`<div class="exp-block">${ifv(e.role,`<div class="exp-role">${esc(e.role)}</div>`)}${ifv(e.company,`<div class="exp-co">${esc(e.company)}</div>`)}${ifv(e.date,`<div class="exp-dt">${esc(e.date)}</div>`)}${ifv(e.desc,`<div class="exp-desc">${esc(e.desc)}</div>`)}</div>`).join('')}`:''}
      ${edus.length?`<div class="sec-title">— Education</div>${edus.map(e=>`<div class="edu-block">${ifv(e.degree,`<div class="edu-deg">${esc(e.degree)}</div>`)}${ifv(e.inst,`<div class="edu-inst">${esc(e.inst)}</div>`)}${ifv(e.year,`<div class="edu-yr">${esc(e.year)}</div>`)}</div>`).join('')}`:''}
      ${skills.length?`<div class="sec-title">— Skills</div><div>${skills.map(s=>`<span class="skill-tag">${esc(s)}</span>`).join('')}</div>`:''}
      ${langs.length?`<div class="sec-title">— Languages</div>${langs.map(l=>`<div class="lang-row">${esc(l.lang)}${l.level?` <span style="color:#94a3b8">/ ${esc(l.level)}</span>`:''}</div>`).join('')}`:''}
      ${certs.length?`<div class="sec-title">— Certifications</div>${certs.map(c=>`<div class="cert-item">— ${esc(c)}</div>`).join('')}`:''}
    </div>`;

  }else if(t===4){
    const cArr=[ci('fa-solid fa-envelope',d.email),ci('fa-solid fa-phone',d.phone),ci('fa-solid fa-location-dot',d.location),ci('fa-solid fa-globe',d.website),ci('fa-brands fa-linkedin',d.linkedin),ci('fa-brands fa-github',d.github)].filter(Boolean);
    html=`
    <div class="bold-header">
      <div class="bold-head-inner">
        ${photoEl('cv-photo','cv-photo-ph')}
        <div>${ifv(d.name,`<div class="cv-name">${esc(d.name)}</div>`)}${ifv(d.title,`<div class="cv-job-title">${esc(d.title)}</div>`)}</div>
      </div>
      ${cArr.length?`<div class="bold-contact-row">${cArr.join('')}</div>`:''}
    </div>
    <div class="bold-body">
      ${ifv(d.summary,`<div class="summary-text">${esc(d.summary)}</div>`)}
      <div class="bold-cols">
        <div>
          ${edus.length?`<div class="bold-left-sec"><div class="sec-title">Education</div>${edus.map(e=>`<div class="edu-block">${ifv(e.degree,`<div class="edu-deg">${esc(e.degree)}</div>`)}${ifv(e.inst,`<div class="edu-inst">${esc(e.inst)}</div>`)}${ifv(e.year,`<div class="edu-yr">${esc(e.year)}</div>`)}</div>`).join('')}</div>`:''}
          ${skills.length?`<div class="bold-left-sec"><div class="sec-title">Skills</div>${skills.map(s=>`<span class="skill-tag">${esc(s)}</span>`).join('')}</div>`:''}
          ${langs.length?`<div class="bold-left-sec"><div class="sec-title">Languages</div>${langs.map(l=>`<div class="lang-row"><span><span class="lang-dot"></span>${esc(l.lang)}</span><span>${esc(l.level)}</span></div>`).join('')}</div>`:''}
          ${certs.length?`<div class="bold-left-sec"><div class="sec-title">Certifications</div>${certs.map(c=>`<div class="cert-item"><i class="fa-solid fa-arrow-right" style="color:#1a56db;font-size:9px;margin-right:5px"></i>${esc(c)}</div>`).join('')}</div>`:''}
        </div>
        <div>
          ${exps.length?`<div class="sec-title">Experience</div>${exps.map(e=>`<div class="exp-block">${ifv(e.role,`<div class="exp-role">${esc(e.role)}</div>`)}${ifv(e.company,`<div class="exp-co">${esc(e.company)}</div>`)}${ifv(e.date,`<div class="exp-dt"><i class="fa-regular fa-calendar" style="margin-right:4px"></i>${esc(e.date)}</div>`)}${ifv(e.desc,`<div class="exp-desc">${esc(e.desc)}</div>`)}</div>`).join('')}`:''}
        </div>
      </div>
    </div>`;
  }
  document.getElementById('paper').innerHTML=html;
}

// ═══ TEMPLATE SWITCH ═══
function setTpl(n){
  currentTpl=n;document.body.setAttribute('data-tpl',n);
  document.querySelectorAll('.tpl-pill').forEach((p,i)=>p.classList.toggle('active',i+1===n));
  localStorage.setItem('cv_tpl3',n);render();
}

// ═══ SAVE ═══
function saveData(){
  collect();localStorage.setItem('cv_data3',JSON.stringify(data));
  const lbl=document.getElementById('save-lbl');lbl.textContent='Saved!';
  setTimeout(()=>lbl.textContent='Save',1600);
}

// ═══ PDF — html2canvas + jsPDF (preserves all colors) ═══
async function downloadPDF(){
  collect();
  const loading=document.getElementById('pdf-loading');
  const msg=document.getElementById('pdf-msg');
  loading.classList.add('show');
  try{
    msg.textContent='Waiting for fonts...';
    await document.fonts.ready;
    await new Promise(r=>setTimeout(r,500));
    msg.textContent='Capturing CV...';
    const paper=document.getElementById('paper');
    const canvas=await html2canvas(paper,{
      scale:2,useCORS:true,allowTaint:true,
      backgroundColor:'#ffffff',logging:false,
      width:794,height:paper.scrollHeight,
      onclone:(doc)=>{
        // ensure background colors render
        doc.querySelectorAll('*').forEach(el=>{
          el.style.webkitPrintColorAdjust='exact';
          el.style.printColorAdjust='exact';
        });
      }
    });
    msg.textContent='Building PDF...';
    const {jsPDF}=window.jspdf;
    const pdf=new jsPDF({orientation:'portrait',unit:'mm',format:'a4'});
    const pageW=210,pageH=297;
    const pxPerMm=canvas.width/(794*25.4/96);
    const totalMm=(canvas.height/canvas.width)*pageW;
    const pages=Math.ceil(totalMm/pageH);
    for(let i=0;i<pages;i++){
      if(i>0)pdf.addPage();
      const sy=Math.round(i*pageH*canvas.width/pageW);
      const sh=Math.min(Math.round(pageH*canvas.width/pageW),canvas.height-sy);
      if(sh<=0)break;
      const sc=document.createElement('canvas');
      sc.width=canvas.width;sc.height=sh;
      sc.getContext('2d').drawImage(canvas,0,sy,canvas.width,sh,0,0,canvas.width,sh);
      const imgData=sc.toDataURL('image/jpeg',0.96);
      const rendH=(sh/canvas.width)*pageW;
      pdf.addImage(imgData,'JPEG',0,0,pageW,rendH);
    }
    msg.textContent='Saving...';
    pdf.save(`${(data.name||'CV').replace(/\s+/g,'_')}_CV.pdf`);
  }catch(err){alert('PDF error: '+err.message);console.error(err);}
  finally{loading.classList.remove('show');}
}

// ═══ DOCX ═══
async function downloadDOCX(){
  collect();
  if(typeof docx==='undefined'){alert('DOCX library loading, try again shortly.');return;}
  const{Document,Packer,Paragraph,TextRun,BorderStyle}=docx;
  const d=data;
  const skills=d.skills.split(',').map(s=>s.trim()).filter(Boolean);
  const certs=d.certs.split('\n').map(s=>s.trim()).filter(Boolean);
  const HR=()=>new Paragraph({border:{bottom:{style:BorderStyle.SINGLE,size:6,color:'1a56db'}},spacing:{after:120}});
  const H=txt=>new Paragraph({children:[new TextRun({text:txt,bold:true,size:22,color:'1a56db',font:'Calibri'})],spacing:{after:60}});
  const P=(txt,color='475569',sz=20)=>txt?new Paragraph({children:[new TextRun({text:txt,size:sz,color,font:'Calibri'})],spacing:{after:60}}):null;
  const B=txt=>new Paragraph({children:[new TextRun({text:txt,bold:true,size:22,color:'0f172a',font:'Calibri'})],spacing:{after:40}});
  const exps=d.experiences.filter(e=>e.role||e.company);
  const edus=d.education.filter(e=>e.degree);
  const langs=d.languages.filter(l=>l.lang);
  const children=[
    new Paragraph({children:[new TextRun({text:d.name||'',bold:true,size:52,color:'0f172a',font:'Calibri'})],spacing:{after:50}}),
    d.title?new Paragraph({children:[new TextRun({text:d.title,size:22,color:'1a56db',font:'Calibri'})],spacing:{after:50}}):null,
    new Paragraph({children:[new TextRun({text:[d.email,d.phone,d.location].filter(Boolean).join('  |  '),size:18,color:'64748b',font:'Calibri'})],spacing:{after:40}}),
    [d.website,d.linkedin,d.github].filter(Boolean).length?new Paragraph({children:[new TextRun({text:[d.website,d.linkedin,d.github].filter(Boolean).join('  |  '),size:18,color:'64748b',font:'Calibri'})],spacing:{after:140}}):null,
    d.summary?H('PROFILE SUMMARY'):null,d.summary?HR():null,d.summary?P(d.summary):null,
    exps.length?new Paragraph({spacing:{after:100}}):null,
    exps.length?H('EXPERIENCE'):null,exps.length?HR():null,
    ...exps.flatMap(e=>[e.role?B(e.role):null,new Paragraph({children:[new TextRun({text:e.company||'',size:20,color:'1a56db',font:'Calibri'}),new TextRun({text:e.date?`   ${e.date}`:'',size:18,color:'94a3b8',font:'Calibri'})],spacing:{after:40}}),e.desc?P(e.desc,'475569',19):null,new Paragraph({spacing:{after:80}})]),
    edus.length?H('EDUCATION'):null,edus.length?HR():null,
    ...edus.flatMap(e=>[B(e.degree),new Paragraph({children:[new TextRun({text:e.inst||'',size:19,color:'64748b',font:'Calibri'}),new TextRun({text:e.year?`   ${e.year}`:'',size:18,color:'94a3b8',font:'Calibri'})],spacing:{after:80}})]),
    skills.length?H('SKILLS'):null,skills.length?HR():null,skills.length?P(skills.join('  •  '),'475569',19):null,
    langs.length?H('LANGUAGES'):null,langs.length?HR():null,
    ...langs.map(l=>P(`${l.lang}${l.level?' — '+l.level:''}`,'475569',19)),
    certs.length?H('CERTIFICATIONS'):null,certs.length?HR():null,
    ...certs.map(c=>P('• '+c,'475569',19))
  ].filter(Boolean);
  const doc=new Document({sections:[{properties:{page:{size:{width:12240,height:15840},margin:{top:720,right:1080,bottom:720,left:1080}}},children}]});
  const blob=await Packer.toBlob(doc);
  const url=URL.createObjectURL(blob);
  const a=document.createElement('a');
  a.href=url;a.download=`${(d.name||'CV').replace(/\s+/g,'_')}_CV.docx`;
  document.body.appendChild(a);a.click();document.body.removeChild(a);URL.revokeObjectURL(url);
}

init();
</script>
</body>
</html>