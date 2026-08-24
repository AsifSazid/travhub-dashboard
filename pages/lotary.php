<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Premium Name & Number Lottery Draw</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@700;800&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

  <style>
    :root {
      --bg-dark: #080c14;
      --card-bg: rgba(16, 23, 38, 0.85);
      --accent: #6366f1;
      --accent-glow: #818cf8;
      --gold: #f59e0b;
      --gold-glow: #fbbf24;
      --green: #10b981;
      --text: #f8fafc;
      --text-muted: #94a3b8;
      --border: rgba(255, 255, 255, 0.08);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      min-height: 100vh;
      background: radial-gradient(circle at 50% 20%, #1e1b4b 0%, var(--bg-dark) 85%);
      font-family: 'Outfit', sans-serif;
      color: var(--text);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }

    .main-container {
      width: 100%;
      max-width: 1000px;
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 24px;
      align-items: start;
    }

    @media (max-width: 860px) {
      .main-container {
        grid-template-columns: 1fr;
      }
    }

    /* Left Card: Slot & Controller */
    .lottery-wrapper {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 36px 28px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 35px rgba(99, 102, 241, 0.12);
      position: relative;
    }

    .header {
      text-align: center;
      margin-bottom: 24px;
    }

    .header h1 {
      font-size: 26px;
      font-weight: 700;
      letter-spacing: -0.5px;
      background: linear-gradient(135deg, #ffffff 40%, var(--accent-glow));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .header p {
      color: var(--text-muted);
      font-size: 13px;
      margin-top: 4px;
    }

    /* Animated Name Display */
    .name-display-box {
      background: rgba(15, 23, 42, 0.9);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 16px;
      text-align: center;
      margin-bottom: 20px;
      box-shadow: inset 0 2px 10px rgba(0,0,0,0.5);
      position: relative;
      overflow: hidden;
    }

    .name-display-box .label {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: var(--accent-glow);
      font-weight: 700;
      margin-bottom: 4px;
      display: block;
    }

    .name-display-box .current-name {
      font-size: 24px;
      font-weight: 700;
      color: #ffffff;
      min-height: 32px;
      transition: all 0.15s ease;
    }

    .name-display-box.rolling .current-name {
      color: var(--accent-glow);
      transform: scale(0.98);
      filter: blur(0.4px);
    }

    /* Slot Machine Numbers */
    .slot-machine {
      background: rgba(0, 0, 0, 0.65);
      border: 2px solid rgba(255, 255, 255, 0.06);
      border-radius: 18px;
      padding: 18px 12px;
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-bottom: 24px;
      box-shadow: inset 0 4px 20px rgba(0,0,0,0.8), 0 0 20px rgba(99, 102, 241, 0.1);
    }

    .digit-box {
      width: 52px;
      height: 74px;
      background: #111827;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'JetBrains Mono', monospace;
      font-size: 38px;
      font-weight: 800;
      color: var(--gold-glow);
      text-shadow: 0 0 16px rgba(245, 158, 11, 0.5);
      user-select: none;
      transition: transform 0.15s ease, border-color 0.15s ease;
    }

    .digit-box.rolling {
      border-color: var(--accent);
      color: var(--accent-glow);
      text-shadow: 0 0 14px var(--accent);
      transform: scale(0.96);
    }

    /* Form Controls */
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .input-field {
      width: 100%;
      background: rgba(15, 23, 42, 0.7);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 13px 16px;
      color: var(--text);
      font-size: 14px;
      outline: none;
      transition: all 0.2s ease;
    }

    .input-field:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    .btn-spin {
      background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
      color: white;
      font-family: 'Outfit', sans-serif;
      font-size: 16px;
      font-weight: 600;
      border: none;
      border-radius: 12px;
      padding: 15px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      box-shadow: 0 8px 24px rgba(79, 70, 229, 0.35);
      transition: all 0.2s ease;
    }

    .btn-spin:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 12px 28px rgba(79, 70, 229, 0.5);
      background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
    }

    .btn-spin:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      filter: grayscale(0.6);
    }

    .meta-panel {
      margin-top: 20px;
      padding-top: 16px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 13px;
      color: var(--text-muted);
    }

    /* Right Card: Winners History Board */
    .history-card {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 24px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
      min-height: 480px;
      display: flex;
      flex-direction: column;
    }

    .history-card h2 {
      font-size: 18px;
      font-weight: 700;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
      padding-bottom: 12px;
      border-bottom: 1px solid var(--border);
    }

    .history-card h2 .badge {
      background: rgba(99, 102, 241, 0.15);
      color: var(--accent-glow);
      font-size: 12px;
      padding: 3px 8px;
      border-radius: 6px;
    }

    .winners-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      overflow-y: auto;
      max-height: 400px;
      padding-right: 4px;
    }

    .winner-item {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid var(--border);
      border-left: 3px solid var(--gold);
      border-radius: 10px;
      padding: 10px 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      animation: fadeIn 0.3s ease forwards;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-6px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .winner-info .name {
      font-weight: 600;
      font-size: 14px;
      color: #fff;
    }

    .winner-info .tag-title {
      font-size: 11px;
      color: var(--text-muted);
    }

    .winner-number {
      font-family: 'JetBrains Mono', monospace;
      font-weight: 700;
      font-size: 14px;
      color: var(--gold-glow);
      background: rgba(245, 158, 11, 0.1);
      padding: 4px 8px;
      border-radius: 6px;
    }

    .empty-state {
      text-align: center;
      color: var(--text-muted);
      font-size: 13px;
      margin-top: 40px;
    }
  </style>
</head>
<body>

  <div class="main-container">
    <!-- Lottery Box -->
    <div class="lottery-wrapper">
      <div class="header">
        <h1>Grand Lucky Draw</h1>
        <p>Series: 460200–460209 &amp; 460220–460224</p>
      </div>

      <!-- Animated Name Box -->
      <div class="name-display-box" id="nameBox">
        <span class="label">Participant</span>
        <div class="current-name" id="nameDisplay">Click Spin to Draw</div>
      </div>

      <!-- 6-digit slot initialized to 000000 -->
      <div class="slot-machine">
        <div class="digit-box" id="d0">0</div>
        <div class="digit-box" id="d1">0</div>
        <div class="digit-box" id="d2">0</div>
        <div class="digit-box" id="d3">0</div>
        <div class="digit-box" id="d4">0</div>
        <div class="digit-box" id="d5">0</div>
      </div>

      <div class="form-group">
        <input 
          type="text" 
          class="input-field" 
          id="prizeLabel" 
          placeholder="Prize Title / Round Name (e.g. 1st Prize, Gift Round)" 
        />
        <button class="btn-spin" id="spinBtn">
          <span>SPIN &amp; SELECT WINNER</span>
        </button>
      </div>

      <div class="meta-panel">
        <span>Available: <strong id="remainingCount" style="color: var(--text);">10 Names / 15 Numbers</strong></span>
        <button id="resetPoolBtn" style="background: none; border: none; color: var(--accent-glow); cursor: pointer; text-decoration: underline;">Reset All</button>
      </div>
    </div>

    <!-- Live Winners Board -->
    <div class="history-card">
      <h2>
        Winners List
        <span class="badge" id="winCount">0 Drawn</span>
      </h2>
      <div class="winners-list" id="winnersList">
        <div class="empty-state" id="emptyState">No winners drawn yet.</div>
      </div>
    </div>
  </div>

  <script>
    // Initial Names List
    const initialNames = [
      "Tarekul Islam", "Afikur Rahman", "Shakil Mahmud", 
      "Sujon Saiyd", "Asif M Sazid", "Nadim Kamal", 
      "Tauhid Imran", "Imran Hossain", "Shahanoor Tanvir", "Nazmul Shanto"
    ];

    // Authorized Number Pool
    const generateNumbers = () => {
      const nums = [];
      for (let i = 460200; i <= 460209; i++) nums.push(i.toString());
      for (let i = 460220; i <= 460224; i++) nums.push(i.toString());
      return nums;
    };

    let namePool = [...initialNames];
    let numberPool = generateNumbers();
    const winners = [];

    // DOM Elements
    const spinBtn = document.getElementById('spinBtn');
    const resetBtn = document.getElementById('resetPoolBtn');
    const nameBox = document.getElementById('nameBox');
    const nameDisplay = document.getElementById('nameDisplay');
    const prizeLabelInput = document.getElementById('prizeLabel');
    const remainingEl = document.getElementById('remainingCount');
    const winnersListEl = document.getElementById('winnersList');
    const winCountBadge = document.getElementById('winCount');
    const emptyState = document.getElementById('emptyState');
    const digitBoxes = Array.from({ length: 6 }, (_, i) => document.getElementById(`d${i}`));

    const updateStats = () => {
      remainingEl.textContent = `${namePool.length} Names / ${numberPool.length} Numbers`;
      winCountBadge.textContent = `${winners.length} Drawn`;
    };

    const spin = () => {
      if (namePool.length === 0 || numberPool.length === 0) {
        alert('All participants or numbers have been drawn! Please reset.');
        return;
      }

      spinBtn.disabled = true;
      nameBox.classList.add('rolling');
      digitBoxes.forEach(d => d.classList.add('rolling'));

      // 1. Pick Winner & Number
      const nameIndex = Math.floor(Math.random() * namePool.length);
      const selectedName = namePool.splice(nameIndex, 1)[0];

      const numIndex = Math.floor(Math.random() * numberPool.length);
      const selectedNumber = numberPool.splice(numIndex, 1)[0];

      const prizeTitle = prizeLabelInput.value.trim() || `Round #${winners.length + 1}`;

      const duration = 2600; // total animation time (ms)
      const intervalMs = 50;
      const start = Date.now();

      // Rapid shuffle animation
      const rollTimer = setInterval(() => {
        const elapsed = Date.now() - start;

        // Shuffle Names
        const randomName = initialNames[Math.floor(Math.random() * initialNames.length)];
        nameDisplay.textContent = randomName;

        // Shuffle Digits with Staggered Stop
        digitBoxes.forEach((box, idx) => {
          const stopThreshold = (duration / 6) * (idx + 1);
          if (elapsed < stopThreshold) {
            box.textContent = Math.floor(Math.random() * 10);
          } else {
            box.textContent = selectedNumber[idx];
            box.classList.remove('rolling');
          }
        });

        if (elapsed >= duration) {
          clearInterval(rollTimer);
          finalizeWinner(selectedName, selectedNumber, prizeTitle);
        }
      }, intervalMs);
    };

    const finalizeWinner = (winnerName, winnerNumber, prizeTitle) => {
      // Set Final Display
      nameDisplay.textContent = winnerName;
      nameBox.classList.remove('rolling');

      digitBoxes.forEach((box, idx) => {
        box.textContent = winnerNumber[idx];
        box.classList.remove('rolling');
      });

      // Confetti Effect
      confetti({
        particleCount: 90,
        spread: 75,
        origin: { y: 0.65 },
        colors: ['#6366f1', '#f59e0b', '#10b981', '#ffffff']
      });

      // Add to Winner Board
      winners.unshift({ name: winnerName, number: winnerNumber, prize: prizeTitle });
      renderWinnersList();
      updateStats();
      prizeLabelInput.value = '';
      spinBtn.disabled = false;
    };

    const renderWinnersList = () => {
      if (winners.length === 0) {
        winnersListEl.innerHTML = '<div class="empty-state">No winners drawn yet.</div>';
        return;
      }

      winnersListEl.innerHTML = winners
        .map(w => `
          <div class="winner-item">
            <div class="winner-info">
              <div class="name">${w.name}</div>
              <div class="tag-title">${w.prize}</div>
            </div>
            <div class="winner-number">#${w.number}</div>
          </div>
        `)
        .join('');
    };

    resetBtn.addEventListener('click', () => {
      namePool = [...initialNames];
      numberPool = generateNumbers();
      winners.length = 0;
      nameDisplay.textContent = 'Click Spin to Draw';
      digitBoxes.forEach(box => box.textContent = '0');
      renderWinnersList();
      updateStats();
    });

    spinBtn.addEventListener('click', spin);
  </script>
</body>
</html>