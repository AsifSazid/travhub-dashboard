<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Iftar Special Menu — Ramadan 2026</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cinzel:wght@600;700&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet"/>
<style>
  :root {
    --gold: #e8b84b;
    --gold-light: #f5d680;
    --gold-dark: #b8892b;
    --bg-deep: #080608;
    --bg-mid: #110e0a;
    --bg-card: #18130e;
    --accent-red: #c0392b;
    --accent-green: #1a7a4a;
    --accent-teal: #0e7a6e;
    --accent-purple: #6b3fa0;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--bg-deep);
    font-family: 'Crimson Text', Georgia, serif;
    min-height: 100vh;
    overflow-x: hidden;
  }

  /* ── Starfield ── */
  .stars {
    position: fixed; inset: 0; pointer-events: none; z-index: 0;
    background:
      radial-gradient(1px 1px at 10% 15%, rgba(255,255,255,.7) 0%, transparent 100%),
      radial-gradient(1px 1px at 23% 42%, rgba(240,192,64,.8) 0%, transparent 100%),
      radial-gradient(2px 2px at 35% 8%,  rgba(255,255,255,.5) 0%, transparent 100%),
      radial-gradient(1px 1px at 48% 27%, rgba(255,255,255,.6) 0%, transparent 100%),
      radial-gradient(1px 1px at 57% 65%, rgba(240,192,64,.7) 0%, transparent 100%),
      radial-gradient(2px 2px at 67% 18%, rgba(255,255,255,.4) 0%, transparent 100%),
      radial-gradient(1px 1px at 76% 80%, rgba(255,255,255,.6) 0%, transparent 100%),
      radial-gradient(1px 1px at 84% 33%, rgba(240,192,64,.5) 0%, transparent 100%),
      radial-gradient(2px 2px at 90% 55%, rgba(255,255,255,.7) 0%, transparent 100%),
      radial-gradient(1px 1px at 5%  70%, rgba(255,255,255,.4) 0%, transparent 100%),
      radial-gradient(1px 1px at 15% 88%, rgba(240,192,64,.6) 0%, transparent 100%),
      radial-gradient(1px 1px at 42% 90%, rgba(255,255,255,.5) 0%, transparent 100%),
      radial-gradient(2px 2px at 60% 95%, rgba(255,255,255,.3) 0%, transparent 100%),
      radial-gradient(1px 1px at 78% 12%, rgba(240,192,64,.7) 0%, transparent 100%),
      radial-gradient(1px 1px at 95% 75%, rgba(255,255,255,.6) 0%, transparent 100%),
      radial-gradient(1px 1px at 30% 58%, rgba(255,255,255,.4) 0%, transparent 100%),
      radial-gradient(1px 1px at 52% 45%, rgba(240,192,64,.5) 0%, transparent 100%),
      radial-gradient(2px 2px at 70% 38%, rgba(255,255,255,.6) 0%, transparent 100%),
      radial-gradient(1px 1px at 88% 22%, rgba(255,255,255,.4) 0%, transparent 100%),
      radial-gradient(1px 1px at 18% 62%, rgba(240,192,64,.6) 0%, transparent 100%);
    animation: twinkle 6s ease-in-out infinite alternate;
  }
  @keyframes twinkle {
    0%   { opacity: .6; }
    50%  { opacity: 1; }
    100% { opacity: .7; }
  }

  /* ── Lantern decorations ── */
  .lantern {
    display: inline-block;
    animation: sway 4s ease-in-out infinite alternate;
    transform-origin: top center;
    font-size: 2.5rem;
    filter: drop-shadow(0 0 12px rgba(240,192,64,.7));
  }
  @keyframes sway {
    from { transform: rotate(-8deg); }
    to   { transform: rotate(8deg); }
  }

  /* ── Hero ── */
  .hero-title {
    font-family: 'Cinzel Decorative', serif;
    color: var(--gold);
    text-shadow: 0 0 20px rgba(232,184,75,.5), 0 0 60px rgba(232,184,75,.2);
    animation: glow 3s ease-in-out infinite alternate;
    line-height: 1.15;
  }
  @keyframes glow {
    from { text-shadow: 0 0 15px rgba(232,184,75,.4); }
    to   { text-shadow: 0 0 40px rgba(232,184,75,.8), 0 0 80px rgba(192,57,43,.3); }
  }

  .gold-line {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
  }

  /* ── Cards ── */
  .menu-card {
    background: linear-gradient(145deg, rgba(255,255,255,.055) 0%, rgba(255,255,255,.02) 100%);
    border: 1px solid rgba(232,184,75,.2);
    border-radius: 20px;
    backdrop-filter: blur(10px);
    transition: transform .35s cubic-bezier(.34,1.56,.64,1), box-shadow .35s ease, border-color .35s ease;
    animation: fadeUp .6s ease forwards;
    opacity: 0;
    position: relative;
    overflow: hidden;
  }
  .menu-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, transparent, var(--card-accent, var(--gold)), transparent);
    opacity: 0;
    transition: opacity .3s ease;
  }
  .menu-card:hover::before { opacity: 1; }
  .menu-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 24px 60px rgba(0,0,0,.7), 0 0 0 1px var(--card-accent, var(--gold-dark));
    border-color: var(--card-accent, var(--gold));
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(35px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  /* Stagger delays */
  .card-1  { animation-delay: .05s; }
  .card-2  { animation-delay: .12s; }
  .card-3  { animation-delay: .19s; }
  .card-4  { animation-delay: .26s; }
  .card-5  { animation-delay: .33s; }
  .card-6  { animation-delay: .40s; }
  .card-7  { animation-delay: .47s; }
  .card-8  { animation-delay: .54s; }
  .card-9  { animation-delay: .61s; }
  .card-10 { animation-delay: .68s; }
  .card-11 { animation-delay: .75s; }
  .card-12 { animation-delay: .82s; }

  /* ── Item rows ── */
  .item-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 6px 0;
    border-bottom: 1px solid rgba(255,255,255,.05);
  }
  .item-row:last-child { border-bottom: none; }

  .set2-highlight {
    color: var(--card-accent, var(--gold));
    font-weight: 600;
  }
  .set2-tag {
    display: inline-block;
    border: 1px solid var(--card-accent, var(--gold));
    background: rgba(232,184,75,.1);
    color: var(--card-accent, var(--gold));
    font-size: .6rem;
    padding: 1px 7px;
    border-radius: 20px;
    font-family: sans-serif;
    font-weight: 700;
    letter-spacing: .5px;
    vertical-align: middle;
    margin-left: 6px;
  }

  /* ── Badge ── */
  .badge {
    display: inline-block;
    font-size: .68rem;
    font-family: sans-serif;
    font-weight: 700;
    letter-spacing: .5px;
    padding: 2px 10px;
    border-radius: 20px;
    border: 1px solid;
  }

  /* ── Price ── */
  .price-tag {
    font-family: 'Cinzel', serif;
    font-weight: 700;
  }

  /* ── Savings pill ── */
  .savings {
    background: rgba(76,175,80,.15);
    border: 1px solid rgba(76,175,80,.4);
    color: #6fcf72;
    font-size: .72rem;
    font-family: sans-serif;
    padding: 3px 12px;
    border-radius: 20px;
    display: inline-block;
  }

  /* ── Corner ornament ── */
  .corner-ornament {
    position: absolute;
    font-size: 3.5rem;
    opacity: .06;
    pointer-events: none;
    user-select: none;
    line-height: 1;
  }

  /* ── Ornamental border on header ── */
  .ornament-border {
    border: 1px solid rgba(232,184,75,.25);
    border-radius: 16px;
    padding: 2px;
    display: inline-block;
  }
  .ornament-inner {
    border: 1px solid rgba(232,184,75,.15);
    border-radius: 14px;
    padding: 30px 50px;
  }

  /* ── Scroll indicator ── */
  .scroll-fade { animation: scrollFade 1.5s ease infinite; }
  @keyframes scrollFade { 0%,100%{opacity:.3} 50%{opacity:1} }

  /* ── Mobile ── */
  @media(max-width:640px) {
    .ornament-inner { padding: 20px 20px; }
    .hero-title { font-size: 1.6rem !important; }
  }
