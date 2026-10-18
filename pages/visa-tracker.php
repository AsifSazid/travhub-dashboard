<?php
/**
 * FILE PATH: /pages/visa-tracker.php
 * Public visa application status tracker — split layout
 */

require_once '../server/db_connection.php';

$error  = null;
$result = null;
$vtnr   = trim($_POST['vtnr'] ?? '');
$lname  = strtolower(trim($_POST['lastname'] ?? ''));

function generateVTNR(string $workSysId, string $travSysId): string {
    $raw   = $workSysId . '|' . $travSysId;
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $hash  = 5381;
    for ($i = 0; $i < strlen($raw); $i++) {
        $hash = (($hash << 5) + $hash) + ord($raw[$i]);
        $hash = $hash & 0x7FFFFFFF;
    }
    $result = '';
    for ($i = 0; $i < 6; $i++) {
        $seed = $hash;
        for ($j = 0; $j <= $i; $j++) {
            $seed = (($seed << 5) + $seed) + ($i * 31 + $j * 17 + ord($raw[$j % strlen($raw)]));
            $seed = $seed & 0x7FFFFFFF;
        }
        $result .= $chars[abs($seed) % strlen($chars)];
    }
    return $result;
}

function statusInfo(string $s): array {
    return [
        'pending'   => ['label'=>'Pending',   'bg'=>'#F1F5F9','color'=>'#64748B','icon'=>'⏳'],
        'ready'     => ['label'=>'Ready',     'bg'=>'#DCFCE7','color'=>'#15803D','icon'=>'✅'],
        'submitted' => ['label'=>'Submitted', 'bg'=>'#EEF2FF','color'=>'#4F46E5','icon'=>'📤'],
        'approved'  => ['label'=>'Approved',  'bg'=>'#D1FAE5','color'=>'#065F46','icon'=>'🎉'],
        'rejected'  => ['label'=>'Rejected',  'bg'=>'#FEE2E2','color'=>'#B91C1C','icon'=>'❌'],
        'on_hold'   => ['label'=>'On Hold',   'bg'=>'#FEF3C7','color'=>'#92400E','icon'=>'⏸'],
    ][$s] ?? ['label'=>ucfirst($s),'bg'=>'#F1F5F9','color'=>'#64748B','icon'=>'•'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $vtnr && $lname) {
    try {
        $stmt = $pdo->query("SELECT work_sys_id, vs_travelers, meta_data FROM visa_services WHERE status != 'cancelled'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $travelers = json_decode($row['vs_travelers'] ?? '[]', true) ?: [];
            $meta      = json_decode($row['meta_data']    ?? '{}', true) ?: [];
            foreach ($travelers as $t) {
                $genVTNR = generateVTNR($row['work_sys_id'], $t['sys_id'] ?? '');
                if ($genVTNR === $vtnr) {
                    $nameParts = explode(' ', strtolower(trim($t['name'] ?? '')));
                    if (in_array($lname, $nameParts) || str_contains(end($nameParts), $lname)) {
                        $result = ['traveler'=>$t,'meta'=>$meta,'vtnr'=>$genVTNR,'work_sys_id'=>$row['work_sys_id']];
                        break 2;
                    }
                }
            }
        }
        if (!$result) $error = 'No application found. Please check your details.';
    } catch (Throwable $e) {
        $error = 'Lookup failed. Please try again.';
    }
}

$hasResult = !!$result;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Visa Tracker — TravHub</title>
<link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body {
    height: 100%;
    font-family: system-ui, -apple-system, sans-serif;
    background: #F1F5F9;
    color: #0F172A;
}
.layout {
    display: grid;
    grid-template-columns: 380px 1fr;
    height: 100vh;
    overflow: hidden;
}
.left {
    background: linear-gradient(160deg, #1E1B4B 0%, #312E81 60%, #4F46E5 100%);
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 48px 40px;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.left::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.left-content { position: relative; z-index: 1; }
.logo-area { margin-bottom: 36px; }
.logo-area img { height: 36px; filter: brightness(0) invert(1); opacity: .9; }
.logo-area .brand { font-size: 1.1rem; font-weight: 800; opacity: .9; margin-top: 8px; }
h1 { font-size: 1.6rem; font-weight: 800; line-height: 1.25; margin-bottom: 10px; }
.subtitle { font-size: .9rem; opacity: .7; margin-bottom: 36px; line-height: 1.6; }
.form-group { margin-bottom: 16px; }
.form-group label {
    display: block;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    opacity: .7;
    margin-bottom: 6px;
}
.form-group input {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255,255,255,.12);
    border: 1.5px solid rgba(255,255,255,.2);
    border-radius: 10px;
    color: #fff;
    font-size: .95rem;
    outline: none;
    transition: border .15s, background .15s;
}
.form-group input::placeholder { opacity: .45; }
.form-group input:focus {
    border-color: rgba(255,255,255,.6);
    background: rgba(255,255,255,.18);
}
.form-group input.vtnr-input {
    font-family: monospace;
    font-size: 1.2rem;
    letter-spacing: .15em;
}
.btn-check {
    width: 100%;
    padding: 13px;
    background: #fff;
    color: #4F46E5;
    font-weight: 800;
    font-size: .95rem;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    margin-top: 8px;
    transition: transform .1s, opacity .15s;
}
.btn-check:hover { opacity: .92; transform: translateY(-1px); }
.error-msg {
    background: rgba(239,68,68,.2);
    border: 1px solid rgba(239,68,68,.4);
    border-radius: 8px;
    padding: 10px 14px;
    font-size: .82rem;
    margin-top: 14px;
    color: #FCA5A5;
}
.right {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    overflow: hidden;
    background: #F1F5F9;
}
.result-card {
    background: #fff;
    border-radius: 24px;
    width: 100%;
    max-width: 460px;
    box-shadow: 0 8px 40px rgba(0,0,0,.10);
    overflow: hidden;
}
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 11px 0;
    border-bottom: 1px solid #F1F5F9;
    font-size: .875rem;
    gap: 12px;
}
.info-row:last-child { border-bottom: none; }
.info-label {
    color: #94A3B8;
    font-size: .72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .05em;
    flex-shrink: 0;
}
.info-val { font-weight: 700; color: #0F172A; text-align: right; font-size: .875rem; }
.note-box {
    margin: 0 28px 20px;
    background: #FEF3C7;
    border-left: 3px solid #F59E0B;
    border-radius: 0 8px 8px 0;
    padding: 11px 14px;
    font-size: .82rem;
    color: #92400E;
}
</style>
</head>
<body>
<div class="layout">

    <!-- LEFT -->
    <div class="left">
        <div class="left-content">
            <div class="logo-area">
                <img src="../assets/images/logo/travhub-logo.png"
                     onerror="this.style.display='none'" alt="TravHub">
                <div class="brand">TravHub Global Limited</div>
            </div>
            <h1>Track Your Visa Application</h1>
            <p class="subtitle">Enter your VTNR code and last name to see your real-time application status.</p>
            <form method="POST">
                <div class="form-group">
                    <label>VTNR Code</label>
                    <input class="vtnr-input" type="text" name="vtnr"
                           value="<?= htmlspecialchars($_POST['vtnr'] ?? '') ?>"
                           placeholder="e.g. qW3nSd"
                           maxlength="6" autocomplete="off" autocorrect="off"
                           autocapitalize="none" spellcheck="false" required />
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="lastname"
                           value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>"
                           placeholder="e.g. SAZID"
                           autocomplete="off" required />
                </div>
                <button type="submit" class="btn-check">Check Status →</button>
            </form>
            <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT -->
    <div class="right">
        <?php if (!$hasResult): ?>
        <!-- Empty state — no icons, clean -->
        <div style="text-align:center;max-width:300px;">
            <div style="width:64px;height:64px;border-radius:16px;background:#fff;
                        box-shadow:0 4px 20px rgba(0,0,0,.07);
                        display:flex;align-items:center;justify-content:center;
                        margin:0 auto 24px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                     stroke="#CBD5E1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <path d="M2 10h20M6 15h4M14 15h4"/>
                </svg>
            </div>
            <div style="font-size:1rem;font-weight:700;color:#334155;margin-bottom:8px;">
                Track Your Application
            </div>
            <p style="font-size:.85rem;color:#94A3B8;line-height:1.75;">
                Enter your VTNR code and last name on the left to view your visa application status.
            </p>
        </div>

        <?php else:
            $t        = $result['traveler'];
            $meta     = $result['meta'];
            $status   = $t['app_status'] ?? 'pending';
            $si       = statusInfo($status);
            $docs     = $t['docs'] ?? [];
            $uploaded = count(array_filter($docs, fn($d) => $d['status'] === 'uploaded'));
            $total    = count(array_filter($docs, fn($d) => ($d['required'] ?? true) !== false));
            $hasCL    = !empty($t['cover_letter']);
            $approval = $t['approval_doc'] ?? null;
            $notes    = $t['status_notes'] ?? '';
            $docKey   = $approval['doc_key'] ?? (!empty($approval['smb_path']) ? md5($approval['smb_path']) : '');
            $pubUrl   = '../api/visa-services/serve-approval-doc.php?k=' . urlencode($docKey);
        ?>

        <?php if ($status === 'approved'): ?>
        <canvas id="confetti-canvas" style="position:fixed;inset:0;pointer-events:none;z-index:100;width:100%;height:100%;"></canvas>
        <?php endif; ?>

        <div class="result-card">

            <!-- VTNR — top right, gorgeous -->
            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding:18px 24px 0;">
                <span style="font-size:.68rem;font-weight:700;color:#CBD5E1;
                             text-transform:uppercase;letter-spacing:.08em;">
                    Visa Application
                </span>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="font-size:.65rem;color:#CBD5E1;font-weight:600;
                                 text-transform:uppercase;letter-spacing:.06em;">VTNR</span>
                    <span style="font-family:monospace;font-size:.85rem;font-weight:800;
                                 color:#4F46E5;background:#EEF2FF;padding:4px 12px;
                                 border-radius:8px;letter-spacing:.1em;
                                 border:1px solid #C7D2FE;">
                        <?= htmlspecialchars($result['vtnr']) ?>
                    </span>
                </div>
            </div>

            <!-- Status hero -->
            <div style="text-align:center;padding:24px 28px 20px;border-bottom:1px solid #F1F5F9;">
                <div style="font-size:3rem;line-height:1;margin-bottom:12px;">
                    <?= $si['icon'] ?>
                </div>
                <div style="font-size:1.75rem;font-weight:900;color:<?= $si['color'] ?>;
                            letter-spacing:-.02em;margin-bottom:10px;">
                    <?= $si['label'] ?>
                </div>
                <span style="display:inline-block;padding:5px 16px;border-radius:999px;
                             background:<?= $si['bg'] ?>;color:<?= $si['color'] ?>;
                             font-size:.78rem;font-weight:700;">
                    <?= htmlspecialchars($meta['country_name'] ?? '') ?> Visa
                </span>
            </div>

            <!-- Info rows -->
            <div style="padding:4px 28px 12px;">
                <div class="info-row">
                    <span class="info-label">Applicant</span>
                    <span class="info-val"><?= htmlspecialchars($t['name'] ?? '—') ?></span>
                </div>
                <?php if (!empty($t['passport_no'])): ?>
                <div class="info-row">
                    <span class="info-label">Passport</span>
                    <span class="info-val" style="font-family:monospace;font-size:.82rem;">
                        <?= htmlspecialchars($t['passport_no']) ?>
                    </span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Visa</span>
                    <span class="info-val" style="font-size:.82rem;">
                        <?= htmlspecialchars($meta['master_visa_title'] ?? '—') ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Documents</span>
                    <span class="info-val"
                          style="color:<?= $uploaded===$total&&$total>0?'#15803D':'#0F172A' ?>;">
                        <?= $uploaded ?>/<?= $total ?> uploaded
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cover Letter</span>
                    <span class="info-val"
                          style="color:<?= $hasCL?'#15803D':'#64748B' ?>;">
                        <?= $hasCL ? 'Ready' : 'Pending' ?>
                    </span>
                </div>
                <?php if (!empty($t['status_history'])): ?>
                <div class="info-row">
                    <span class="info-label">Last Updated</span>
                    <span class="info-val" style="font-size:.8rem;color:#64748B;">
                        <?= htmlspecialchars(end($t['status_history'])['at'] ?? '—') ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Ready message -->
            <?php if ($status === 'ready'): ?>
            <div style="margin:0 24px 16px;padding:13px 16px;background:#EEF2FF;
                        border-left:3px solid #6366F1;border-radius:0 8px 8px 0;
                        font-size:.84rem;color:#3730A3;line-height:1.65;">
                Your documents are ready. Please complete your payment to proceed with submission.
            </div>
            <?php endif; ?>

            <!-- Approval download — no icon -->
            <?php if ($approval && $docKey): ?>
            <div style="padding:0 24px 24px;">
                <a href="<?= htmlspecialchars($pubUrl) ?>" target="_blank"
                   style="display:flex;align-items:center;justify-content:space-between;
                          padding:16px 20px;
                          background:linear-gradient(135deg,#D1FAE5 0%,#A7F3D0 100%);
                          border:1px solid #6EE7B7;border-radius:12px;
                          text-decoration:none;color:#065F46;
                          transition:opacity .15s;">
                    <div>
                        <div style="font-weight:800;font-size:.9rem;">Download Visa Approval</div>
                        <div style="font-size:.72rem;opacity:.7;margin-top:2px;">
                            <?= htmlspecialchars($approval['filename'] ?? '') ?>
                        </div>
                    </div>
                    <div style="font-size:.82rem;font-weight:700;">Download</div>
                </a>
            </div>
            <?php endif; ?>

            <!-- Notes -->
            <?php if ($notes): ?>
            <div class="note-box">
                <strong>Note:</strong> <?= htmlspecialchars($notes) ?>
            </div>
            <?php endif; ?>

        </div>

        <?php if ($status === 'approved'): ?>
        <script>
        (function(){
            const cvs = document.getElementById('confetti-canvas');
            if (!cvs) return;
            cvs.width = window.innerWidth;
            cvs.height = window.innerHeight;
            const ctx = cvs.getContext('2d');
            const colors = ['#6366F1','#10B981','#F59E0B','#EF4444','#3B82F6','#EC4899','#8B5CF6'];
            const pieces = Array.from({length: 150}, () => ({
                x: Math.random() * cvs.width,
                y: Math.random() * -cvs.height,
                w: Math.random() * 10 + 4,
                h: Math.random() * 5 + 3,
                color: colors[Math.floor(Math.random() * colors.length)],
                speed: Math.random() * 3 + 2,
                angle: Math.random() * Math.PI * 2,
                spin: Math.random() * .2 - .1,
                opacity: 1
            }));
            let frame = 0;
            function draw() {
                ctx.clearRect(0, 0, cvs.width, cvs.height);
                pieces.forEach(p => {
                    p.y += p.speed;
                    p.angle += p.spin;
                    p.x += Math.cos(p.angle) * 1.5;
                    if (frame > 120) p.opacity = Math.max(0, (180 - frame) / 60);
                    ctx.save();
                    ctx.globalAlpha = p.opacity;
                    ctx.translate(p.x, p.y);
                    ctx.rotate(p.angle);
                    ctx.fillStyle = p.color;
                    ctx.fillRect(-p.w/2, -p.h/2, p.w, p.h);
                    ctx.restore();
                });
                frame++;
                if (frame < 180) requestAnimationFrame(draw);
                else ctx.clearRect(0, 0, cvs.width, cvs.height);
            }
            setTimeout(draw, 300);
        })();
        </script>
        <?php endif; ?>

        <?php endif; ?>
    </div>

</div>
</body>
</html>