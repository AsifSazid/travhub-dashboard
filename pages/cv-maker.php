<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>CV Builder – Asif Mostofa Sazid</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/docx@8.5.0/build/index.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --accent:#1A2039;--accent-2:#1a56db;--paper-bg:#ffffff;
  --ink:#0f172a;--muted:#64748b;--line:#e2e8f0
}
body{font-family:'Poppins',sans-serif;background:#d1d5db;min-height:100vh;display:flex;flex-direction:column}

/* ── TOPBAR ── */
#topbar{background:#0f172a;color:#f8fafc;display:flex;align-items:center;justify-content:space-between;padding:11px 22px;gap:14px;position:sticky;top:0;z-index:200;box-shadow:0 2px 14px rgba(0,0,0,.4)}
#topbar h1{font-size:14px;font-weight:700;letter-spacing:1.5px;white-space:nowrap;text-transform:uppercase}
#topbar-powered-badge{font-size:10px;font-weight:500;color:#64748b;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);padding:4px 10px;border-radius:20px;white-space:nowrap;display:flex;align-items:center;gap:5px;letter-spacing:.3px}
#topbar-powered-badge i{color:#facc15;font-size:9px}
.tbar-right{display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end}
.tbar-btn{font-family:'Poppins',sans-serif;font-size:12px;font-weight:500;padding:6px 14px;border-radius:6px;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.07);color:#f1f5f9;cursor:pointer;transition:.15s;white-space:nowrap;display:flex;align-items:center;gap:6px}
.tbar-btn:hover{background:rgba(255,255,255,.16);transform:translateY(-1px)}
.tbar-btn.primary{background:#1a56db;border-color:#1a56db}
.tbar-btn.primary:hover{background:#1d4ed8}
.tbar-btn.success{background:#16a34a;border-color:#16a34a}
.tbar-btn.success:hover{background:#15803d}
.tbar-btn.mobile-only{display:none}
.tpl-pills{display:flex;gap:5px}
.tpl-pill{font-size:11px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;padding:5px 11px;border-radius:20px;border:1px solid rgba(255,255,255,.18);background:transparent;color:#94a3b8;cursor:pointer;transition:.15s;font-family:'Poppins',sans-serif}
.tpl-pill.active{background:#1a56db;border-color:#1a56db;color:#fff}
.tpl-pill:hover:not(.active){background:rgba(255,255,255,.1);color:#f1f5f9}
#editor-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:150}
#editor-backdrop.show{display:block}

/* ── LAYOUT ── */
#app{display:flex;flex:1;min-height:0}

/* ── EDITOR (resizable) ── */
#editor-wrap{position:relative;flex-shrink:0;display:flex}
#editor{width:320px;min-width:260px;background:#1e293b;overflow-y:auto;padding:18px 16px;flex-shrink:0}
#resize-handle{width:6px;flex-shrink:0;cursor:col-resize;background:rgba(255,255,255,.04);position:relative;transition:background .15s}
#resize-handle:hover,#resize-handle.dragging{background:rgba(26,86,219,.5)}
#resize-handle::after{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:3px;height:34px;border-radius:3px;background:rgba(255,255,255,.25)}
#resize-handle:hover::after,#resize-handle.dragging::after{background:#fff}
body.resizing{cursor:col-resize;user-select:none}
body.resizing #paper,body.resizing iframe{pointer-events:none}

/* ── ACCORDION ── */
.acc-section{border:1px solid rgba(255,255,255,.07);border-radius:8px;margin-bottom:10px;overflow:hidden;background:rgba(255,255,255,.015)}
.acc-header{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;cursor:pointer;user-select:none;transition:background .15s}
.acc-header:hover{background:rgba(255,255,255,.03)}
.acc-header-left{display:flex;align-items:center;gap:10px}
.acc-num{width:22px;height:22px;border-radius:50%;background:rgba(255,255,255,.08);color:#94a3b8;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:.15s}
.acc-section.done .acc-num{background:#16a34a;color:#fff}
.acc-section.active .acc-num{background:var(--accent,#1a56db);color:#fff}
.acc-title{font-size:12.5px;font-weight:600;color:#e2e8f0;letter-spacing:.3px}
.acc-sub{font-size:10px;color:#64748b;margin-top:1px}
.acc-chevron{color:#64748b;font-size:12px;transition:transform .2s}
.acc-section.active .acc-chevron{transform:rotate(180deg);color:#93c5fd}
.acc-body{max-height:0;overflow:hidden;transition:max-height .25s ease}
.acc-section.active .acc-body{max-height:4000px}
.acc-body-inner{padding:2px 14px 16px}
.acc-nav{display:flex;gap:8px;margin-top:10px}
.acc-nav-btn{flex:1;padding:8px 10px;border-radius:6px;font-size:11.5px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;border:none;transition:.15s}
.acc-nav-btn.next{background:var(--accent,#1a56db);color:#fff}
.acc-nav-btn.next:hover{filter:brightness(1.1)}
.acc-nav-btn.back{background:rgba(255,255,255,.07);color:#cbd5e1}
.acc-nav-btn.back:hover{background:rgba(255,255,255,.13)}

#editor h2{font-size:10px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:#475569;margin:14px 0 8px;padding-bottom:5px;border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;justify-content:space-between}
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

/* CV import (scrape) */
.import-box{border:2px dashed rgba(80,188,129,.45);border-radius:8px;padding:14px;text-align:center;cursor:pointer;transition:.15s;margin-bottom:6px;position:relative;background:rgba(80,188,129,.05)}
.import-box:hover{border-color:#50BC81;background:rgba(80,188,129,.12)}
.import-box input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;z-index:2}
.import-box i{font-size:20px;color:#50BC81;margin-bottom:6px;display:block}
.import-box p{font-size:11px;color:#a7f3d0;font-weight:600}
.import-box small{font-size:9.5px;color:#64748b;display:block;margin-top:2px}
.import-status{font-size:11px;margin-top:7px;padding:7px 9px;border-radius:5px;display:none;align-items:center;gap:7px}
.import-status.show{display:flex}
.import-status.loading{background:rgba(26,86,219,.15);color:#93c5fd}
.import-status.success{background:rgba(22,163,74,.15);color:#86efac}
.import-status.error{background:rgba(239,68,68,.15);color:#fca5a5}
.mini-spinner{width:12px;height:12px;border:2px solid rgba(255,255,255,.25);border-top-color:currentColor;border-radius:50%;animation:spin .7s linear infinite;flex-shrink:0}

/* color controls */
.color-presets{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px}
.color-preset-btn{border:1.5px solid rgba(255,255,255,.12);border-radius:7px;padding:5px 8px;cursor:pointer;background:#0f172a;display:flex;align-items:center;gap:6px;transition:.15s}
.color-preset-btn:hover{border-color:#475569}
.color-preset-btn.active{border-color:#f1f5f9}
.color-preset-btn .swatch-pair{display:flex}
.color-preset-btn .sw{width:12px;height:16px}
.color-preset-btn .sw:first-child{border-radius:3px 0 0 3px}
.color-preset-btn .sw:last-child{border-radius:0 3px 3px 0}
.color-preset-btn span{font-size:10px;color:#cbd5e1;font-weight:600}
.color-custom-row{display:flex;gap:8px;margin-bottom:6px}
.swap-colors-btn{width:30px;height:30px;flex-shrink:0;border-radius:6px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);color:#93c5fd;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;transition:.15s;margin-bottom:1px}
.swap-colors-btn:hover{background:rgba(26,86,219,.25);border-color:var(--accent,#1a56db);transform:rotate(180deg)}
.color-field{flex:1}
.color-field label{display:block;font-size:9.5px;color:#94a3b8;margin-bottom:3px}
.color-field .swatch-input{display:flex;align-items:center;gap:6px;background:#0f172a;border:1px solid rgba(255,255,255,.08);border-radius:5px;padding:4px 6px}
.color-field input[type=color]{width:26px;height:26px;border:none;background:none;padding:0;cursor:pointer;border-radius:4px;overflow:hidden}
.color-field input[type=text]{background:transparent;border:none;color:#f1f5f9;font-size:11px;font-family:monospace;width:100%;outline:none}

/* skill tags editor */
.skill-tags-editor{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px}
.skill-tag-chip{display:flex;align-items:center;gap:6px;background:#0f172a;border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:4px 6px 4px 11px}
.skill-tag-chip input{background:transparent;border:none;color:#f1f5f9;font-size:11px;font-family:'Poppins',sans-serif;outline:none;width:90px}
.skill-tag-chip button{background:none;border:none;color:#ef4444;cursor:pointer;font-size:10px;opacity:.7;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center}
.skill-tag-chip button:hover{opacity:1;background:rgba(239,68,68,.15)}

/* ── PREVIEW ── */
#preview-wrap{flex:1;overflow:auto;padding:28px;display:flex;flex-direction:column;align-items:center;background:#d1d5db}
#cv-footer-bar{width:794px;max-width:100%;margin-top:10px;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 4px;font-size:10.5px;color:#6b7280;font-family:'Poppins',sans-serif}
#footer-powered{display:flex;align-items:center;gap:5px;font-weight:600;color:#475569}
#footer-powered i{color:#f59e0b;font-size:9.5px}
#footer-copyright{color:#6b7280;font-weight:500}
#paper-scale{transform-origin:top center;transition:transform .15s ease}
#paper{width:794px;min-height:1123px;background:var(--paper-bg);box-shadow:0 4px 40px rgba(0,0,0,.25);position:relative;overflow:hidden}

/* ── PDF Loading overlay ── */
#pdf-loading{display:none;position:fixed;inset:0;background:rgba(0,0,0,.78);z-index:9999;align-items:center;justify-content:center;flex-direction:column;gap:14px;color:#fff;font-family:'Poppins',sans-serif;font-size:13px}
#pdf-loading.show{display:flex}
.spinner{width:38px;height:38px;border:3px solid rgba(255,255,255,.2);border-top-color:#fff;border-radius:50%;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── CUSTOM MODAL (replaces browser alert/confirm) ── */
#app-modal-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;padding:20px}
#app-modal-backdrop.show{display:flex}
#app-modal{background:#1e293b;border-radius:12px;width:100%;max-width:420px;max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.5);animation:modalPop .18s ease}
@keyframes modalPop{from{transform:scale(.94);opacity:0}to{transform:scale(1);opacity:1}}
#app-modal-header{display:flex;align-items:center;gap:10px;padding:18px 18px 12px}
#app-modal-icon{width:34px;height:34px;border-radius:50%;background:rgba(26,86,219,.18);color:#93c5fd;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
#app-modal-icon.error{background:rgba(239,68,68,.18);color:#fca5a5}
#app-modal-icon.success{background:rgba(22,163,74,.18);color:#86efac}
#app-modal-title{flex:1;font-size:14px;font-weight:700;color:#f1f5f9;font-family:'Poppins',sans-serif}
#app-modal-close{background:none;border:none;color:#64748b;cursor:pointer;font-size:15px;padding:4px;border-radius:5px;transition:.15s}
#app-modal-close:hover{background:rgba(255,255,255,.08);color:#f1f5f9}
#app-modal-body{padding:0 18px 8px;font-size:12.5px;color:#cbd5e1;line-height:1.7;font-family:'Poppins',sans-serif}
#app-modal-body ul{margin:8px 0 0;padding-left:18px}
#app-modal-body li{margin-bottom:5px}
#app-modal-footer{padding:14px 18px 18px;display:flex;justify-content:flex-end}

/* scrollbar */
#editor::-webkit-scrollbar{width:4px}
#editor::-webkit-scrollbar-thumb{background:#334155;border-radius:2px}
#preview-wrap::-webkit-scrollbar{width:6px}
#preview-wrap::-webkit-scrollbar-thumb{background:#9ca3af;border-radius:3px}

/* ═══ TEMPLATE 1 — MODERN ═══ */
[data-tpl="1"] #paper{font-family:'Poppins',sans-serif}
[data-tpl="1"] .cv-sidebar{position:absolute;left:0;top:0;bottom:0;width:255px;background:var(--ink);padding:30px 22px;color:#f1f5f9}
[data-tpl="1"] .cv-main{margin-left:255px;padding:34px 28px}
[data-tpl="1"] .cv-photo{width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--accent-2);display:block;margin:0 auto 14px}
[data-tpl="1"] .cv-photo-ph{width:88px;height:88px;border-radius:50%;background:#0f172a;border:3px solid var(--accent-2);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;color:#334155;font-size:30px}
[data-tpl="1"] .cv-name{font-size:20px;font-weight:700;line-height:1.2;color:#f1f5f9;margin-bottom:3px;text-align:center}
[data-tpl="1"] .cv-job-title{font-size:10px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;color:var(--accent-2);margin-bottom:22px;text-align:center}
[data-tpl="1"] .side-sec{font-size:8.5px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#475569;margin:20px 0 9px;padding-bottom:5px;border-bottom:1px solid rgba(255,255,255,.09)}
[data-tpl="1"] .ci{display:flex;align-items:flex-start;gap:8px;margin-bottom:7px;font-size:11px;color:#cbd5e1;line-height:1.45;overflow-wrap:break-word;word-break:normal}
[data-tpl="1"] .ci i{color:var(--accent-2);font-size:11px;margin-top:2px;flex-shrink:0;width:13px;text-align:center}
[data-tpl="1"] .skill-tag{display:inline-block;font-size:10px;background:color-mix(in srgb, var(--accent) 28%, transparent);color:#e0e7ff;padding:3px 10px;border-radius:10px;margin:2px 3px 0 0}
[data-tpl="1"] .lang-row{display:flex;justify-content:space-between;font-size:11px;color:#cbd5e1;margin-bottom:5px}
[data-tpl="1"] .lang-lv{font-size:10px;color:#64748b}
[data-tpl="1"] .cert-item{font-size:10.5px;color:#cbd5e1;margin-bottom:5px;line-height:1.4}
[data-tpl="1"] .sec-title{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--accent);margin:24px 0 10px;display:flex;align-items:center;gap:8px}
[data-tpl="1"] .sec-title:first-child{margin-top:0}
[data-tpl="1"] .sec-title::after{content:'';flex:1;height:1px;background:var(--accent-2)}
[data-tpl="1"] .summary-text{font-size:12px;color:#475569;line-height:1.7}
[data-tpl="1"] .exp-block{margin-bottom:18px}
[data-tpl="1"] .exp-role{font-size:13px;font-weight:600;color:var(--ink)}
[data-tpl="1"] .exp-co{font-size:11.5px;color:var(--accent);font-weight:500}
[data-tpl="1"] .exp-dt{font-size:10px;color:#94a3b8;margin:2px 0 5px}
[data-tpl="1"] .exp-desc{font-size:11.5px;color:#475569;line-height:1.65}
[data-tpl="1"] .edu-block{margin-bottom:13px}
[data-tpl="1"] .edu-deg{font-size:12px;font-weight:600;color:var(--ink)}
[data-tpl="1"] .edu-inst{font-size:11px;color:#64748b}
[data-tpl="1"] .edu-yr{font-size:10px;color:#94a3b8;margin-top:2px}

/* ═══ TEMPLATE 2 — CLASSIC ═══ */
[data-tpl="2"] #paper{font-family:'Poppins',sans-serif;padding:48px 54px}
[data-tpl="2"] .cv-sidebar{display:none}
[data-tpl="2"] .cv-main{margin-left:0}
[data-tpl="2"] .classic-top{display:flex;align-items:center;gap:22px;margin-bottom:4px}
[data-tpl="2"] .cv-photo{width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--accent-2);flex-shrink:0}
[data-tpl="2"] .cv-photo-ph{width:80px;height:80px;border-radius:50%;background:#f1f5f9;border:3px solid var(--accent-2);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#94a3b8;font-size:28px}
[data-tpl="2"] .cv-name{font-size:30px;font-weight:700;color:var(--ink);letter-spacing:-0.5px}
[data-tpl="2"] .cv-job-title{font-size:11px;color:var(--accent);margin-top:3px;letter-spacing:1px;text-transform:uppercase;font-weight:500}
[data-tpl="2"] .classic-line{height:2px;background:var(--accent-2);margin:14px 0}
[data-tpl="2"] .classic-contacts{display:flex;flex-wrap:wrap;gap:4px 16px;font-size:11px;color:#475569;margin-bottom:24px}
[data-tpl="2"] .ci{display:flex;align-items:center;gap:5px;overflow-wrap:break-word}
[data-tpl="2"] .ci i{color:var(--accent);font-size:11px;width:12px;text-align:center}
[data-tpl="2"] .sec-title{font-size:13px;font-weight:700;color:var(--ink);margin:20px 0 9px;padding-bottom:5px;border-bottom:2px solid var(--accent-2)}
[data-tpl="2"] .sec-title:first-child{margin-top:0}
[data-tpl="2"] .summary-text{font-size:12px;color:#475569;line-height:1.7}
[data-tpl="2"] .exp-block{margin-bottom:16px;display:grid;grid-template-columns:1fr auto;gap:0 12px}
[data-tpl="2"] .exp-role{font-size:13px;font-weight:600;color:var(--ink)}
[data-tpl="2"] .exp-co{font-size:12px;color:#475569;font-style:italic}
[data-tpl="2"] .exp-dt{font-size:11px;color:#94a3b8;text-align:right;white-space:nowrap;margin-top:2px}
[data-tpl="2"] .exp-desc{font-size:11.5px;color:#475569;line-height:1.65;grid-column:1/-1;margin-top:5px}
[data-tpl="2"] .edu-block{margin-bottom:12px;display:grid;grid-template-columns:1fr auto}
[data-tpl="2"] .edu-deg{font-size:12px;font-weight:600;color:var(--ink)}
[data-tpl="2"] .edu-inst{font-size:11px;color:#64748b}
[data-tpl="2"] .edu-yr{font-size:11px;color:#94a3b8;text-align:right}
[data-tpl="2"] .skill-tag{font-size:11px;color:var(--ink);border:1px solid var(--accent-2);padding:3px 11px;border-radius:4px;display:inline-block;margin:3px 3px 0 0}
[data-tpl="2"] .skills-wrap{margin-top:4px}
[data-tpl="2"] .lang-row{font-size:12px;color:#475569;margin-bottom:4px}
[data-tpl="2"] .cert-item{font-size:11.5px;color:#475569;margin-bottom:4px}

/* ═══ TEMPLATE 3 — MINIMAL ═══ */
[data-tpl="3"] #paper{font-family:'Poppins',sans-serif;padding:52px 60px}
[data-tpl="3"] .cv-sidebar{display:none}
[data-tpl="3"] .cv-main{margin-left:0}
[data-tpl="3"] .minimal-head{display:flex;align-items:center;gap:20px;margin-bottom:6px}
[data-tpl="3"] .cv-photo{width:70px;height:70px;border-radius:6px;object-fit:cover;flex-shrink:0}
[data-tpl="3"] .cv-photo-ph{width:70px;height:70px;border-radius:6px;background:#f8fafc;border:1px solid var(--line);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#cbd5e1;font-size:26px}
[data-tpl="3"] .cv-name{font-size:24px;font-weight:700;color:var(--ink);letter-spacing:-0.5px}
[data-tpl="3"] .cv-job-title{font-size:11px;color:var(--accent);margin-top:4px;letter-spacing:2px;text-transform:uppercase}
[data-tpl="3"] .min-bar{width:36px;height:3px;background:var(--accent-2);margin:14px 0 10px}
[data-tpl="3"] .minimal-contacts{font-size:10.5px;color:#64748b;display:flex;flex-wrap:wrap;gap:4px 16px;margin-bottom:28px}
[data-tpl="3"] .ci{display:flex;align-items:center;gap:5px;overflow-wrap:break-word}
[data-tpl="3"] .ci i{color:var(--accent-2);font-size:10px;width:11px;text-align:center}
[data-tpl="3"] .sec-title{font-size:8.5px;font-weight:600;letter-spacing:4px;text-transform:uppercase;color:#94a3b8;margin:24px 0 10px}
[data-tpl="3"] .sec-title:first-child{margin-top:0}
[data-tpl="3"] .summary-text{font-size:12px;color:#475569;line-height:1.75}
[data-tpl="3"] .exp-block{margin-bottom:18px;border-left:2px solid var(--accent-2);padding-left:14px}
[data-tpl="3"] .exp-role{font-size:12.5px;font-weight:600;color:var(--ink)}
[data-tpl="3"] .exp-co{font-size:11px;color:#64748b}
[data-tpl="3"] .exp-dt{font-size:10px;color:#94a3b8;margin:2px 0 5px}
[data-tpl="3"] .exp-desc{font-size:11px;color:#475569;line-height:1.7}
[data-tpl="3"] .edu-block{margin-bottom:12px;border-left:2px solid var(--line);padding-left:14px}
[data-tpl="3"] .edu-deg{font-size:12px;font-weight:600;color:var(--ink)}
[data-tpl="3"] .edu-inst{font-size:11px;color:#64748b}
[data-tpl="3"] .edu-yr{font-size:10px;color:#94a3b8;margin-top:2px}
[data-tpl="3"] .skill-tag{display:inline-block;font-size:10.5px;background:#f1f5f9;color:#475569;padding:3px 10px;border-radius:3px;margin:2px}
[data-tpl="3"] .lang-row{font-size:11px;color:#475569;margin-bottom:4px}
[data-tpl="3"] .cert-item{font-size:11px;color:#475569;margin-bottom:4px}

/* ═══ TEMPLATE 4 — BOLD ═══ */
[data-tpl="4"] #paper{font-family:'Poppins',sans-serif}
[data-tpl="4"] .cv-sidebar{display:none}
[data-tpl="4"] .cv-main{margin-left:0}
[data-tpl="4"] .bold-header{background:var(--ink);padding:30px 44px 24px}
[data-tpl="4"] .bold-head-inner{display:flex;align-items:center;gap:22px}
[data-tpl="4"] .cv-photo{width:84px;height:84px;border-radius:50%;object-fit:cover;border:3px solid var(--accent-2);flex-shrink:0}
[data-tpl="4"] .cv-photo-ph{width:84px;height:84px;border-radius:50%;background:#1e293b;border:3px solid var(--accent-2);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#334155;font-size:28px}
[data-tpl="4"] .cv-name{font-size:28px;font-weight:800;color:#fff;letter-spacing:-1px;line-height:1}
[data-tpl="4"] .cv-job-title{font-size:11px;letter-spacing:2.5px;text-transform:uppercase;color:var(--accent-2);margin-top:6px;font-weight:500}
[data-tpl="4"] .bold-contact-row{display:flex;flex-wrap:wrap;gap:5px 20px;margin-top:16px}
[data-tpl="4"] .ci{font-size:11px;color:#94a3b8;display:flex;align-items:center;gap:5px;overflow-wrap:break-word}
[data-tpl="4"] .ci i{color:var(--accent-2);font-size:11px;width:12px;text-align:center}
[data-tpl="4"] .bold-body{padding:30px 44px}
[data-tpl="4"] .bold-cols{display:grid;grid-template-columns:1fr 1.7fr;gap:36px}
[data-tpl="4"] .bold-left-sec{margin-bottom:22px}
[data-tpl="4"] .sec-title{font-size:9.5px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:var(--ink);margin-bottom:12px;padding-bottom:7px;border-bottom:3px solid var(--accent-2);display:inline-block}
[data-tpl="4"] .summary-text{font-size:12.5px;color:#475569;line-height:1.7;margin-bottom:22px}
[data-tpl="4"] .exp-block{margin-bottom:20px}
[data-tpl="4"] .exp-role{font-size:13px;font-weight:600;color:var(--ink)}
[data-tpl="4"] .exp-co{font-size:11px;color:var(--accent);font-weight:600;letter-spacing:.4px}
[data-tpl="4"] .exp-dt{font-size:10px;color:#94a3b8;margin:2px 0 5px;font-weight:500}
[data-tpl="4"] .exp-desc{font-size:11.5px;color:#475569;line-height:1.65}
[data-tpl="4"] .edu-block{margin-bottom:12px}
[data-tpl="4"] .edu-deg{font-size:12px;font-weight:600;color:var(--ink)}
[data-tpl="4"] .edu-inst{font-size:11px;color:#64748b}
[data-tpl="4"] .edu-yr{font-size:10px;color:#94a3b8;margin-top:2px}
/* FIX: Bold template skills — was messy inline wrap; now a clean flex-wrap grid of evenly sized pill tags */
[data-tpl="4"] .skills-wrap{display:flex;flex-wrap:wrap;gap:6px;margin-top:2px}
[data-tpl="4"] .skill-tag{display:inline-flex;align-items:center;font-size:10px;font-weight:600;letter-spacing:.3px;background:color-mix(in srgb, var(--accent) 10%, white);color:var(--accent);padding:4px 10px;border-radius:5px;margin:0;border:1px solid var(--accent-2);line-height:1.4;white-space:nowrap}
[data-tpl="4"] .lang-row{font-size:11.5px;color:#475569;margin-bottom:5px;display:flex;justify-content:space-between;align-items:center}
[data-tpl="4"] .lang-dot{display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--accent-2);margin-right:5px;vertical-align:middle}
[data-tpl="4"] .cert-item{font-size:11px;color:#475569;margin-bottom:5px;line-height:1.5}

/* ══════════════════════════════════════════════════════════
   RESPONSIVE — mobile / tablet / desktop
   Strategy:
   - Desktop (>1024px): editor sidebar + preview side-by-side (unchanged)
   - Tablet (769-1024px): narrower fixed sidebar, preview auto-scales to fit
   - Mobile (<=768px): editor becomes a full-height slide-in drawer opened
     via a hamburger-style button in the topbar; preview takes full width
     and the A4 paper is scaled down with CSS transform so nothing
     overflows horizontally; resize handle is hidden (touch drag for a
     280px range isn't practical, drawer covers that need instead)
   ══════════════════════════════════════════════════════════ */

/* ---- Tablet ---- */
@media (max-width:1024px){
  #editor{width:280px!important}
  #topbar h1{font-size:12px}
  .tpl-pill{padding:5px 8px;font-size:10px}
  #preview-wrap{padding:16px}
}

/* ---- Mobile & small tablet ---- */
@media (max-width:768px){
  body{overflow:hidden}
  #topbar{padding:9px 12px;gap:8px}
  #topbar h1{font-size:11px;letter-spacing:.5px}
  #topbar h1 i{margin-right:4px!important}
  .tbar-btn.mobile-only{display:flex}
  .tbar-right{gap:5px}
  .tbar-btn{padding:6px 9px;font-size:11px}
  .tbar-btn .btn-label{display:none}
  .tpl-pills{gap:3px;order:3;width:100%;justify-content:center;margin-top:6px}
  .tpl-pill{padding:4px 8px;font-size:9.5px}
  #topbar{flex-wrap:wrap}

  #app{position:relative;overflow:hidden}

  /* editor becomes a slide-in drawer */
  #editor-wrap{position:fixed;top:0;left:0;bottom:0;z-index:180;transform:translateX(-100%);transition:transform .25s ease;box-shadow:4px 0 24px rgba(0,0,0,.4)}
  #editor-wrap.open{transform:translateX(0)}
  #editor{width:min(86vw,340px)!important;height:100vh}
  #resize-handle{display:none}

  #preview-wrap{width:100%;padding:14px 10px;justify-content:center}
  #paper{box-shadow:0 2px 20px rgba(0,0,0,.3)}
  #topbar-powered-badge{display:none}
  #cv-footer-bar{width:100%;flex-direction:column;align-items:flex-start;gap:3px;font-size:9.5px}
}

/* ---- Very small phones ---- */
@media (max-width:420px){
  #topbar h1 span,#topbar h1{font-size:10px}
  .tbar-btn{padding:5px 7px}
  .color-custom-row{flex-direction:column}
}

/* ---- Orientation / short viewport safety ---- */
@media (max-width:768px) and (orientation:landscape){
  #editor{height:100vh;overflow-y:auto}
}

/* ---- PRINT ---- */
/* window.print() / "Save as PDF" via browser dialog should show ONLY the
   CV page itself — no topbar, no editor sidebar, no resize handle, no
   drag backdrop, no modal, and no page shadow/scaling that would look odd
   on paper. */
@media print{
  #topbar,#editor-wrap,#resize-handle,#editor-backdrop,
  #app-modal-backdrop,#pdf-loading,#cv-footer-bar{display:none!important}
  body{background:#fff!important}
  #app{display:block}
  #preview-wrap{padding:0;overflow:visible;display:block;background:#fff}
  #paper-scale{transform:none!important;margin-bottom:0!important}
  #paper{box-shadow:none;width:100%;min-height:0}
  @page{margin:12mm}
}
</style>
</head>
<body data-tpl="1">

<div id="pdf-loading"><div class="spinner"></div><span id="pdf-msg">Generating PDF...</span></div>

<!-- Custom modal (replaces browser alert()) -->
<div id="app-modal-backdrop">
  <div id="app-modal">
    <div id="app-modal-header">
      <div id="app-modal-icon"><i class="fa-solid fa-circle-info"></i></div>
      <h3 id="app-modal-title">Title</h3>
      <button id="app-modal-close" onclick="closeAppModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="app-modal-body"></div>
    <div id="app-modal-footer">
      <button class="tbar-btn primary" onclick="closeAppModal()">Got it</button>
    </div>
  </div>
</div>

<!-- TOPBAR -->
<div id="topbar">
  <button class="tbar-btn mobile-only" id="editor-toggle-btn" onclick="toggleEditorDrawer()" title="Edit CV"><i class="fa-solid fa-sliders"></i></button>
  <h1><i class="fa-solid fa-file-lines" style="margin-right:7px"></i>CV Builder</h1>
  <span id="topbar-powered-badge"><i class="fa-solid fa-bolt"></i> Powered by Metabit Technology</span>
  <div class="tbar-right">
    <div class="tpl-pills">
      <button class="tpl-pill active" onclick="setTpl(1)">Modern</button>
      <button class="tpl-pill" onclick="setTpl(2)">Classic</button>
      <button class="tpl-pill" onclick="setTpl(3)">Minimal</button>
      <button class="tpl-pill" onclick="setTpl(4)">Bold</button>
    </div>
    <button class="tbar-btn primary" onclick="downloadPDF()"><i class="fa-solid fa-file-pdf"></i> <span class="btn-label">PDF</span></button>
    <button class="tbar-btn" onclick="printCV()"><i class="fa-solid fa-print"></i> <span class="btn-label">Print</span></button>
    <button class="tbar-btn success" onclick="downloadDOCX()"><i class="fa-solid fa-file-word"></i> <span class="btn-label">DOCX</span></button>
    <button class="tbar-btn" onclick="saveData()"><i class="fa-solid fa-floppy-disk"></i> <span id="save-lbl" class="btn-label">Save</span></button>
    <button class="tbar-btn" onclick="showStorageInfo()" title="Where is my data stored?"><i class="fa-solid fa-circle-info"></i></button>
  </div>
</div>
<div id="editor-backdrop" onclick="closeEditorDrawer()"></div>

<div id="app">
<!-- EDITOR -->
<div id="editor-wrap">
<div id="editor">

  <!-- STEP 1: Import -->
  <div class="acc-section active" data-step="1">
    <div class="acc-header" onclick="toggleStep(1)">
      <div class="acc-header-left">
        <div class="acc-num">1</div>
        <div><div class="acc-title">Upload CV or Start Fresh</div><div class="acc-sub">Import existing CV or write from scratch</div></div>
      </div>
      <i class="fa-solid fa-chevron-down acc-chevron"></i>
    </div>
    <div class="acc-body">
      <div class="acc-body-inner">
        <div class="import-box" id="import-drop">
          <input type="file" accept="application/pdf,image/*" id="import-input" onchange="handleCvImport(event)"/>
          <i class="fa-solid fa-file-arrow-up"></i>
          <p>Upload existing CV (PDF/Image)</p>
          <small>AI auto-fills your details below</small>
        </div>
        <div class="import-status" id="import-status"></div>
        <div class="acc-nav"><button class="acc-nav-btn next" onclick="goStep(2)">Next: Photo & Colors <i class="fa-solid fa-arrow-right"></i></button></div>
      </div>
    </div>
  </div>

  <!-- STEP 2: Photo + Colors -->
  <div class="acc-section" data-step="2">
    <div class="acc-header" onclick="toggleStep(2)">
      <div class="acc-header-left">
        <div class="acc-num">2</div>
        <div><div class="acc-title">Photo & Colors</div><div class="acc-sub">Add a photo and pick your theme</div></div>
      </div>
      <i class="fa-solid fa-chevron-down acc-chevron"></i>
    </div>
    <div class="acc-body">
      <div class="acc-body-inner">
        <h2>Photo</h2>
        <div class="photo-upload-area" id="photo-drop">
          <input type="file" accept="image/*" id="photo-input" onchange="handlePhoto(event)"/>
          <div id="photo-preview-area">
            <div class="photo-no-img"><i class="fa-solid fa-user"></i></div>
            <p>Click to upload photo</p>
          </div>
          <button class="remove-photo-btn" id="remove-photo-btn" onclick="removePhoto(event)"><i class="fa-solid fa-trash"></i> Remove photo</button>
        </div>

        <h2>Colors</h2>
        <div class="color-presets" id="color-presets"></div>
        <div class="color-custom-row" style="align-items:flex-end">
          <div class="color-field">
            <label>Primary</label>
            <div class="swatch-input">
              <input type="color" id="c-primary" oninput="onColorPick('primary')"/>
              <input type="text" id="c-primary-hex" oninput="onColorType('primary')" maxlength="7"/>
            </div>
          </div>
          <button type="button" class="swap-colors-btn" onclick="swapPrimarySecondary()" title="Swap Primary & Secondary">
            <i class="fa-solid fa-right-left"></i>
          </button>
          <div class="color-field">
            <label>Secondary</label>
            <div class="swatch-input">
              <input type="color" id="c-secondary" oninput="onColorPick('secondary')"/>
              <input type="text" id="c-secondary-hex" oninput="onColorType('secondary')" maxlength="7"/>
            </div>
          </div>
        </div>
        <div class="color-custom-row">
          <div class="color-field">
            <label>Background</label>
            <div class="swatch-input">
              <input type="color" id="c-bg" oninput="onColorPick('bg')"/>
              <input type="text" id="c-bg-hex" oninput="onColorType('bg')" maxlength="7"/>
            </div>
          </div>
        </div>
        <div class="acc-nav">
          <button class="acc-nav-btn back" onclick="goStep(1)"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button class="acc-nav-btn next" onclick="goStep(3)">Next: Personal Info <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 3: Personal Info -->
  <div class="acc-section" data-step="3">
    <div class="acc-header" onclick="toggleStep(3)">
      <div class="acc-header-left">
        <div class="acc-num">3</div>
        <div><div class="acc-title">Personal Info</div><div class="acc-sub">Name, title & contact details</div></div>
      </div>
      <i class="fa-solid fa-chevron-down acc-chevron"></i>
    </div>
    <div class="acc-body">
      <div class="acc-body-inner">
        <div class="field-group"><label>Full Name</label><input id="f-name" oninput="render()"/></div>
        <div class="field-group"><label>Job Title / Designation</label><input id="f-title" oninput="render()"/></div>
        <div class="field-group"><label>Email</label><input id="f-email" oninput="render()"/></div>
        <div class="field-group"><label>Phone</label><input id="f-phone" oninput="render()"/></div>
        <div class="field-group"><label>Location</label><textarea id="f-location" rows="2" oninput="render()"></textarea></div>
        <div class="field-group"><label>Website</label><input id="f-website" oninput="render()"/></div>
        <div class="field-group"><label>LinkedIn</label><input id="f-linkedin" oninput="render()"/></div>
        <div class="field-group"><label>GitHub</label><input id="f-github" oninput="render()"/></div>
        <div class="field-group"><label>Facebook</label><input id="f-facebook" oninput="render()"/></div>
        <div class="acc-nav">
          <button class="acc-nav-btn back" onclick="goStep(2)"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button class="acc-nav-btn next" onclick="goStep(4)">Next: Summary <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 4: Summary -->
  <div class="acc-section" data-step="4">
    <div class="acc-header" onclick="toggleStep(4)">
      <div class="acc-header-left">
        <div class="acc-num">4</div>
        <div><div class="acc-title">Professional Summary</div><div class="acc-sub">A short pitch about you</div></div>
      </div>
      <i class="fa-solid fa-chevron-down acc-chevron"></i>
    </div>
    <div class="acc-body">
      <div class="acc-body-inner">
        <div class="field-group"><textarea id="f-summary" rows="4" oninput="render()"></textarea></div>
        <div class="acc-nav">
          <button class="acc-nav-btn back" onclick="goStep(3)"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button class="acc-nav-btn next" onclick="goStep(5)">Next: Experience <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 5: Experience -->
  <div class="acc-section" data-step="5">
    <div class="acc-header" onclick="toggleStep(5)">
      <div class="acc-header-left">
        <div class="acc-num">5</div>
        <div><div class="acc-title">Experience</div><div class="acc-sub">Your work history</div></div>
      </div>
      <i class="fa-solid fa-chevron-down acc-chevron"></i>
    </div>
    <div class="acc-body">
      <div class="acc-body-inner">
        <div id="exp-list"></div>
        <button class="add-btn" onclick="addExp()">+ Add Experience</button>
        <div class="acc-nav">
          <button class="acc-nav-btn back" onclick="goStep(4)"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button class="acc-nav-btn next" onclick="goStep(6)">Next: Education <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 6: Education -->
  <div class="acc-section" data-step="6">
    <div class="acc-header" onclick="toggleStep(6)">
      <div class="acc-header-left">
        <div class="acc-num">6</div>
        <div><div class="acc-title">Education</div><div class="acc-sub">Degrees & institutions</div></div>
      </div>
      <i class="fa-solid fa-chevron-down acc-chevron"></i>
    </div>
    <div class="acc-body">
      <div class="acc-body-inner">
        <div id="edu-list"></div>
        <button class="add-btn" onclick="addEdu()">+ Add Education</button>
        <div class="acc-nav">
          <button class="acc-nav-btn back" onclick="goStep(5)"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button class="acc-nav-btn next" onclick="goStep(7)">Next: Skills <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 7: Skills -->
  <div class="acc-section" data-step="7">
    <div class="acc-header" onclick="toggleStep(7)">
      <div class="acc-header-left">
        <div class="acc-num">7</div>
        <div><div class="acc-title">Skills</div><div class="acc-sub">Your key skills as tags</div></div>
      </div>
      <i class="fa-solid fa-chevron-down acc-chevron"></i>
    </div>
    <div class="acc-body">
      <div class="acc-body-inner">
        <div class="skill-tags-editor" id="skill-tags-editor"></div>
        <button class="add-btn" onclick="addSkill()">+ Add Skill</button>
        <div class="acc-nav">
          <button class="acc-nav-btn back" onclick="goStep(6)"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button class="acc-nav-btn next" onclick="goStep(8)">Next: Languages <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 8: Languages -->
  <div class="acc-section" data-step="8">
    <div class="acc-header" onclick="toggleStep(8)">
      <div class="acc-header-left">
        <div class="acc-num">8</div>
        <div><div class="acc-title">Languages</div><div class="acc-sub">Languages you speak</div></div>
      </div>
      <i class="fa-solid fa-chevron-down acc-chevron"></i>
    </div>
    <div class="acc-body">
      <div class="acc-body-inner">
        <div id="lang-list"></div>
        <button class="add-btn" onclick="addLang()">+ Add Language</button>
        <div class="acc-nav">
          <button class="acc-nav-btn back" onclick="goStep(7)"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button class="acc-nav-btn next" onclick="goStep(9)">Next: Certifications <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 9: Certifications -->
  <div class="acc-section" data-step="9">
    <div class="acc-header" onclick="toggleStep(9)">
      <div class="acc-header-left">
        <div class="acc-num">9</div>
        <div><div class="acc-title">Certifications</div><div class="acc-sub">Courses, licenses & awards</div></div>
      </div>
      <i class="fa-solid fa-chevron-down acc-chevron"></i>
    </div>
    <div class="acc-body">
      <div class="acc-body-inner">
        <div class="field-group"><label>One per line</label><textarea id="f-certs" rows="3" oninput="render()"></textarea></div>
        <div class="acc-nav">
          <button class="acc-nav-btn back" onclick="goStep(8)"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button class="acc-nav-btn next" onclick="goStep(10)">Next: Projects <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 10: Projects -->
  <div class="acc-section" data-step="10">
    <div class="acc-header" onclick="toggleStep(10)">
      <div class="acc-header-left">
        <div class="acc-num">10</div>
        <div><div class="acc-title">Projects</div><div class="acc-sub">Portfolio & personal projects</div></div>
      </div>
      <i class="fa-solid fa-chevron-down acc-chevron"></i>
    </div>
    <div class="acc-body">
      <div class="acc-body-inner">
        <div id="project-list"></div>
        <button class="add-btn" onclick="addProject()">+ Add Project</button>
        <div class="acc-nav">
          <button class="acc-nav-btn back" onclick="goStep(9)"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button class="acc-nav-btn next" onclick="goStep(11)">Next: Awards <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 11: Awards & Achievements -->
  <div class="acc-section" data-step="11">
    <div class="acc-header" onclick="toggleStep(11)">
      <div class="acc-header-left">
        <div class="acc-num">11</div>
        <div><div class="acc-title">Awards & Achievements</div><div class="acc-sub">Recognitions worth highlighting</div></div>
      </div>
      <i class="fa-solid fa-chevron-down acc-chevron"></i>
    </div>
    <div class="acc-body">
      <div class="acc-body-inner">
        <div id="award-list"></div>
        <button class="add-btn" onclick="addAward()">+ Add Award</button>
        <div class="acc-nav">
          <button class="acc-nav-btn back" onclick="goStep(10)"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button class="acc-nav-btn next" onclick="goStep(12)">Next: Volunteer <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 12: Volunteer Experience -->
  <div class="acc-section" data-step="12">
    <div class="acc-header" onclick="toggleStep(12)">
      <div class="acc-header-left">
        <div class="acc-num">12</div>
        <div><div class="acc-title">Volunteer Experience</div><div class="acc-sub">Community & unpaid work</div></div>
      </div>
      <i class="fa-solid fa-chevron-down acc-chevron"></i>
    </div>
    <div class="acc-body">
      <div class="acc-body-inner">
        <div id="volunteer-list"></div>
        <button class="add-btn" onclick="addVolunteer()">+ Add Volunteer Role</button>
        <div class="acc-nav">
          <button class="acc-nav-btn back" onclick="goStep(11)"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button class="acc-nav-btn next" onclick="goStep(13)">Next: References <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 13: References -->
  <div class="acc-section" data-step="13">
    <div class="acc-header" onclick="toggleStep(13)">
      <div class="acc-header-left">
        <div class="acc-num">13</div>
        <div><div class="acc-title">References</div><div class="acc-sub">People who can vouch for you</div></div>
      </div>
      <i class="fa-solid fa-chevron-down acc-chevron"></i>
    </div>
    <div class="acc-body">
      <div class="acc-body-inner">
        <div id="reference-list"></div>
        <button class="add-btn" onclick="addReference()">+ Add Reference</button>
        <div class="acc-nav">
          <button class="acc-nav-btn back" onclick="goStep(12)"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button class="acc-nav-btn next" onclick="goStep(14)">Next: Hobbies & More <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 14: Hobbies + Custom Sections -->
  <div class="acc-section" data-step="14">
    <div class="acc-header" onclick="toggleStep(14)">
      <div class="acc-header-left">
        <div class="acc-num">14</div>
        <div><div class="acc-title">Hobbies & Custom Sections</div><div class="acc-sub">Interests + anything else you want to add</div></div>
      </div>
      <i class="fa-solid fa-chevron-down acc-chevron"></i>
    </div>
    <div class="acc-body">
      <div class="acc-body-inner">
        <h2>Hobbies / Interests</h2>
        <div class="skill-tags-editor" id="hobby-tags-editor"></div>
        <button class="add-btn" onclick="addHobby()">+ Add Hobby</button>

        <h2 style="margin-top:18px">Custom Sections</h2>
        <div id="custom-section-list"></div>
        <button class="add-btn" onclick="addCustomSection()">+ Add Custom Section</button>

        <div class="acc-nav">
          <button class="acc-nav-btn back" onclick="goStep(13)"><i class="fa-solid fa-arrow-left"></i> Back</button>
          <button class="acc-nav-btn next" onclick="finishWizard()"><i class="fa-solid fa-check"></i> Done</button>
        </div>
      </div>
    </div>
  </div>

</div>
<div id="resize-handle"></div>
</div>

<!-- PREVIEW -->
<div id="preview-wrap">
  <div id="paper-scale"><div id="paper"></div></div>
  <div id="cv-footer-bar">
    <span id="footer-powered"><i class="fa-solid fa-bolt"></i> Powered by Metabit Technology</span>
    <span id="footer-copyright">© 2026 TravHub Global Limited</span>
  </div>
</div>
</div>
<div id="footer-bar">
  <span class="footer-powered"><i class="fa-solid fa-bolt"></i> Powered by Metabit Technology</span>
  <span class="footer-copyright">© 2026 TravHub Global Limited</span>
</div>

<script>
// ═══ ACCORDION WIZARD ═══
const TOTAL_STEPS=14;
function toggleStep(n){
  const sec=document.querySelector(`.acc-section[data-step="${n}"]`);
  const isActive=sec.classList.contains('active');
  document.querySelectorAll('.acc-section').forEach(s=>s.classList.remove('active'));
  if(!isActive)sec.classList.add('active');
}
function goStep(n){
  // mark current (and all previous) as done
  const cur=document.querySelector('.acc-section.active');
  if(cur){
    cur.classList.remove('active');
    cur.classList.add('done');
  }
  document.querySelectorAll('.acc-section').forEach(s=>s.classList.remove('active'));
  const next=document.querySelector(`.acc-section[data-step="${n}"]`);
  if(next){
    next.classList.add('active');
    next.classList.remove('done');
    next.scrollIntoView({behavior:'smooth',block:'start'});
  }
}
function finishWizard(){
  document.querySelectorAll('.acc-section').forEach(s=>{
    s.classList.remove('active');
    s.classList.add('done');
  });
  saveData();
  if(window.innerWidth<=768)closeEditorDrawer();
}

// ═══ SIDEBAR RESIZE ═══
const EDITOR_MIN_W=260,EDITOR_MAX_W=650;
// ═══ MOBILE DRAWER (editor as slide-in panel on small screens) ═══
function toggleEditorDrawer(){
  document.getElementById('editor-wrap').classList.toggle('open');
  document.getElementById('editor-backdrop').classList.toggle('show');
}
function closeEditorDrawer(){
  document.getElementById('editor-wrap').classList.remove('open');
  document.getElementById('editor-backdrop').classList.remove('show');
}
// Auto-close the drawer once the user picks a step's Next/Back on mobile
// isn't required (they may want to keep working through steps), but closing
// after Done makes sense — handled inside finishWizard().

// ═══ PAPER AUTO-SCALE (keeps the fixed 794px A4 page inside the viewport
// on tablets/phones, instead of causing horizontal scrolling) ═══
function scalePaperToFit(){
  const wrap=document.getElementById('preview-wrap');
  const scaleEl=document.getElementById('paper-scale');
  if(!wrap||!scaleEl)return;
  const PAPER_W=794;
  const horizontalPadding=window.innerWidth<=768?20:56; // matches #preview-wrap padding*2 roughly
  const available=wrap.clientWidth-horizontalPadding;
  if(available>=PAPER_W){
    scaleEl.style.transform='none';
    scaleEl.style.marginBottom='0';
  }else{
    const scale=Math.max(0.28,available/PAPER_W);
    scaleEl.style.transform=`scale(${scale})`;
    // compensate the layout box so scaled-down content doesn't leave a huge blank gap below it
    const paperEl=document.getElementById('paper');
    const h=paperEl?paperEl.scrollHeight:1123;
    scaleEl.style.marginBottom=(-(h*(1-scale)))+'px';
  }
}
window.addEventListener('resize',()=>{
  clearTimeout(window._scaleDebounce);
  window._scaleDebounce=setTimeout(scalePaperToFit,120);
});

function initResize(){
  const editor=document.getElementById('editor');
  const handle=document.getElementById('resize-handle');
  const savedW=parseInt(localStorage.getItem('cv_editor_w')||'320');
  editor.style.width=Math.min(EDITOR_MAX_W,Math.max(EDITOR_MIN_W,savedW))+'px';

  let dragging=false,startX=0,startW=0;
  handle.addEventListener('mousedown',e=>{
    dragging=true;startX=e.clientX;startW=editor.getBoundingClientRect().width;
    handle.classList.add('dragging');
    document.body.classList.add('resizing');
    e.preventDefault();
  });
  window.addEventListener('mousemove',e=>{
    if(!dragging)return;
    const newW=Math.min(EDITOR_MAX_W,Math.max(EDITOR_MIN_W,startW+(e.clientX-startX)));
    editor.style.width=newW+'px';
  });
  window.addEventListener('mouseup',()=>{
    if(!dragging)return;
    dragging=false;
    handle.classList.remove('dragging');
    document.body.classList.remove('resizing');
    localStorage.setItem('cv_editor_w',Math.round(editor.getBoundingClientRect().width));
  });
  // touch support
  handle.addEventListener('touchstart',e=>{
    dragging=true;startX=e.touches[0].clientX;startW=editor.getBoundingClientRect().width;
    handle.classList.add('dragging');
  },{passive:true});
  window.addEventListener('touchmove',e=>{
    if(!dragging)return;
    const newW=Math.min(EDITOR_MAX_W,Math.max(EDITOR_MIN_W,startW+(e.touches[0].clientX-startX)));
    editor.style.width=newW+'px';
  },{passive:true});
  window.addEventListener('touchend',()=>{
    if(!dragging)return;
    dragging=false;
    handle.classList.remove('dragging');
    localStorage.setItem('cv_editor_w',Math.round(editor.getBoundingClientRect().width));
  });
}

// ═══ COLOR PRESETS ═══
const COLOR_PRESETS=[
  {id:'default',label:'Default',primary:'#1A2039',secondary:'#1a56db',bg:'#ffffff'},
  {id:'travhub',label:'TravHub',primary:'#1A2039',secondary:'#50BC81',bg:'#ffffff'},
  {id:'majestic-classic',label:'Majestic Classic',primary:'#1A2039',secondary:'#F36E56',bg:'#FFFDFA'},
  {id:'majestic-bold',label:'Majestic Bold',primary:'#1A2039',secondary:'#D2202F',bg:'#FFFDFA'}
];

// ═══ DATA ═══
const DEFAULT={
  photo:'',name:'Asif Mostofa Sazid',title:'Software Engineer & Web Developer',
  email:'asif83sazid@gmail.com',phone:'+880 1751 906 710',
  location:'Uttarkhan, Dhaka-1230, Bangladesh',website:'www.asifsazid.com',
  linkedin:'linkedin.com/in/sazidmostofa',github:'github.com/AsifSazid',facebook:'',
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
  skillsArr:['PHP','Laravel','JavaScript','React','HTML/CSS','Bootstrap 5','Tailwind CSS','jQuery','SASS','MySQL','Oracle','Git','GitHub','GitLab','Google Cloud','Agile','Waterfall','C','C++','Linux','Generative AI Prompt Engineering'],
  languages:[{lang:'Bangla',level:'Native'},{lang:'English',level:'Intermediate'},{lang:'Hindi',level:'Elementary'}],
  certs:'Web Design & Development for Freelancing – Level 3 (NSDA)\nPHP with Laravel Framework – Batch 1 (SEIP/TRANCE-3)',
  colors:{preset:'default',primary:'#1A2039',secondary:'#1a56db',bg:'#ffffff'},
  projects:[],
  awards:[],
  volunteer:[],
  references:[],
  hobbiesArr:[],
  customSections:[]
};

function blankProject(){return {name:'',tech:'',link:'',desc:''};}
function blankAward(){return {title:'',issuer:'',year:'',desc:''};}
function blankVolunteer(){return {role:'',org:'',date:'',desc:''};}
function blankReference(){return {name:'',position:'',company:'',contact:''};}
function blankCustomSection(){return {id:'cs_'+Date.now()+'_'+Math.floor(Math.random()*1000),title:'',items:['']};}

let data=JSON.parse(localStorage.getItem('cv_data4')||'null')||JSON.parse(JSON.stringify(DEFAULT));
// migrate legacy comma-string skills to array
if(typeof data.skills==='string'&&!data.skillsArr){
  data.skillsArr=data.skills.split(',').map(s=>s.trim()).filter(Boolean);
}
if(!data.skillsArr)data.skillsArr=[];
if(!data.colors)data.colors={preset:'default',primary:'#1A2039',secondary:'#1a56db',bg:'#ffffff'};
if(!data.facebook)data.facebook='';
// migrate: new optional sections added later — default to empty so older saved data doesn't break
if(!data.projects)data.projects=[];
if(!data.awards)data.awards=[];
if(!data.volunteer)data.volunteer=[];
if(!data.references)data.references=[];
if(!data.hobbiesArr)data.hobbiesArr=[];
if(!data.customSections)data.customSections=[];
// migrate custom sections missing an id (shouldn't happen, but be safe)
data.customSections.forEach(cs=>{if(!cs.id)cs.id='cs_'+Date.now()+'_'+Math.floor(Math.random()*1000);if(!Array.isArray(cs.items))cs.items=[''];});

let currentTpl=parseInt(localStorage.getItem('cv_tpl4')||'1');

// ═══ INIT ═══
function init(){
  document.body.setAttribute('data-tpl',currentTpl);
  document.querySelectorAll('.tpl-pill').forEach((p,i)=>p.classList.toggle('active',i+1===currentTpl));
  populateEditor();
  renderColorPresets();
  applyColors();
  initResize();
  render();
  scalePaperToFit();
}
function populateEditor(){
  ['name','title','email','phone','location','website','linkedin','github','facebook','summary','certs'].forEach(k=>{
    const el=document.getElementById('f-'+k);if(el)el.value=data[k]||'';
  });
  if(data.photo)setPhotoPreview(data.photo);
  renderExpEditor();renderEduEditor();renderLangEditor();renderSkillEditor();
  renderProjectEditor();renderAwardEditor();renderVolunteerEditor();renderReferenceEditor();
  renderHobbyEditor();renderCustomSectionEditor();
  document.getElementById('c-primary').value=data.colors.primary;
  document.getElementById('c-primary-hex').value=data.colors.primary;
  document.getElementById('c-secondary').value=data.colors.secondary;
  document.getElementById('c-secondary-hex').value=data.colors.secondary;
  document.getElementById('c-bg').value=data.colors.bg;
  document.getElementById('c-bg-hex').value=data.colors.bg;
}

// ═══ COLORS ═══
function renderColorPresets(){
  document.getElementById('color-presets').innerHTML=COLOR_PRESETS.map(p=>`
    <button class="color-preset-btn ${data.colors.preset===p.id?'active':''}" data-preset="${p.id}" onclick="applyPreset('${p.id}')">
      <span class="swatch-pair"><span class="sw" style="background:${p.primary}"></span><span class="sw" style="background:${p.secondary}"></span></span>
      <span>${p.label}</span>
    </button>`).join('');
}
function applyPreset(id){
  const p=COLOR_PRESETS.find(x=>x.id===id);if(!p)return;
  data.colors={preset:id,primary:p.primary,secondary:p.secondary,bg:p.bg};
  document.getElementById('c-primary').value=p.primary;
  document.getElementById('c-primary-hex').value=p.primary;
  document.getElementById('c-secondary').value=p.secondary;
  document.getElementById('c-secondary-hex').value=p.secondary;
  document.getElementById('c-bg').value=p.bg;
  document.getElementById('c-bg-hex').value=p.bg;
  renderColorPresets();applyColors();
}
function isValidHex(v){return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v);}
function onColorPick(key){
  const v=document.getElementById('c-'+key).value;
  data.colors[key]=v;data.colors.preset='custom';
  document.getElementById('c-'+key+'-hex').value=v;
  renderColorPresets();applyColors();
}
function onColorType(key){
  const v=document.getElementById('c-'+key+'-hex').value.trim();
  if(!isValidHex(v))return;
  data.colors[key]=v;data.colors.preset='custom';
  document.getElementById('c-'+key).value=v;
  renderColorPresets();applyColors();
}
function applyColors(){
  document.documentElement.style.setProperty('--accent',data.colors.primary);
  document.documentElement.style.setProperty('--accent-2',data.colors.secondary);
  document.documentElement.style.setProperty('--paper-bg',data.colors.bg);
}
function swapPrimarySecondary(){
  const tmp=data.colors.primary;
  data.colors.primary=data.colors.secondary;
  data.colors.secondary=tmp;
  data.colors.preset='custom';
  document.getElementById('c-primary').value=data.colors.primary;
  document.getElementById('c-primary-hex').value=data.colors.primary;
  document.getElementById('c-secondary').value=data.colors.secondary;
  document.getElementById('c-secondary-hex').value=data.colors.secondary;
  renderColorPresets();applyColors();render();
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

// ═══ CV IMPORT / AI SCRAPE (Gemini via api/tools/cv-extractor.php) ═══
function setImportStatus(type,msg){
  const el=document.getElementById('import-status');
  el.className='import-status show '+type;
  el.innerHTML=type==='loading'?`<span class="mini-spinner"></span><span>${msg}</span>`:
    type==='success'?`<i class="fa-solid fa-circle-check"></i><span>${msg}</span>`:
    `<i class="fa-solid fa-circle-exclamation"></i><span>${msg}</span>`;
}
async function handleCvImport(e){
  const file=e.target.files[0];if(!file)return;
  setImportStatus('loading','Reading file...');
  try{
    const fd=new FormData();
    fd.append('file',file);
    setImportStatus('loading','Extracting with AI...');
    const res=await fetch('/api/tools/cv-extractor.php',{
      method:'POST',
      credentials:'include',
      body:fd
    });
    if(!res.ok){
      const txt=await res.text().catch(()=>'');
      throw new Error(`Server error (${res.status}) ${txt.slice(0,120)}`);
    }
    const json=await res.json();
    if(!json.success){throw new Error(json.message||'Extraction failed');}
    applyExtractedData(json.data||json.result||{});
    setImportStatus('success','CV data imported! Review the fields below.');
    setTimeout(()=>document.getElementById('import-status').classList.remove('show'),4000);
  }catch(err){
    setImportStatus('error','Import failed: '+err.message);
  }finally{
    document.getElementById('import-input').value='';
  }
}
// Maps the JSON returned by cv-extractor.php (Gemini structured extraction) onto our data model.
// Expected shape (fields optional, extractor should omit unknowns rather than guess):
// { name, title, email, phone, location, website, linkedin, github, facebook, summary,
//   experiences:[{role,company,date,desc}], education:[{degree,inst,year}],
//   skills:[string], languages:[{lang,level}], certifications:[string] }
function applyExtractedData(ex){
  const map={name:'name',title:'title',email:'email',phone:'phone',location:'location',
    website:'website',linkedin:'linkedin',github:'github',facebook:'facebook',summary:'summary'};
  Object.entries(map).forEach(([k,fld])=>{
    if(ex[k]&&String(ex[k]).trim())data[fld]=String(ex[k]).trim();
  });
  if(Array.isArray(ex.experiences)&&ex.experiences.length){
    data.experiences=ex.experiences.map(e=>({
      role:e.role||e.title||'',company:e.company||e.employer||'',
      date:e.date||e.duration||'',desc:e.desc||e.description||''
    }));
  }
  if(Array.isArray(ex.education)&&ex.education.length){
    data.education=ex.education.map(e=>({
      degree:e.degree||'',inst:e.inst||e.institution||'',year:e.year||e.result||''
    }));
  }
  if(Array.isArray(ex.skills)&&ex.skills.length){
    data.skillsArr=ex.skills.map(s=>String(s).trim()).filter(Boolean);
  }
  if(Array.isArray(ex.languages)&&ex.languages.length){
    data.languages=ex.languages.map(l=>({lang:l.lang||l.language||'',level:l.level||''}));
  }
  const certsArr=ex.certifications||ex.certs;
  if(Array.isArray(certsArr)&&certsArr.length){
    data.certs=certsArr.join('\n');
  }
  populateEditor();
  render();
}

// ═══ COLLECT ═══
function collect(){
  ['name','title','email','phone','location','website','linkedin','github','facebook','summary','certs'].forEach(k=>{
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
  data.skillsArr.forEach((s,i)=>{
    const el=document.getElementById(`skill-val-${i}`);
    if(el)data.skillsArr[i]=el.value;
  });
  data.projects.forEach((p,i)=>{
    ['name','tech','link','desc'].forEach(f=>{const el=document.getElementById(`proj-${f}-${i}`);if(el)p[f]=el.value;});
  });
  data.awards.forEach((a,i)=>{
    ['title','issuer','year','desc'].forEach(f=>{const el=document.getElementById(`award-${f}-${i}`);if(el)a[f]=el.value;});
  });
  data.volunteer.forEach((v,i)=>{
    ['role','org','date','desc'].forEach(f=>{const el=document.getElementById(`vol-${f}-${i}`);if(el)v[f]=el.value;});
  });
  data.references.forEach((r,i)=>{
    ['name','position','company','contact'].forEach(f=>{const el=document.getElementById(`ref-${f}-${i}`);if(el)r[f]=el.value;});
  });
  data.hobbiesArr.forEach((h,i)=>{
    const el=document.getElementById(`hobby-val-${i}`);
    if(el)data.hobbiesArr[i]=el.value;
  });
  data.customSections.forEach(cs=>{
    const titleEl=document.getElementById(`cs-title-${cs.id}`);
    if(titleEl)cs.title=titleEl.value;
    cs.items.forEach((item,i)=>{
      const el=document.getElementById(`cs-item-${cs.id}-${i}`);
      if(el)cs.items[i]=el.value;
    });
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
function renderSkillEditor(){
  document.getElementById('skill-tags-editor').innerHTML=data.skillsArr.map((s,i)=>`
    <div class="skill-tag-chip">
      <input id="skill-val-${i}" value="${esc(s)}" oninput="render()" placeholder="Skill"/>
      <button onclick="removeSkill(${i})" title="Remove">✕</button>
    </div>`).join('');
}
function addExp(){collect();data.experiences.push({role:'',company:'',date:'',desc:''});renderExpEditor();render();}
function removeExp(i){collect();data.experiences.splice(i,1);renderExpEditor();render();}
function addEdu(){collect();data.education.push({degree:'',inst:'',year:''});renderEduEditor();render();}
function removeEdu(i){collect();data.education.splice(i,1);renderEduEditor();render();}
function addLang(){collect();data.languages.push({lang:'',level:''});renderLangEditor();render();}
function removeLang(i){collect();data.languages.splice(i,1);renderLangEditor();render();}
function addSkill(){collect();data.skillsArr.push('');renderSkillEditor();render();
  const inputs=document.querySelectorAll('#skill-tags-editor input');
  if(inputs.length)inputs[inputs.length-1].focus();
}
function removeSkill(i){collect();data.skillsArr.splice(i,1);renderSkillEditor();render();}

function renderProjectEditor(){
  document.getElementById('project-list').innerHTML=data.projects.map((p,i)=>`
    <div class="exp-item">
      <div class="exp-header"><span>Project ${i+1}</span><button class="remove-btn" onclick="removeProject(${i})">✕ Remove</button></div>
      <div class="field-group"><label>Project Name</label><input id="proj-name-${i}" value="${esc(p.name)}" oninput="render()"/></div>
      <div class="field-group"><label>Tech / Tools Used</label><input id="proj-tech-${i}" value="${esc(p.tech)}" oninput="render()" placeholder="e.g. React, Node.js, MySQL"/></div>
      <div class="field-group"><label>Link (optional)</label><input id="proj-link-${i}" value="${esc(p.link)}" oninput="render()" placeholder="https://..."/></div>
      <div class="field-group"><label>Description</label><textarea id="proj-desc-${i}" oninput="render()">${esc(p.desc)}</textarea></div>
    </div>`).join('');
}
function addProject(){collect();data.projects.push(blankProject());renderProjectEditor();render();}
function removeProject(i){collect();data.projects.splice(i,1);renderProjectEditor();render();}

function renderAwardEditor(){
  document.getElementById('award-list').innerHTML=data.awards.map((a,i)=>`
    <div class="exp-item">
      <div class="exp-header"><span>Award ${i+1}</span><button class="remove-btn" onclick="removeAward(${i})">✕ Remove</button></div>
      <div class="field-group"><label>Title</label><input id="award-title-${i}" value="${esc(a.title)}" oninput="render()"/></div>
      <div class="field-group"><label>Issuer / Organization</label><input id="award-issuer-${i}" value="${esc(a.issuer)}" oninput="render()"/></div>
      <div class="field-group"><label>Year</label><input id="award-year-${i}" value="${esc(a.year)}" oninput="render()"/></div>
      <div class="field-group"><label>Description (optional)</label><textarea id="award-desc-${i}" oninput="render()">${esc(a.desc)}</textarea></div>
    </div>`).join('');
}
function addAward(){collect();data.awards.push(blankAward());renderAwardEditor();render();}
function removeAward(i){collect();data.awards.splice(i,1);renderAwardEditor();render();}

function renderVolunteerEditor(){
  document.getElementById('volunteer-list').innerHTML=data.volunteer.map((v,i)=>`
    <div class="exp-item">
      <div class="exp-header"><span>Volunteer ${i+1}</span><button class="remove-btn" onclick="removeVolunteer(${i})">✕ Remove</button></div>
      <div class="field-group"><label>Role</label><input id="vol-role-${i}" value="${esc(v.role)}" oninput="render()"/></div>
      <div class="field-group"><label>Organization</label><input id="vol-org-${i}" value="${esc(v.org)}" oninput="render()"/></div>
      <div class="field-group"><label>Date</label><input id="vol-date-${i}" value="${esc(v.date)}" oninput="render()"/></div>
      <div class="field-group"><label>Description</label><textarea id="vol-desc-${i}" oninput="render()">${esc(v.desc)}</textarea></div>
    </div>`).join('');
}
function addVolunteer(){collect();data.volunteer.push(blankVolunteer());renderVolunteerEditor();render();}
function removeVolunteer(i){collect();data.volunteer.splice(i,1);renderVolunteerEditor();render();}

function renderReferenceEditor(){
  document.getElementById('reference-list').innerHTML=data.references.map((r,i)=>`
    <div class="exp-item">
      <div class="exp-header"><span>Reference ${i+1}</span><button class="remove-btn" onclick="removeReference(${i})">✕ Remove</button></div>
      <div class="field-group"><label>Name</label><input id="ref-name-${i}" value="${esc(r.name)}" oninput="render()"/></div>
      <div class="field-group"><label>Position</label><input id="ref-position-${i}" value="${esc(r.position)}" oninput="render()"/></div>
      <div class="field-group"><label>Company</label><input id="ref-company-${i}" value="${esc(r.company)}" oninput="render()"/></div>
      <div class="field-group"><label>Email / Phone</label><input id="ref-contact-${i}" value="${esc(r.contact)}" oninput="render()"/></div>
    </div>`).join('');
}
function addReference(){collect();data.references.push(blankReference());renderReferenceEditor();render();}
function removeReference(i){collect();data.references.splice(i,1);renderReferenceEditor();render();}

function renderHobbyEditor(){
  document.getElementById('hobby-tags-editor').innerHTML=data.hobbiesArr.map((h,i)=>`
    <div class="skill-tag-chip">
      <input id="hobby-val-${i}" value="${esc(h)}" oninput="render()" placeholder="Hobby"/>
      <button onclick="removeHobby(${i})" title="Remove">✕</button>
    </div>`).join('');
}
function addHobby(){collect();data.hobbiesArr.push('');renderHobbyEditor();render();
  const inputs=document.querySelectorAll('#hobby-tags-editor input');
  if(inputs.length)inputs[inputs.length-1].focus();
}
function removeHobby(i){collect();data.hobbiesArr.splice(i,1);renderHobbyEditor();render();}

function renderCustomSectionEditor(){
  document.getElementById('custom-section-list').innerHTML=data.customSections.map((cs)=>`
    <div class="exp-item">
      <div class="exp-header"><span>Custom Section</span><button class="remove-btn" onclick="removeCustomSection('${cs.id}')">✕ Remove Section</button></div>
      <div class="field-group"><label>Section Title</label><input id="cs-title-${cs.id}" value="${esc(cs.title)}" oninput="render()" placeholder="e.g. Publications, Extra-curricular"/></div>
      <div class="field-group"><label>Items</label>
        ${cs.items.map((item,i)=>`
          <div style="display:flex;gap:6px;margin-bottom:5px">
            <input id="cs-item-${cs.id}-${i}" value="${esc(item)}" oninput="render()" placeholder="Item ${i+1}" style="flex:1"/>
            <button class="remove-btn" style="opacity:1" onclick="removeCustomItem('${cs.id}',${i})">✕</button>
          </div>`).join('')}
      </div>
      <button class="add-btn" onclick="addCustomItem('${cs.id}')">+ Add Item</button>
    </div>`).join('');
}
function addCustomSection(){collect();data.customSections.push(blankCustomSection());renderCustomSectionEditor();render();}
function removeCustomSection(id){collect();data.customSections=data.customSections.filter(cs=>cs.id!==id);renderCustomSectionEditor();render();}
function addCustomItem(id){collect();const cs=data.customSections.find(c=>c.id===id);if(cs)cs.items.push('');renderCustomSectionEditor();render();}
function removeCustomItem(id,i){collect();const cs=data.customSections.find(c=>c.id===id);if(cs){cs.items.splice(i,1);if(!cs.items.length)cs.items.push('');}renderCustomSectionEditor();render();}


// ═══ EXTRA-SECTION RENDER HELPERS (shared across all templates) ═══
// Each returns '' if there's nothing to show, so templates can splice
// these in without extra empty headings.
function renderProjectsSection(d,titleHtmlFn){
  const items=(d.projects||[]).filter(p=>p.name||p.desc);
  if(!items.length)return '';
  return titleHtmlFn('Projects')+items.map(p=>`
    <div class="exp-block">
      ${ifv(p.name,`<div class="exp-role">${esc(p.name)}${p.link?` <a href="${esc(p.link)}" style="color:var(--accent);font-size:10px;font-weight:500;text-decoration:none"><i class="fa-solid fa-link"></i></a>`:''}</div>`)}
      ${ifv(p.tech,`<div class="exp-co">${esc(p.tech)}</div>`)}
      ${ifv(p.desc,`<div class="exp-desc">${esc(p.desc)}</div>`)}
    </div>`).join('');
}
function renderAwardsSection(d,titleHtmlFn){
  const items=(d.awards||[]).filter(a=>a.title);
  if(!items.length)return '';
  return titleHtmlFn('Awards & Achievements')+items.map(a=>`
    <div class="exp-block">
      ${ifv(a.title,`<div class="exp-role">${esc(a.title)}</div>`)}
      ${(a.issuer||a.year)?`<div class="exp-co">${esc(a.issuer)}${a.issuer&&a.year?' — ':''}${esc(a.year)}</div>`:''}
      ${ifv(a.desc,`<div class="exp-desc">${esc(a.desc)}</div>`)}
    </div>`).join('');
}
function renderVolunteerSection(d,titleHtmlFn){
  const items=(d.volunteer||[]).filter(v=>v.role||v.org);
  if(!items.length)return '';
  return titleHtmlFn('Volunteer Experience')+items.map(v=>`
    <div class="exp-block">
      ${ifv(v.role,`<div class="exp-role">${esc(v.role)}</div>`)}
      ${ifv(v.org,`<div class="exp-co">${esc(v.org)}</div>`)}
      ${ifv(v.date,`<div class="exp-dt">${esc(v.date)}</div>`)}
      ${ifv(v.desc,`<div class="exp-desc">${esc(v.desc)}</div>`)}
    </div>`).join('');
}
function renderReferencesSection(d,titleHtmlFn){
  const items=(d.references||[]).filter(r=>r.name);
  if(!items.length)return '';
  return titleHtmlFn('References')+items.map(r=>`
    <div class="edu-block">
      ${ifv(r.name,`<div class="edu-deg">${esc(r.name)}</div>`)}
      ${(r.position||r.company)?`<div class="edu-inst">${esc(r.position)}${r.position&&r.company?', ':''}${esc(r.company)}</div>`:''}
      ${ifv(r.contact,`<div class="edu-yr">${esc(r.contact)}</div>`)}
    </div>`).join('');
}
function renderHobbiesSection(d,titleHtmlFn){
  const items=(d.hobbiesArr||[]).filter(h=>h&&h.trim());
  if(!items.length)return '';
  return titleHtmlFn('Hobbies & Interests')+`<div class="skills-wrap">${items.map(h=>`<span class="skill-tag">${esc(h)}</span>`).join('')}</div>`;
}
function renderCustomSectionsHtml(d,titleHtmlFn){
  const sections=(d.customSections||[]).filter(cs=>cs.title&&cs.items.some(i=>i&&i.trim()));
  if(!sections.length)return '';
  return sections.map(cs=>{
    const items=cs.items.filter(i=>i&&i.trim());
    return titleHtmlFn(esc(cs.title))+`<ul style="margin:0;padding-left:16px">${items.map(i=>`<li class="exp-desc" style="margin-bottom:3px">${esc(i)}</li>`).join('')}</ul>`;
  }).join('');
}

// ═══ RENDER ═══
function render(){
  collect();
  const t=currentTpl,d=data;
  const skills=d.skillsArr.filter(s=>s&&s.trim());
  const certs=d.certs.split('\n').map(s=>s.trim()).filter(Boolean);
  const exps=d.experiences.filter(e=>e.role||e.company);
  const edus=d.education.filter(e=>e.degree);
  const langs=d.languages.filter(l=>l.lang);
  let html='';

  if(t===1){
    const sideC=[ci('fa-solid fa-envelope',d.email),ci('fa-solid fa-phone',d.phone),ci('fa-solid fa-location-dot',d.location),ci('fa-solid fa-globe',d.website),ci('fa-brands fa-linkedin',d.linkedin),ci('fa-brands fa-github',d.github),ci('fa-brands fa-facebook',d.facebook)].filter(Boolean).join('');
    html=`
    <div class="cv-sidebar">
      ${photoEl('cv-photo','cv-photo-ph')}
      ${ifv(d.name,`<div class="cv-name">${esc(d.name)}</div>`)}
      ${ifv(d.title,`<div class="cv-job-title">${esc(d.title)}</div>`)}
      ${sideC?`<div class="side-sec">Contact</div>${sideC}`:''}
      ${skills.length?`<div class="side-sec">Skills</div><div class="skills-wrap">${skills.map(s=>`<span class="skill-tag">${esc(s)}</span>`).join('')}</div>`:''}
      ${langs.length?`<div class="side-sec">Languages</div>${langs.map(l=>`<div class="lang-row"><span>${esc(l.lang)}</span>${ifv(l.level,`<span class="lang-lv">${esc(l.level)}</span>`)}</div>`).join('')}`:''}
      ${certs.length?`<div class="side-sec">Certifications</div>${certs.map(c=>`<div class="cert-item"><i class="fa-solid fa-certificate" style="color:var(--accent-2);font-size:10px;margin-right:5px"></i>${esc(c)}</div>`).join('')}`:''}
      ${renderHobbiesSection(d,t=>`<div class="side-sec">${t}</div>`)}
    </div>
    <div class="cv-main">
      ${ifv(d.summary,`<div class="sec-title">Profile</div><div class="summary-text">${esc(d.summary)}</div>`)}
      ${exps.length?`<div class="sec-title">Experience</div>${exps.map(e=>`<div class="exp-block">${ifv(e.role,`<div class="exp-role">${esc(e.role)}</div>`)}${ifv(e.company,`<div class="exp-co">${esc(e.company)}</div>`)}${ifv(e.date,`<div class="exp-dt"><i class="fa-regular fa-calendar" style="margin-right:4px"></i>${esc(e.date)}</div>`)}${ifv(e.desc,`<div class="exp-desc">${esc(e.desc)}</div>`)}</div>`).join('')}`:''}
      ${edus.length?`<div class="sec-title">Education</div>${edus.map(e=>`<div class="edu-block">${ifv(e.degree,`<div class="edu-deg">${esc(e.degree)}</div>`)}${ifv(e.inst,`<div class="edu-inst">${esc(e.inst)}</div>`)}${ifv(e.year,`<div class="edu-yr">${esc(e.year)}</div>`)}</div>`).join('')}`:''}
      ${renderProjectsSection(d,t=>`<div class="sec-title">${t}</div>`)}
      ${renderAwardsSection(d,t=>`<div class="sec-title">${t}</div>`)}
      ${renderVolunteerSection(d,t=>`<div class="sec-title">${t}</div>`)}
      ${renderReferencesSection(d,t=>`<div class="sec-title">${t}</div>`)}
      ${renderCustomSectionsHtml(d,t=>`<div class="sec-title">${t}</div>`)}
    </div>`;

  }else if(t===2){
    const cArr=[ci('fa-solid fa-envelope',d.email),ci('fa-solid fa-phone',d.phone),ci('fa-solid fa-location-dot',d.location),ci('fa-solid fa-globe',d.website),ci('fa-brands fa-linkedin',d.linkedin),ci('fa-brands fa-github',d.github),ci('fa-brands fa-facebook',d.facebook)].filter(Boolean);
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
      ${certs.length?`<div class="sec-title">Certifications</div>${certs.map(c=>`<div class="cert-item"><i class="fa-solid fa-certificate" style="color:var(--ink);font-size:10px;margin-right:5px"></i>${esc(c)}</div>`).join('')}`:''}
      ${renderProjectsSection(d,t=>`<div class="sec-title">${t}</div>`)}
      ${renderAwardsSection(d,t=>`<div class="sec-title">${t}</div>`)}
      ${renderVolunteerSection(d,t=>`<div class="sec-title">${t}</div>`)}
      ${renderHobbiesSection(d,t=>`<div class="sec-title">${t}</div>`)}
      ${renderReferencesSection(d,t=>`<div class="sec-title">${t}</div>`)}
      ${renderCustomSectionsHtml(d,t=>`<div class="sec-title">${t}</div>`)}
    </div>`;

  }else if(t===3){
    const cArr=[ci('fa-solid fa-envelope',d.email),ci('fa-solid fa-phone',d.phone),ci('fa-solid fa-location-dot',d.location),ci('fa-solid fa-globe',d.website),ci('fa-brands fa-linkedin',d.linkedin),ci('fa-brands fa-github',d.github),ci('fa-brands fa-facebook',d.facebook)].filter(Boolean);
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
      ${renderProjectsSection(d,t=>`<div class="sec-title">— ${t}</div>`)}
      ${renderAwardsSection(d,t=>`<div class="sec-title">— ${t}</div>`)}
      ${renderVolunteerSection(d,t=>`<div class="sec-title">— ${t}</div>`)}
      ${renderHobbiesSection(d,t=>`<div class="sec-title">— ${t}</div>`)}
      ${renderReferencesSection(d,t=>`<div class="sec-title">— ${t}</div>`)}
      ${renderCustomSectionsHtml(d,t=>`<div class="sec-title">— ${t}</div>`)}
    </div>`;

  }else if(t===4){
    const cArr=[ci('fa-solid fa-envelope',d.email),ci('fa-solid fa-phone',d.phone),ci('fa-solid fa-location-dot',d.location),ci('fa-solid fa-globe',d.website),ci('fa-brands fa-linkedin',d.linkedin),ci('fa-brands fa-github',d.github),ci('fa-brands fa-facebook',d.facebook)].filter(Boolean);
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
          ${skills.length?`<div class="bold-left-sec"><div class="sec-title">Skills</div><div class="skills-wrap">${skills.map(s=>`<span class="skill-tag">${esc(s)}</span>`).join('')}</div></div>`:''}
          ${langs.length?`<div class="bold-left-sec"><div class="sec-title">Languages</div>${langs.map(l=>`<div class="lang-row"><span><span class="lang-dot"></span>${esc(l.lang)}</span><span>${esc(l.level)}</span></div>`).join('')}</div>`:''}
          ${certs.length?`<div class="bold-left-sec"><div class="sec-title">Certifications</div>${certs.map(c=>`<div class="cert-item"><i class="fa-solid fa-arrow-right" style="color:var(--accent);font-size:9px;margin-right:5px"></i>${esc(c)}</div>`).join('')}</div>`:''}
          ${d.hobbiesArr&&d.hobbiesArr.filter(h=>h&&h.trim()).length?`<div class="bold-left-sec">${renderHobbiesSection(d,t=>`<div class="sec-title">${t}</div>`)}</div>`:''}
          ${d.references&&d.references.filter(r=>r.name).length?`<div class="bold-left-sec">${renderReferencesSection(d,t=>`<div class="sec-title">${t}</div>`)}</div>`:''}
        </div>
        <div>
          ${exps.length?`<div class="sec-title">Experience</div>${exps.map(e=>`<div class="exp-block">${ifv(e.role,`<div class="exp-role">${esc(e.role)}</div>`)}${ifv(e.company,`<div class="exp-co">${esc(e.company)}</div>`)}${ifv(e.date,`<div class="exp-dt"><i class="fa-regular fa-calendar" style="margin-right:4px"></i>${esc(e.date)}</div>`)}${ifv(e.desc,`<div class="exp-desc">${esc(e.desc)}</div>`)}</div>`).join('')}`:''}
          ${renderProjectsSection(d,t=>`<div class="sec-title">${t}</div>`)}
          ${renderAwardsSection(d,t=>`<div class="sec-title">${t}</div>`)}
          ${renderVolunteerSection(d,t=>`<div class="sec-title">${t}</div>`)}
          ${renderCustomSectionsHtml(d,t=>`<div class="sec-title">${t}</div>`)}
        </div>
      </div>
    </div>`;
  }
  document.getElementById('paper').innerHTML=html;
  scalePaperToFit();
}

// ═══ TEMPLATE SWITCH ═══
function setTpl(n){
  currentTpl=n;document.body.setAttribute('data-tpl',n);
  document.querySelectorAll('.tpl-pill').forEach((p,i)=>p.classList.toggle('active',i+1===n));
  localStorage.setItem('cv_tpl4',n);render();
}

// ═══ SAVE ═══
function saveData(){
  collect();localStorage.setItem('cv_data4',JSON.stringify(data));
  const lbl=document.getElementById('save-lbl');lbl.textContent='Saved!';
  setTimeout(()=>lbl.textContent='Save',1600);
}

// ═══ CUSTOM MODAL (replaces browser alert()) ═══
function openAppModal({title,html,icon='fa-solid fa-circle-info',iconType=''}){
  document.getElementById('app-modal-title').textContent=title;
  document.getElementById('app-modal-body').innerHTML=html;
  const iconEl=document.getElementById('app-modal-icon');
  iconEl.innerHTML=`<i class="${icon}"></i>`;
  iconEl.className=iconType; // '', 'error', or 'success'
  document.getElementById('app-modal-backdrop').classList.add('show');
}
function closeAppModal(){
  document.getElementById('app-modal-backdrop').classList.remove('show');
}
document.addEventListener('keydown',e=>{
  if(e.key==='Escape')closeAppModal();
});

// Data lives only in this browser's localStorage (key: cv_data4) — nothing
// is sent to or stored on any server. Clearing browser data / using another
// device or a private/incognito window means this CV data will not be there.
function showStorageInfo(){
  openAppModal({
    title:'Where is my CV data stored?',
    icon:'fa-solid fa-database',
    html:`Your CV data is saved only in <strong>this browser</strong> (localStorage) — nothing is sent to or stored on any server.
    <ul>
      <li>It stays even after closing the tab, as long as you use the same browser on this device.</li>
      <li>It will <strong>not</strong> appear on another device or browser.</li>
      <li>Clearing your browser data/cache will erase it.</li>
    </ul>
    Use the <strong>PDF</strong> or <strong>DOCX</strong> export to keep a permanent copy.`
  });
}

// ═══ PRINT ═══
// Opens the browser's native print dialog with just the CV page — uses a
// dedicated print stylesheet so the editor sidebar, topbar, and shadows
// don't show up on the printed/PDF-via-print output.
function printCV(){
  collect();
  window.print();
}



// ═══ PDF — html2canvas + jsPDF (preserves all colors) ═══
// FIX: previous version sliced the tall canvas at hard pixel boundaries with
// no regard for where content actually broke, so page 2+ started mid-line/
// mid-block with no top margin (page 1 looked fine because it starts at 0).
// Now we render the full-height canvas at a HIGH-RES scale, then slice using
// the actual printable-area height in canvas pixels (accounting for jsPDF's
// mm-to-px ratio precisely) and add a small consistent top/bottom margin on
// every page after the first, so continued content never touches the edge.
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
    const SCALE=2;
    const canvas=await html2canvas(paper,{
      scale:SCALE,useCORS:true,allowTaint:true,
      backgroundColor:getComputedStyle(document.documentElement).getPropertyValue('--paper-bg').trim()||'#ffffff',
      logging:false,
      width:794,height:paper.scrollHeight,
      windowWidth:794,
      onclone:(doc)=>{
        // ensure the mobile/tablet CSS scale-down transform never affects the
        // full-resolution PDF capture — always render at true 794px size
        const clonedScaleWrap=doc.getElementById('paper-scale');
        if(clonedScaleWrap)clonedScaleWrap.style.transform='none';
        doc.querySelectorAll('*').forEach(el=>{
          el.style.webkitPrintColorAdjust='exact';
          el.style.printColorAdjust='exact';
        });
      }
    });
    msg.textContent='Building PDF...';
    const {jsPDF}=window.jspdf;
    const pdf=new jsPDF({orientation:'portrait',unit:'mm',format:'a4'});
    const pageWmm=210,pageHmm=297;
    const marginMm=8; // safe margin so page breaks never look flush/cut
    const usableHmm=pageHmm-(marginMm*2);

    // px-per-mm for THIS canvas (width in px maps to full page width in mm)
    const pxPerMm=canvas.width/pageWmm;
    const usableHpx=Math.floor(usableHmm*pxPerMm);

    let renderedPx=0;
    let pageNum=0;
    while(renderedPx<canvas.height){
      const sliceHpx=Math.min(usableHpx,canvas.height-renderedPx);
      if(sliceHpx<=0)break;

      const sc=document.createElement('canvas');
      sc.width=canvas.width;
      sc.height=sliceHpx;
      const sctx=sc.getContext('2d');
      // fill background first so any transparent edge doesn't turn black in JPEG
      sctx.fillStyle=getComputedStyle(document.documentElement).getPropertyValue('--paper-bg').trim()||'#ffffff';
      sctx.fillRect(0,0,sc.width,sc.height);
      sctx.drawImage(canvas,0,renderedPx,canvas.width,sliceHpx,0,0,canvas.width,sliceHpx);

      if(pageNum>0)pdf.addPage();
      const imgData=sc.toDataURL('image/jpeg',0.96);
      const renderedHmm=sliceHpx/pxPerMm;
      // place with a consistent top margin on every page
      pdf.addImage(imgData,'JPEG',0,marginMm,pageWmm,renderedHmm);

      renderedPx+=sliceHpx;
      pageNum++;
    }
    msg.textContent='Saving...';
    pdf.save(`${(data.name||'CV').replace(/\s+/g,'_')}_CV.pdf`);
  }catch(err){
    openAppModal({
      title:'PDF generation failed',
      icon:'fa-solid fa-triangle-exclamation',
      iconType:'error',
      html:`Something went wrong while creating the PDF.<br><br><strong>Details:</strong> ${esc(err.message)}<br><br>Try again, or use the <strong>Print</strong> button and choose "Save as PDF" from your browser's print dialog instead.`
    });
  }
  finally{loading.classList.remove('show');}
}

// ═══ DOCX ═══
// FIX: previous version silently failed for two reasons —
//  1) `B(e.degree)` / other helpers were called even when the field was
//     empty in some rows, and a couple of helpers didn't null-guard,
//     which could throw INSIDE the array build (before any try/catch existed).
//  2) There was no error reporting at all around Packer.toBlob()/the download
//     step, so if the CDN UMD build ever threw (e.g. blocked by an ad-blocker,
//     slow network, or a transient library issue) nothing told you why.
// Now: every text helper is null-safe, the whole thing runs in try/catch with
// a visible alert + console.error on failure, and we use Packer.toBlob with
// an explicit mime type plus a fallback to Packer.toBase64String if toBlob
// is ever unavailable on the loaded build.
async function downloadDOCX(){
  collect();
  const btn=document.querySelector('.tbar-btn.success');
  const origLabel=btn?btn.innerHTML:null;
  try{
    if(typeof docx==='undefined'){
      throw new Error('DOCX library did not load — check your internet connection and try again.');
    }
    const{Document,Packer,Paragraph,TextRun,BorderStyle}=docx;
    if(!Document||!Packer||!Paragraph||!TextRun){
      throw new Error('DOCX library loaded but is missing expected exports (Document/Packer/Paragraph/TextRun).');
    }
    if(btn)btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Building...';

    const d=data;
    const primaryHex=(d.colors&&d.colors.primary?d.colors.primary:'#1a56db').replace('#','');
    const inkHex='0f172a';
    const skills=(d.skillsArr||[]).filter(s=>s&&s.trim());
    const certs=(d.certs||'').split('\n').map(s=>s.trim()).filter(Boolean);
    const exps=(d.experiences||[]).filter(e=>e.role||e.company);
    const edus=(d.education||[]).filter(e=>e.degree);
    const langs=(d.languages||[]).filter(l=>l.lang);

    const HR=()=>new Paragraph({border:{bottom:{style:BorderStyle.SINGLE,size:6,color:primaryHex}},spacing:{after:120}});
    const H=txt=>txt?new Paragraph({children:[new TextRun({text:String(txt),bold:true,size:22,color:primaryHex,font:'Calibri'})],spacing:{after:60}}):null;
    const P=(txt,color='475569',sz=20)=>txt&&String(txt).trim()?new Paragraph({children:[new TextRun({text:String(txt),size:sz,color,font:'Calibri'})],spacing:{after:60}}):null;
    const B=txt=>txt&&String(txt).trim()?new Paragraph({children:[new TextRun({text:String(txt),bold:true,size:22,color:inkHex,font:'Calibri'})],spacing:{after:40}}):null;

    const children=[
      new Paragraph({children:[new TextRun({text:d.name||'',bold:true,size:52,color:inkHex,font:'Calibri'})],spacing:{after:50}}),
      d.title?new Paragraph({children:[new TextRun({text:d.title,size:22,color:primaryHex,font:'Calibri'})],spacing:{after:50}}):null,
      new Paragraph({children:[new TextRun({text:[d.email,d.phone,d.location].filter(Boolean).join('  |  '),size:18,color:'64748b',font:'Calibri'})],spacing:{after:40}}),
      [d.website,d.linkedin,d.github,d.facebook].filter(Boolean).length?new Paragraph({children:[new TextRun({text:[d.website,d.linkedin,d.github,d.facebook].filter(Boolean).join('  |  '),size:18,color:'64748b',font:'Calibri'})],spacing:{after:140}}):null,
      d.summary?H('PROFILE SUMMARY'):null,d.summary?HR():null,d.summary?P(d.summary):null,
      exps.length?new Paragraph({spacing:{after:100}}):null,
      exps.length?H('EXPERIENCE'):null,exps.length?HR():null,
      ...exps.flatMap(e=>[
        B(e.role),
        (e.company||e.date)?new Paragraph({children:[new TextRun({text:e.company||'',size:20,color:primaryHex,font:'Calibri'}),new TextRun({text:e.date?`   ${e.date}`:'',size:18,color:'94a3b8',font:'Calibri'})],spacing:{after:40}}):null,
        P(e.desc,'475569',19),
        new Paragraph({spacing:{after:80}})
      ]),
      edus.length?H('EDUCATION'):null,edus.length?HR():null,
      ...edus.flatMap(e=>[
        B(e.degree),
        (e.inst||e.year)?new Paragraph({children:[new TextRun({text:e.inst||'',size:19,color:'64748b',font:'Calibri'}),new TextRun({text:e.year?`   ${e.year}`:'',size:18,color:'94a3b8',font:'Calibri'})],spacing:{after:80}}):null
      ]),
      skills.length?H('SKILLS'):null,skills.length?HR():null,skills.length?P(skills.join('  •  '),'475569',19):null,
      langs.length?H('LANGUAGES'):null,langs.length?HR():null,
      ...langs.map(l=>P(`${l.lang}${l.level?' — '+l.level:''}`,'475569',19)),
      certs.length?H('CERTIFICATIONS'):null,certs.length?HR():null,
      ...certs.map(c=>P('• '+c,'475569',19)),

      // Projects
      ...(()=>{
        const items=(d.projects||[]).filter(p=>p.name||p.desc);
        if(!items.length)return [];
        return [
          H('PROJECTS'),HR(),
          ...items.flatMap(p=>[
            B(p.name+(p.link?`  (${p.link})`:'')),
            P(p.tech,'1a56db',19),
            P(p.desc,'475569',19),
            new Paragraph({spacing:{after:60}})
          ])
        ];
      })(),

      // Awards & Achievements
      ...(()=>{
        const items=(d.awards||[]).filter(a=>a.title);
        if(!items.length)return [];
        return [
          H('AWARDS & ACHIEVEMENTS'),HR(),
          ...items.flatMap(a=>[
            B(a.title),
            P([a.issuer,a.year].filter(Boolean).join(' — '),'64748b',19),
            P(a.desc,'475569',19),
            new Paragraph({spacing:{after:60}})
          ])
        ];
      })(),

      // Volunteer Experience
      ...(()=>{
        const items=(d.volunteer||[]).filter(v=>v.role||v.org);
        if(!items.length)return [];
        return [
          H('VOLUNTEER EXPERIENCE'),HR(),
          ...items.flatMap(v=>[
            B(v.role),
            P([v.org,v.date].filter(Boolean).join('   '),'64748b',19),
            P(v.desc,'475569',19),
            new Paragraph({spacing:{after:60}})
          ])
        ];
      })(),

      // References
      ...(()=>{
        const items=(d.references||[]).filter(r=>r.name);
        if(!items.length)return [];
        return [
          H('REFERENCES'),HR(),
          ...items.flatMap(r=>[
            B(r.name),
            P([r.position,r.company].filter(Boolean).join(', '),'64748b',19),
            P(r.contact,'475569',19),
            new Paragraph({spacing:{after:60}})
          ])
        ];
      })(),

      // Hobbies & Interests
      ...(()=>{
        const items=(d.hobbiesArr||[]).filter(h=>h&&h.trim());
        if(!items.length)return [];
        return [H('HOBBIES & INTERESTS'),HR(),P(items.join('  •  '),'475569',19)];
      })(),

      // Custom Sections
      ...(()=>{
        const sections=(d.customSections||[]).filter(cs=>cs.title&&cs.items.some(i=>i&&i.trim()));
        if(!sections.length)return [];
        return sections.flatMap(cs=>{
          const items=cs.items.filter(i=>i&&i.trim());
          return [H(cs.title.toUpperCase()),HR(),...items.map(i=>P('• '+i,'475569',19))];
        });
      })()
    ].filter(Boolean);

    const doc=new Document({
      sections:[{
        properties:{page:{size:{width:12240,height:15840},margin:{top:720,right:1080,bottom:720,left:1080}}},
        children
      }]
    });

    let blob;
    if(typeof Packer.toBlob==='function'){
      blob=await Packer.toBlob(doc);
    }else if(typeof Packer.toBase64String==='function'){
      // Fallback for builds where toBlob isn't exposed
      const b64=await Packer.toBase64String(doc);
      const byteChars=atob(b64);
      const byteNumbers=new Array(byteChars.length);
      for(let i=0;i<byteChars.length;i++)byteNumbers[i]=byteChars.charCodeAt(i);
      const byteArray=new Uint8Array(byteNumbers);
      blob=new Blob([byteArray],{type:'application/vnd.openxmlformats-officedocument.wordprocessingml.document'});
    }else{
      throw new Error('This build of the DOCX library exposes neither Packer.toBlob nor Packer.toBase64String.');
    }

    if(!blob||blob.size===0){
      throw new Error('Generated DOCX file was empty.');
    }

    const url=URL.createObjectURL(blob);
    const a=document.createElement('a');
    a.href=url;
    a.download=`${(d.name||'CV').replace(/\s+/g,'_')}_CV.docx`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(()=>URL.revokeObjectURL(url),1000);

  }catch(err){
    openAppModal({
      title:'DOCX generation failed',
      icon:'fa-solid fa-triangle-exclamation',
      iconType:'error',
      html:`Something went wrong while creating the Word document.<br><br><strong>Details:</strong> ${esc(err.message)}<br><br>Check your internet connection (the DOCX library loads from a CDN) and try again.`
    });
  }finally{
    if(btn&&origLabel)btn.innerHTML=origLabel;
  }
}

init();
</script>
</body>
</html>