</style>
</head>
<body>

<div class="stars"></div>

<!-- ═══════════════════  HERO  ═══════════════════ -->
<header class="relative z-10 flex flex-col items-center text-center pt-14 pb-10 px-4">

  <!-- Lanterns row -->
  <div class="flex gap-8 mb-6">
    <span class="lantern" style="animation-delay:0s">🪔</span>
    <span class="lantern" style="animation-delay:.4s">🌙</span>
    <span class="lantern" style="animation-delay:.8s">🪔</span>
  </div>

  <div class="ornament-border mb-4">
    <div class="ornament-inner">
      <p class="text-xs tracking-widest mb-2" style="color:#b8892b;font-family:'Cinzel',serif;letter-spacing:4px;">
        ☪ &nbsp; RAMADAN MUBARAK &nbsp; ☪
      </p>
      <h1 class="hero-title" style="font-size:clamp(1.8rem,5vw,3.4rem);">
        IFTAR SPECIAL
      </h1>
      <h2 class="hero-title" style="font-size:clamp(1rem,3vw,1.8rem);opacity:.85;margin-top:4px;">
        SET MENU COLLECTION
      </h2>
      <div class="gold-line mt-5 mb-3" style="width:180px;margin-left:auto;margin-right:auto;"></div>
      <p style="color:#a08060;font-family:'Crimson Text',serif;font-style:italic;font-size:1.05rem;letter-spacing:1px;">
        Every set includes <span style="color:var(--gold);font-weight:600;">Set-2</span> — Egg Fried Rice · Mixed Veg · 2 pcs Fried Chicken
      </p>
    </div>
  </div>

  <div style="background:rgba(232,184,75,.1);border:1px solid rgba(232,184,75,.3);border-radius:30px;padding:6px 24px;color:var(--gold);font-family:sans-serif;font-size:.82rem;letter-spacing:1.5px;">
    Tk 230 &nbsp;–&nbsp; Tk 590 &nbsp;·&nbsp; 12 Exclusive Packages
  </div>

  <div class="gold-line mt-8" style="width:60%;max-width:500px;"></div>
</header>

<!-- ═══════════════════  GRID  ═══════════════════ -->
<main class="relative z-10 max-w-7xl mx-auto px-4 pb-16">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="menuGrid">

    <!-- ─── SET 1 ─── -->
    <div class="menu-card card-1 p-6" style="--card-accent:#e8b84b;">
      <div class="corner-ornament" style="bottom:-10px;right:-5px;">🌙</div>
      <div class="flex justify-between items-start mb-3">
        <div>
          <h3 style="font-family:'Cinzel',serif;color:#e8b84b;font-size:1.1rem;letter-spacing:1px;">Set Menu — 1</h3>
          <span class="badge" style="color:#e8b84b;border-color:#e8b84b55;background:rgba(232,184,75,.1);">Starter Pack</span>
        </div>
        <div class="text-right">
          <div class="price-tag" style="color:#e8b84b;font-size:1.65rem;">Tk 230</div>
          <div style="color:#4a3f2f;text-decoration:line-through;font-size:.8rem;font-family:sans-serif;">Tk 280</div>
        </div>
      </div>
      <div class="gold-line mb-3" style="background:linear-gradient(90deg,transparent,#e8b84b,transparent);"></div>
      <div>
        <div class="item-row"><span>🌴</span><div><span style="color:#e8d5b0;font-size:.95rem;">Khejur</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3 pcs</span></div></div>
        <div class="item-row"><span>🥤</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shorbot (Rooh Afza)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 glass</span></div></div>
        <div class="item-row"><span>🍗</span><div><span class="set2-highlight">Set-2<span class="set2-tag">INCLUDED</span></span><br><span style="color:#5a4a30;font-family:sans-serif;font-size:.72rem;font-style:italic;">Egg Fried Rice · Mixed Veg · 2 pcs Fried Chicken</span></div></div>
        <div class="item-row"><span>🧅</span><div><span style="color:#e8d5b0;font-size:.95rem;">Piyaju</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3 pcs</span></div></div>
        <div class="item-row"><span>🍆</span><div><span style="color:#e8d5b0;font-size:.95rem;">Beguni</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2 pcs</span></div></div>
      </div>
      <div class="mt-4 flex justify-between items-center">
        <span class="savings">Save Tk 50 · 18% off</span>
        <div style="width:8px;height:8px;border-radius:50%;background:#e8b84b;box-shadow:0 0 8px #e8b84b;"></div>
      </div>
    </div>

    <!-- ─── SET 2 ─── -->
    <div class="menu-card card-2 p-6" style="--card-accent:#c0392b;">
      <div class="corner-ornament" style="bottom:-10px;right:-5px;">🕌</div>
      <div class="flex justify-between items-start mb-3">
        <div>
          <h3 style="font-family:'Cinzel',serif;color:#c0392b;font-size:1.1rem;letter-spacing:1px;">Set Menu — 2</h3>
          <span class="badge" style="color:#c0392b;border-color:#c0392b55;background:rgba(192,57,43,.1);">Most Popular 🔥</span>
        </div>
        <div class="text-right">
          <div class="price-tag" style="color:#c0392b;font-size:1.65rem;">Tk 290</div>
          <div style="color:#4a3f2f;text-decoration:line-through;font-size:.8rem;font-family:sans-serif;">Tk 350</div>
        </div>
      </div>
      <div class="gold-line mb-3" style="background:linear-gradient(90deg,transparent,#c0392b,transparent);"></div>
      <div>
        <div class="item-row"><span>🌴</span><div><span style="color:#e8d5b0;font-size:.95rem;">Khejur</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3 pcs</span></div></div>
        <div class="item-row"><span>🥤</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shorbot (Lemon)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 glass</span></div></div>
        <div class="item-row"><span>🍗</span><div><span class="set2-highlight" style="color:#c0392b;">Set-2<span class="set2-tag" style="border-color:#c0392b;color:#c0392b;background:rgba(192,57,43,.1);">INCLUDED</span></span><br><span style="color:#5a4a30;font-family:sans-serif;font-size:.72rem;font-style:italic;">Egg Fried Rice · Mixed Veg · 2 pcs Fried Chicken</span></div></div>
        <div class="item-row"><span>🥣</span><div><span style="color:#e8d5b0;font-size:.95rem;">Halim</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 cup</span></div></div>
        <div class="item-row"><span>🧅</span><div><span style="color:#e8d5b0;font-size:.95rem;">Piyaju + Beguni</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2+2 pcs</span></div></div>
        <div class="item-row"><span>🍬</span><div><span style="color:#e8d5b0;font-size:.95rem;">Jilapi</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2 pcs</span></div></div>
      </div>
      <div class="mt-4 flex justify-between items-center">
        <span class="savings">Save Tk 60 · 17% off</span>
        <div style="width:8px;height:8px;border-radius:50%;background:#c0392b;box-shadow:0 0 8px #c0392b;"></div>
      </div>
    </div>

    <!-- ─── SET 3 ─── -->
    <div class="menu-card card-3 p-6" style="--card-accent:#1a7a4a;">
      <div class="corner-ornament" style="bottom:-10px;right:-5px;">🌿</div>
      <div class="flex justify-between items-start mb-3">
        <div>
          <h3 style="font-family:'Cinzel',serif;color:#1a7a4a;font-size:1.1rem;letter-spacing:1px;">Set Menu — 3</h3>
          <span class="badge" style="color:#1a7a4a;border-color:#1a7a4a55;background:rgba(26,122,74,.1);">Best Value ⭐</span>
        </div>
        <div class="text-right">
          <div class="price-tag" style="color:#1a7a4a;font-size:1.65rem;">Tk 340</div>
          <div style="color:#4a3f2f;text-decoration:line-through;font-size:.8rem;font-family:sans-serif;">Tk 410</div>
        </div>
      </div>
      <div class="gold-line mb-3" style="background:linear-gradient(90deg,transparent,#1a7a4a,transparent);"></div>
      <div>
        <div class="item-row"><span>🌴</span><div><span style="color:#e8d5b0;font-size:.95rem;">Khejur</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3 pcs</span></div></div>
        <div class="item-row"><span>🥤</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shorbot (Tamarind)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 glass</span></div></div>
        <div class="item-row"><span>🍗</span><div><span class="set2-highlight" style="color:#1a7a4a;">Set-2<span class="set2-tag" style="border-color:#1a7a4a;color:#1a7a4a;background:rgba(26,122,74,.1);">INCLUDED</span></span><br><span style="color:#5a4a30;font-family:sans-serif;font-size:.72rem;font-style:italic;">Egg Fried Rice · Mixed Veg · 2 pcs Fried Chicken</span></div></div>
        <div class="item-row"><span>🥣</span><div><span style="color:#e8d5b0;font-size:.95rem;">Halim</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 bowl</span></div></div>
        <div class="item-row"><span>🍢</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shami Kebab</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2 pcs</span></div></div>
        <div class="item-row"><span>🍆</span><div><span style="color:#e8d5b0;font-size:.95rem;">Beguni + Piyaju</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3+3 pcs</span></div></div>
        <div class="item-row"><span>🍮</span><div><span style="color:#e8d5b0;font-size:.95rem;">Firni</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 cup</span></div></div>
      </div>
      <div class="mt-4 flex justify-between items-center">
        <span class="savings">Save Tk 70 · 17% off</span>
        <div style="width:8px;height:8px;border-radius:50%;background:#1a7a4a;box-shadow:0 0 8px #1a7a4a;"></div>
      </div>
    </div>

    <!-- ─── SET 4 ─── -->
    <div class="menu-card card-4 p-6" style="--card-accent:#7b3fa0;">
      <div class="corner-ornament" style="bottom:-10px;right:-5px;">👑</div>
      <div class="flex justify-between items-start mb-3">
        <div>
          <h3 style="font-family:'Cinzel',serif;color:#7b3fa0;font-size:1.1rem;letter-spacing:1px;">Set Menu — 4</h3>
          <span class="badge" style="color:#7b3fa0;border-color:#7b3fa055;background:rgba(123,63,160,.1);">Premium 👑</span>
        </div>
        <div class="text-right">
          <div class="price-tag" style="color:#7b3fa0;font-size:1.65rem;">Tk 400</div>
          <div style="color:#4a3f2f;text-decoration:line-through;font-size:.8rem;font-family:sans-serif;">Tk 480</div>
        </div>
      </div>
      <div class="gold-line mb-3" style="background:linear-gradient(90deg,transparent,#7b3fa0,transparent);"></div>
      <div>
        <div class="item-row"><span>🌴</span><div><span style="color:#e8d5b0;font-size:.95rem;">Khejur</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">5 pcs</span></div></div>
        <div class="item-row"><span>🥤</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shorbot (Borhani)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 glass</span></div></div>
        <div class="item-row"><span>🍗</span><div><span class="set2-highlight" style="color:#7b3fa0;">Set-2<span class="set2-tag" style="border-color:#7b3fa0;color:#7b3fa0;background:rgba(123,63,160,.1);">INCLUDED</span></span><br><span style="color:#5a4a30;font-family:sans-serif;font-size:.72rem;font-style:italic;">Egg Fried Rice · Mixed Veg · 2 pcs Fried Chicken</span></div></div>
        <div class="item-row"><span>🍖</span><div><span style="color:#e8d5b0;font-size:.95rem;">Chicken Roast</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 pc</span></div></div>
        <div class="item-row"><span>🥣</span><div><span style="color:#e8d5b0;font-size:.95rem;">Halim</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 bowl</span></div></div>
        <div class="item-row"><span>🍢</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shami Kebab</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2 pcs</span></div></div>
        <div class="item-row"><span>🧅</span><div><span style="color:#e8d5b0;font-size:.95rem;">Piyaju + Beguni + Jilapi</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2+2+3</span></div></div>
        <div class="item-row"><span>🍮</span><div><span style="color:#e8d5b0;font-size:.95rem;">Firni + Payesh</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 each</span></div></div>
      </div>
      <div class="mt-4 flex justify-between items-center">
        <span class="savings">Save Tk 80 · 17% off</span>
        <div style="width:8px;height:8px;border-radius:50%;background:#7b3fa0;box-shadow:0 0 8px #7b3fa0;"></div>
      </div>
    </div>

    <!-- ─── SET 5 ─── -->
    <div class="menu-card card-5 p-6" style="--card-accent:#c97010;">
      <div class="corner-ornament" style="bottom:-10px;right:-5px;">🍛</div>
      <div class="flex justify-between items-start mb-3">
        <div>
          <h3 style="font-family:'Cinzel',serif;color:#c97010;font-size:1.1rem;letter-spacing:1px;">Set Menu — 5</h3>
          <span class="badge" style="color:#c97010;border-color:#c9701055;background:rgba(201,112,16,.1);">Biryani Special 🍛</span>
        </div>
        <div class="text-right">
          <div class="price-tag" style="color:#c97010;font-size:1.65rem;">Tk 420</div>
          <div style="color:#4a3f2f;text-decoration:line-through;font-size:.8rem;font-family:sans-serif;">Tk 500</div>
        </div>
      </div>
      <div class="gold-line mb-3" style="background:linear-gradient(90deg,transparent,#c97010,transparent);"></div>
      <div>
        <div class="item-row"><span>🌴</span><div><span style="color:#e8d5b0;font-size:.95rem;">Khejur</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3 pcs</span></div></div>
        <div class="item-row"><span>🥤</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shorbot (Mango)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 glass</span></div></div>
        <div class="item-row"><span>🍗</span><div><span class="set2-highlight" style="color:#c97010;">Set-2<span class="set2-tag" style="border-color:#c97010;color:#c97010;background:rgba(201,112,16,.1);">INCLUDED</span></span><br><span style="color:#5a4a30;font-family:sans-serif;font-size:.72rem;font-style:italic;">Egg Fried Rice · Mixed Veg · 2 pcs Fried Chicken</span></div></div>
        <div class="item-row"><span>🍚</span><div><span style="color:#e8d5b0;font-size:.95rem;">Chicken Biryani</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">½ plate</span></div></div>
        <div class="item-row"><span>🥣</span><div><span style="color:#e8d5b0;font-size:.95rem;">Halim</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 cup</span></div></div>
        <div class="item-row"><span>🍢</span><div><span style="color:#e8d5b0;font-size:.95rem;">Seekh Kebab</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2 pcs</span></div></div>
        <div class="item-row"><span>🍆</span><div><span style="color:#e8d5b0;font-size:.95rem;">Beguni + Piyaju</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2+2 pcs</span></div></div>
        <div class="item-row"><span>🍮</span><div><span style="color:#e8d5b0;font-size:.95rem;">Doi (Mishti)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 cup</span></div></div>
      </div>
      <div class="mt-4 flex justify-between items-center">
        <span class="savings">Save Tk 80 · 16% off</span>
        <div style="width:8px;height:8px;border-radius:50%;background:#c97010;box-shadow:0 0 8px #c97010;"></div>
      </div>
    </div>

    <!-- ─── SET 6 ─── -->
    <div class="menu-card card-6 p-6" style="--card-accent:#0e7a6e;">
      <div class="corner-ornament" style="bottom:-10px;right:-5px;">🫙</div>
      <div class="flex justify-between items-start mb-3">
        <div>
          <h3 style="font-family:'Cinzel',serif;color:#0e7a6e;font-size:1.1rem;letter-spacing:1px;">Set Menu — 6</h3>
          <span class="badge" style="color:#0e7a6e;border-color:#0e7a6e55;background:rgba(14,122,110,.1);">Kebab Lover 🍢</span>
        </div>
        <div class="text-right">
          <div class="price-tag" style="color:#0e7a6e;font-size:1.65rem;">Tk 450</div>
          <div style="color:#4a3f2f;text-decoration:line-through;font-size:.8rem;font-family:sans-serif;">Tk 540</div>
        </div>
      </div>
      <div class="gold-line mb-3" style="background:linear-gradient(90deg,transparent,#0e7a6e,transparent);"></div>
      <div>
        <div class="item-row"><span>🌴</span><div><span style="color:#e8d5b0;font-size:.95rem;">Khejur</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">5 pcs</span></div></div>
        <div class="item-row"><span>🥤</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shorbot (Mint Lemon)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 glass</span></div></div>
        <div class="item-row"><span>🍗</span><div><span class="set2-highlight" style="color:#0e7a6e;">Set-2<span class="set2-tag" style="border-color:#0e7a6e;color:#0e7a6e;background:rgba(14,122,110,.1);">INCLUDED</span></span><br><span style="color:#5a4a30;font-family:sans-serif;font-size:.72rem;font-style:italic;">Egg Fried Rice · Mixed Veg · 2 pcs Fried Chicken</span></div></div>
        <div class="item-row"><span>🥩</span><div><span style="color:#e8d5b0;font-size:.95rem;">Beef Shami Kebab</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3 pcs</span></div></div>
        <div class="item-row"><span>🍢</span><div><span style="color:#e8d5b0;font-size:.95rem;">Seekh Kebab</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2 pcs</span></div></div>
        <div class="item-row"><span>🥣</span><div><span style="color:#e8d5b0;font-size:.95rem;">Halim</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 bowl</span></div></div>
        <div class="item-row"><span>🧅</span><div><span style="color:#e8d5b0;font-size:.95rem;">Piyaju + Beguni</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3+2 pcs</span></div></div>
        <div class="item-row"><span>🍬</span><div><span style="color:#e8d5b0;font-size:.95rem;">Jilapi</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3 pcs</span></div></div>
        <div class="item-row"><span>🍮</span><div><span style="color:#e8d5b0;font-size:.95rem;">Doi</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 cup</span></div></div>
      </div>
      <div class="mt-4 flex justify-between items-center">
        <span class="savings">Save Tk 90 · 17% off</span>
        <div style="width:8px;height:8px;border-radius:50%;background:#0e7a6e;box-shadow:0 0 8px #0e7a6e;"></div>
      </div>
    </div>

    <!-- ─── SET 7 ─── -->
    <div class="menu-card card-7 p-6" style="--card-accent:#a03060;">
      <div class="corner-ornament" style="bottom:-10px;right:-5px;">🌹</div>
      <div class="flex justify-between items-start mb-3">
        <div>
          <h3 style="font-family:'Cinzel',serif;color:#a03060;font-size:1.1rem;letter-spacing:1px;">Set Menu — 7</h3>
          <span class="badge" style="color:#a03060;border-color:#a0306055;background:rgba(160,48,96,.1);">Mutton Special 🐑</span>
        </div>
        <div class="text-right">
          <div class="price-tag" style="color:#a03060;font-size:1.65rem;">Tk 490</div>
          <div style="color:#4a3f2f;text-decoration:line-through;font-size:.8rem;font-family:sans-serif;">Tk 590</div>
        </div>
      </div>
      <div class="gold-line mb-3" style="background:linear-gradient(90deg,transparent,#a03060,transparent);"></div>
      <div>
        <div class="item-row"><span>🌴</span><div><span style="color:#e8d5b0;font-size:.95rem;">Khejur</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">5 pcs</span></div></div>
        <div class="item-row"><span>🥤</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shorbot (Rose)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 glass</span></div></div>
        <div class="item-row"><span>🍗</span><div><span class="set2-highlight" style="color:#a03060;">Set-2<span class="set2-tag" style="border-color:#a03060;color:#a03060;background:rgba(160,48,96,.1);">INCLUDED</span></span><br><span style="color:#5a4a30;font-family:sans-serif;font-size:.72rem;font-style:italic;">Egg Fried Rice · Mixed Veg · 2 pcs Fried Chicken</span></div></div>
        <div class="item-row"><span>🍖</span><div><span style="color:#e8d5b0;font-size:.95rem;">Mutton Tehari</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">½ plate</span></div></div>
        <div class="item-row"><span>🥣</span><div><span style="color:#e8d5b0;font-size:.95rem;">Mutton Halim</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 bowl</span></div></div>
        <div class="item-row"><span>🥩</span><div><span style="color:#e8d5b0;font-size:.95rem;">Mutton Shami Kebab</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2 pcs</span></div></div>
        <div class="item-row"><span>🍆</span><div><span style="color:#e8d5b0;font-size:.95rem;">Beguni + Piyaju</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2+2 pcs</span></div></div>
        <div class="item-row"><span>🍬</span><div><span style="color:#e8d5b0;font-size:.95rem;">Jilapi</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3 pcs</span></div></div>
        <div class="item-row"><span>🍮</span><div><span style="color:#e8d5b0;font-size:.95rem;">Payesh</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 cup</span></div></div>
      </div>
      <div class="mt-4 flex justify-between items-center">
        <span class="savings">Save Tk 100 · 17% off</span>
        <div style="width:8px;height:8px;border-radius:50%;background:#a03060;box-shadow:0 0 8px #a03060;"></div>
      </div>
    </div>

    <!-- ─── SET 8 ─── -->
    <div class="menu-card card-8 p-6" style="--card-accent:#1a5a8a;">
      <div class="corner-ornament" style="bottom:-10px;right:-5px;">🫐</div>
      <div class="flex justify-between items-start mb-3">
        <div>
          <h3 style="font-family:'Cinzel',serif;color:#2a80c0;font-size:1.1rem;letter-spacing:1px;">Set Menu — 8</h3>
          <span class="badge" style="color:#2a80c0;border-color:#2a80c055;background:rgba(42,128,192,.1);">Fish Delight 🐟</span>
        </div>
        <div class="text-right">
          <div class="price-tag" style="color:#2a80c0;font-size:1.65rem;">Tk 460</div>
          <div style="color:#4a3f2f;text-decoration:line-through;font-size:.8rem;font-family:sans-serif;">Tk 550</div>
        </div>
      </div>
      <div class="gold-line mb-3" style="background:linear-gradient(90deg,transparent,#2a80c0,transparent);"></div>
      <div>
        <div class="item-row"><span>🌴</span><div><span style="color:#e8d5b0;font-size:.95rem;">Khejur</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3 pcs</span></div></div>
        <div class="item-row"><span>🥤</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shorbot (Coconut)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 glass</span></div></div>
        <div class="item-row"><span>🍗</span><div><span class="set2-highlight" style="color:#2a80c0;">Set-2<span class="set2-tag" style="border-color:#2a80c0;color:#2a80c0;background:rgba(42,128,192,.1);">INCLUDED</span></span><br><span style="color:#5a4a30;font-family:sans-serif;font-size:.72rem;font-style:italic;">Egg Fried Rice · Mixed Veg · 2 pcs Fried Chicken</span></div></div>
        <div class="item-row"><span>🐟</span><div><span style="color:#e8d5b0;font-size:.95rem;">Fish Fry (Rui / Hilsa)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2 pcs</span></div></div>
        <div class="item-row"><span>🥣</span><div><span style="color:#e8d5b0;font-size:.95rem;">Halim</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 cup</span></div></div>
        <div class="item-row"><span>🧅</span><div><span style="color:#e8d5b0;font-size:.95rem;">Piyaju + Beguni</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3+2 pcs</span></div></div>
        <div class="item-row"><span>🍬</span><div><span style="color:#e8d5b0;font-size:.95rem;">Jilapi</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2 pcs</span></div></div>
        <div class="item-row"><span>🍮</span><div><span style="color:#e8d5b0;font-size:.95rem;">Firni</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 cup</span></div></div>
      </div>
      <div class="mt-4 flex justify-between items-center">
        <span class="savings">Save Tk 90 · 16% off</span>
        <div style="width:8px;height:8px;border-radius:50%;background:#2a80c0;box-shadow:0 0 8px #2a80c0;"></div>
      </div>
    </div>

    <!-- ─── SET 9 ─── -->
    <div class="menu-card card-9 p-6" style="--card-accent:#6a7a10;">
      <div class="corner-ornament" style="bottom:-10px;right:-5px;">🌾</div>
      <div class="flex justify-between items-start mb-3">
        <div>
          <h3 style="font-family:'Cinzel',serif;color:#8a9a20;font-size:1.1rem;letter-spacing:1px;">Set Menu — 9</h3>
          <span class="badge" style="color:#8a9a20;border-color:#8a9a2055;background:rgba(138,154,32,.1);">Deshi Classic 🌾</span>
        </div>
        <div class="text-right">
          <div class="price-tag" style="color:#8a9a20;font-size:1.65rem;">Tk 380</div>
          <div style="color:#4a3f2f;text-decoration:line-through;font-size:.8rem;font-family:sans-serif;">Tk 460</div>
        </div>
      </div>
      <div class="gold-line mb-3" style="background:linear-gradient(90deg,transparent,#8a9a20,transparent);"></div>
      <div>
        <div class="item-row"><span>🌴</span><div><span style="color:#e8d5b0;font-size:.95rem;">Khejur</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">5 pcs</span></div></div>
        <div class="item-row"><span>🥤</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shorbot (Chirer)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 glass</span></div></div>
        <div class="item-row"><span>🍗</span><div><span class="set2-highlight" style="color:#8a9a20;">Set-2<span class="set2-tag" style="border-color:#8a9a20;color:#8a9a20;background:rgba(138,154,32,.1);">INCLUDED</span></span><br><span style="color:#5a4a30;font-family:sans-serif;font-size:.72rem;font-style:italic;">Egg Fried Rice · Mixed Veg · 2 pcs Fried Chicken</span></div></div>
        <div class="item-row"><span>🍲</span><div><span style="color:#e8d5b0;font-size:.95rem;">Khichuri</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 plate</span></div></div>
        <div class="item-row"><span>🥚</span><div><span style="color:#e8d5b0;font-size:.95rem;">Dim Bhuna (Curry Egg)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2 pcs</span></div></div>
        <div class="item-row"><span>🍆</span><div><span style="color:#e8d5b0;font-size:.95rem;">Beguni + Piyaju</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3+3 pcs</span></div></div>
        <div class="item-row"><span>🥣</span><div><span style="color:#e8d5b0;font-size:.95rem;">Halim</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 cup</span></div></div>
        <div class="item-row"><span>🍬</span><div><span style="color:#e8d5b0;font-size:.95rem;">Jilapi + Malpoa</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2+1 pcs</span></div></div>
      </div>
      <div class="mt-4 flex justify-between items-center">
        <span class="savings">Save Tk 80 · 17% off</span>
        <div style="width:8px;height:8px;border-radius:50%;background:#8a9a20;box-shadow:0 0 8px #8a9a20;"></div>
      </div>
    </div>

    <!-- ─── SET 10 ─── -->
    <div class="menu-card card-10 p-6" style="--card-accent:#c05010;">
      <div class="corner-ornament" style="bottom:-10px;right:-5px;">🍖</div>
      <div class="flex justify-between items-start mb-3">
        <div>
          <h3 style="font-family:'Cinzel',serif;color:#c05010;font-size:1.1rem;letter-spacing:1px;">Set Menu — 10</h3>
          <span class="badge" style="color:#c05010;border-color:#c0501055;background:rgba(192,80,16,.1);">Roast & Grill 🍖</span>
        </div>
        <div class="text-right">
          <div class="price-tag" style="color:#c05010;font-size:1.65rem;">Tk 520</div>
          <div style="color:#4a3f2f;text-decoration:line-through;font-size:.8rem;font-family:sans-serif;">Tk 620</div>
        </div>
      </div>
      <div class="gold-line mb-3" style="background:linear-gradient(90deg,transparent,#c05010,transparent);"></div>
      <div>
        <div class="item-row"><span>🌴</span><div><span style="color:#e8d5b0;font-size:.95rem;">Khejur</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">5 pcs</span></div></div>
        <div class="item-row"><span>🥤</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shorbot (Tamarind)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 glass</span></div></div>
        <div class="item-row"><span>🍗</span><div><span class="set2-highlight" style="color:#c05010;">Set-2<span class="set2-tag" style="border-color:#c05010;color:#c05010;background:rgba(192,80,16,.1);">INCLUDED</span></span><br><span style="color:#5a4a30;font-family:sans-serif;font-size:.72rem;font-style:italic;">Egg Fried Rice · Mixed Veg · 2 pcs Fried Chicken</span></div></div>
        <div class="item-row"><span>🍖</span><div><span style="color:#e8d5b0;font-size:.95rem;">Chicken Roast (Full)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 pc</span></div></div>
        <div class="item-row"><span>🥩</span><div><span style="color:#e8d5b0;font-size:.95rem;">Beef Shami + Seekh Kebab</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2+2 pcs</span></div></div>
        <div class="item-row"><span>🥣</span><div><span style="color:#e8d5b0;font-size:.95rem;">Beef Halim</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 bowl</span></div></div>
        <div class="item-row"><span>🍆</span><div><span style="color:#e8d5b0;font-size:.95rem;">Beguni + Piyaju</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3+3 pcs</span></div></div>
        <div class="item-row"><span>🍬</span><div><span style="color:#e8d5b0;font-size:.95rem;">Jilapi + Doi Bora</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3+2 pcs</span></div></div>
        <div class="item-row"><span>🍮</span><div><span style="color:#e8d5b0;font-size:.95rem;">Firni</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 cup</span></div></div>
      </div>
      <div class="mt-4 flex justify-between items-center">
        <span class="savings">Save Tk 100 · 16% off</span>
        <div style="width:8px;height:8px;border-radius:50%;background:#c05010;box-shadow:0 0 8px #c05010;"></div>
      </div>
    </div>

    <!-- ─── SET 11 ─── -->
    <div class="menu-card card-11 p-6" style="--card-accent:#1a6090;">
      <div class="corner-ornament" style="bottom:-10px;right:-5px;">👨‍👩‍👧‍👦</div>
      <div class="flex justify-between items-start mb-3">
        <div>
          <h3 style="font-family:'Cinzel',serif;color:#2a90c0;font-size:1.1rem;letter-spacing:1px;">Set Menu — 11</h3>
          <span class="badge" style="color:#2a90c0;border-color:#2a90c055;background:rgba(42,144,192,.1);">Family Pack 👨‍👩‍👧</span>
        </div>
        <div class="text-right">
          <div class="price-tag" style="color:#2a90c0;font-size:1.65rem;">Tk 550</div>
          <div style="color:#4a3f2f;text-decoration:line-through;font-size:.8rem;font-family:sans-serif;">Tk 660</div>
        </div>
      </div>
      <div class="gold-line mb-3" style="background:linear-gradient(90deg,transparent,#2a90c0,transparent);"></div>
      <p style="color:#5a4a30;font-family:sans-serif;font-size:.72rem;margin-bottom:6px;font-style:italic;">Serves 2 persons</p>
      <div>
        <div class="item-row"><span>🌴</span><div><span style="color:#e8d5b0;font-size:.95rem;">Khejur</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">6 pcs</span></div></div>
        <div class="item-row"><span>🥤</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shorbot (Mixed)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2 glasses</span></div></div>
        <div class="item-row"><span>🍗</span><div><span class="set2-highlight" style="color:#2a90c0;">Set-2 × 2<span class="set2-tag" style="border-color:#2a90c0;color:#2a90c0;background:rgba(42,144,192,.1);">INCLUDED</span></span><br><span style="color:#5a4a30;font-family:sans-serif;font-size:.72rem;font-style:italic;">Egg Fried Rice · Mixed Veg · 2 pcs Fried Chicken (×2)</span></div></div>
        <div class="item-row"><span>🥣</span><div><span style="color:#e8d5b0;font-size:.95rem;">Halim</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2 cups</span></div></div>
        <div class="item-row"><span>🥩</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shami Kebab</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">4 pcs</span></div></div>
        <div class="item-row"><span>🍆</span><div><span style="color:#e8d5b0;font-size:.95rem;">Beguni + Piyaju</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">4+4 pcs</span></div></div>
        <div class="item-row"><span>🍬</span><div><span style="color:#e8d5b0;font-size:.95rem;">Jilapi + Malpoa</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">4+2 pcs</span></div></div>
        <div class="item-row"><span>🍮</span><div><span style="color:#e8d5b0;font-size:.95rem;">Firni + Payesh</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 each</span></div></div>
      </div>
      <div class="mt-4 flex justify-between items-center">
        <span class="savings">Save Tk 110 · 17% off</span>
        <div style="width:8px;height:8px;border-radius:50%;background:#2a90c0;box-shadow:0 0 8px #2a90c0;"></div>
      </div>
    </div>

    <!-- ─── SET 12 ─── -->
    <div class="menu-card card-12 p-6" style="--card-accent:#c8a000;border-color:rgba(200,160,0,.4) !important;">
      <div class="corner-ornament" style="bottom:-10px;right:-5px;">✨</div>
      <!-- Glow border -->
      <div style="position:absolute;inset:0;border-radius:20px;box-shadow:inset 0 0 30px rgba(200,160,0,.08);pointer-events:none;"></div>
      <div class="flex justify-between items-start mb-3">
        <div>
          <h3 style="font-family:'Cinzel',serif;color:#c8a000;font-size:1.1rem;letter-spacing:1px;">Set Menu — 12</h3>
          <span class="badge" style="color:#c8a000;border-color:#c8a00066;background:rgba(200,160,0,.15);font-size:.7rem;">✦ Grand Royal Special ✦</span>
        </div>
        <div class="text-right">
          <div class="price-tag" style="color:#c8a000;font-size:1.65rem;">Tk 590</div>
          <div style="color:#4a3f2f;text-decoration:line-through;font-size:.8rem;font-family:sans-serif;">Tk 720</div>
        </div>
      </div>
      <div class="gold-line mb-3" style="background:linear-gradient(90deg,transparent,#c8a000,transparent);opacity:.8;"></div>
      <div>
        <div class="item-row"><span>🌴</span><div><span style="color:#e8d5b0;font-size:.95rem;">Premium Ajwa Khejur</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">7 pcs</span></div></div>
        <div class="item-row"><span>🥤</span><div><span style="color:#e8d5b0;font-size:.95rem;">Shorbot (Borhani + Rooh Afza)</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2 glasses</span></div></div>
        <div class="item-row"><span>🍗</span><div><span class="set2-highlight" style="color:#c8a000;">Set-2<span class="set2-tag" style="border-color:#c8a000;color:#c8a000;background:rgba(200,160,0,.1);">INCLUDED</span></span><br><span style="color:#5a4a30;font-family:sans-serif;font-size:.72rem;font-style:italic;">Egg Fried Rice · Mixed Veg · 2 pcs Fried Chicken</span></div></div>
        <div class="item-row"><span>🍚</span><div><span style="color:#e8d5b0;font-size:.95rem;">Mutton Biryani</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">½ plate</span></div></div>
        <div class="item-row"><span>🍖</span><div><span style="color:#e8d5b0;font-size:.95rem;">Chicken Roast</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 pc</span></div></div>
        <div class="item-row"><span>🥣</span><div><span style="color:#e8d5b0;font-size:.95rem;">Mutton Halim</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 bowl</span></div></div>
        <div class="item-row"><span>🥩</span><div><span style="color:#e8d5b0;font-size:.95rem;">Beef Shami + Seekh Kebab</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">2+2 pcs</span></div></div>
        <div class="item-row"><span>🍆</span><div><span style="color:#e8d5b0;font-size:.95rem;">Beguni + Piyaju + Doi Bora</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">3+3+3</span></div></div>
        <div class="item-row"><span>🍬</span><div><span style="color:#e8d5b0;font-size:.95rem;">Jilapi + Malpoa</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">4+2 pcs</span></div></div>
        <div class="item-row"><span>🍮</span><div><span style="color:#e8d5b0;font-size:.95rem;">Firni + Payesh + Doi Mishti</span><span style="color:#5a4a30;font-family:sans-serif;font-size:.75rem;float:right;">1 each</span></div></div>
      </div>
      <div class="mt-4 flex justify-between items-center">
        <span class="savings">Save Tk 130 · 18% off</span>
        <div style="width:8px;height:8px;border-radius:50%;background:#c8a000;box-shadow:0 0 12px #c8a000, 0 0 24px rgba(200,160,0,.4);animation:twinkle 2s ease infinite alternate;"></div>
      </div>
    </div>

  </div><!-- /grid -->
</main>

<!-- ═══════════════════  FOOTER  ═══════════════════ -->
<footer class="relative z-10 text-center pb-10 px-4">
  <div class="gold-line mb-6" style="width:40%;max-width:300px;margin-left:auto;margin-right:auto;"></div>
  <div style="display:inline-block;border:1px solid rgba(232,184,75,.15);border-radius:12px;padding:14px 30px;">
    <p style="color:#6a5a40;font-family:'Crimson Text',serif;font-size:.95rem;font-style:italic;">
      ✦ &nbsp; Orders accepted daily from Asr · Pre-order before 3:00 PM &nbsp; ✦
    </p>
    <p style="color:#4a3a28;font-family:sans-serif;font-size:.75rem;margin-top:6px;letter-spacing:1px;">
      ☪ &nbsp; MAY ALLAH ACCEPT YOUR FASTS &nbsp;·&nbsp; RAMADAN MUBARAK &nbsp; ☪
    </p>
  </div>
</footer>

</body>
</html>