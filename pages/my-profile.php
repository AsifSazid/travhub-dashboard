<?php
// PATH: pages/my-profile.php
// Self-service profile page — left sidebar layout with Attendance & Leave module.

include_once('./authenticate.php');
require_once __DIR__ . '/../server/db_connection.php';
require_once __DIR__ . '/../server/permissions.php';

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) { $ip_port = "https://dev.travhub.com.bd/"; }
$ip_port = rtrim($ip_port, '/') . '/';

$myEmpId = $_SESSION['user_id']     ?? '';
$myName  = $_SESSION['user_name']   ?? '';
$myEmail = $_SESSION['user_email']  ?? '';
$myDesg  = $_SESSION['designation'] ?? '';
$myRole  = $_SESSION['user_role']   ?? '';
$isHR    = ($myRole === '0') || canAccess($pdo, 'hrm_employee_view');

// Load employee row
$emp = null;
if ($myEmpId) {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE sys_id = ? LIMIT 1");
    $stmt->execute([$myEmpId]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
}

$companyInfo   = json_decode($emp['company_related_info'] ?? '{}', true) ?: [];
$basicInfo     = json_decode($emp['basic_info']           ?? '{}', true) ?: [];
$phoneData     = json_decode($emp['phone']                ?? '{}', true) ?: [];
$emailData     = json_decode($emp['email']                ?? '{}', true) ?: [];
$addressData   = json_decode($emp['address']              ?? '{}', true) ?: [];
$emergencyData = json_decode($emp['emergency_contact']    ?? '{}', true) ?: [];

// Photo
$photoUrl = '';
if (!empty($emp['profile_photo'])) {
    $photoUrl = $ip_port . 'uploads/' . ltrim($emp['profile_photo'], '/') . '?t=' . time();
} else {
    $images = json_decode($emp['image_name'] ?? '', true);
    if (!empty($images[0]['file_path'])) {
        $photoUrl = $ip_port . 'uploads/' . $images[0]['file_path'] . '?t=' . time();
    }
}

$photoFallback = "data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2250%22 fill=%22%231e293b%22/%3E%3Ccircle cx=%2250%22 cy=%2238%22 r=%2216%22 fill=%22%2364748b%22/%3E%3Cellipse cx=%2250%22 cy=%2285%22 rx=%2230%22 ry=%2222%22 fill=%22%2364748b%22/%3E%3C/svg%3E";

$API_BASE  = rtrim($ip_port, '/');
$empStatus = $emp['status'] ?? 'unknown';

function mpVal($v) {
    $v = trim((string)($v ?? ''));
    return $v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : '';
}
?>
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
<body class="bg-gray-50 font-sans">

<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<main id="mainContent" class="pt-16 pl-0 lg:pl-64 lg:my-16 transition-all duration-300">
<div class="p-4 md:p-6 max-w-screen-2xl mx-auto">

    <!-- Page header -->
    <div class="mb-5 flex items-center gap-3">
        <i class="fas fa-user-circle text-2xl text-blue-500"></i>
        <div>
            <h1 class="text-xl font-bold text-gray-800">My Profile</h1>
            <p class="text-sm text-gray-500">View and manage your account details</p>
        </div>
    </div>

    <!-- ── Hero Card ──────────────────────────────────────────── -->
    <div class="bg-gradient-to-r from-slate-800 to-blue-900 rounded-2xl p-5 mb-5 flex flex-col sm:flex-row items-center sm:items-start gap-4">
        <div class="mp-photo-ring" id="mpPhotoRing" title="Click to change photo">
            <img id="mpProfileImg"
                 src="<?= $photoUrl ? htmlspecialchars($photoUrl) : $photoFallback ?>"
                 onerror="this.src='<?= $photoFallback ?>'"
                 alt="Profile Photo">
            <div class="mp-photo-overlay"><i class="fas fa-camera text-white text-xl"></i></div>
        </div>
        <input type="file" id="mpPhotoInput" accept="image/jpeg,image/png,image/webp" class="hidden">

        <div class="text-center sm:text-left flex-1">
            <h2 class="text-2xl font-bold text-white"><?= htmlspecialchars($myName) ?></h2>
            <p class="text-blue-200 mt-0.5"><?= mpVal($myDesg ?: ($companyInfo['designation'] ?? '')) ?: '—' ?></p>
            <p class="text-blue-300 text-sm"><?= mpVal($emp['department_name'] ?? '') ?: '—' ?></p>
            <div class="mt-2 flex flex-wrap gap-2 justify-center sm:justify-start">
                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $empStatus==='active' ? 'bg-green-400/20 text-green-300' : 'bg-red-400/20 text-red-300' ?>">
                    <span class="w-1.5 h-1.5 rounded-full inline-block mr-1 <?= $empStatus==='active'?'bg-green-400':'bg-red-400' ?>"></span>
                    <?= ucfirst($empStatus) ?>
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-white/80">
                    ID: <?= htmlspecialchars($myEmpId) ?>
                </span>
            </div>
        </div>

        <div id="mpPhotoMsg" class="hidden text-xs text-blue-200 self-center text-center"></div>

        <!-- Check-in status widget in hero -->
        <div class="ml-auto self-center flex-shrink-0">
            <div id="mpCheckinWidget" class="bg-white/10 backdrop-blur rounded-xl p-3 text-center min-w-[130px]">
                <div class="text-xs text-blue-200 mb-1"><i class="fas fa-clock mr-1"></i>Today</div>
                <div id="mpCheckinStatus" class="text-sm font-semibold text-white">—</div>
                <div id="mpCheckinTime" class="text-xs text-blue-300 mt-0.5"></div>
                <button id="mpSignOutBtn" class="hidden mt-2 px-3 py-1.5 bg-red-500/80 hover:bg-red-500 text-white text-xs font-semibold rounded-lg transition w-full">
                    <i class="fas fa-sign-out-alt mr-1"></i> Sign Out
                </button>
                <button id="mpCheckInManualBtn" class="hidden mt-2 px-3 py-1.5 bg-green-500/80 hover:bg-green-500 text-white text-xs font-semibold rounded-lg transition w-full">
                    <i class="fas fa-fingerprint mr-1"></i> Check In
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-3 mb-5">
        <?php
        $quickActions = [
            ['icon'=>'fas fa-file-contract',  'label'=>'Appointment Letter', 'id'=>'qaAppoint',  'color'=>'blue'],
            ['icon'=>'fas fa-passport',        'label'=>'NOC Letter',        'id'=>'qaNoc',       'color'=>'purple'],
            ['icon'=>'fas fa-file-invoice-dollar','label'=>'Salary Slip',   'id'=>'qaSalary',    'color'=>'green'],
            ['icon'=>'fas fa-id-card',         'label'=>'ID Card',           'id'=>'qaIdCard',    'color'=>'orange'],
            ['icon'=>'fas fa-share-alt',       'label'=>'Share Profile',     'id'=>'qaShare',     'color'=>'indigo'],
            ['icon'=>'fas fa-business-time',   'label'=>'Visiting Card',     'id'=>'qaVisit',     'color'=>'pink'],
        ];
        $colorMap = [
            'blue'   => 'bg-blue-50 text-blue-600 hover:bg-blue-100 border-blue-100',
            'purple' => 'bg-purple-50 text-purple-600 hover:bg-purple-100 border-purple-100',
            'green'  => 'bg-green-50 text-green-600 hover:bg-green-100 border-green-100',
            'orange' => 'bg-orange-50 text-orange-600 hover:bg-orange-100 border-orange-100',
            'indigo' => 'bg-indigo-50 text-indigo-600 hover:bg-indigo-100 border-indigo-100',
            'pink'   => 'bg-pink-50 text-pink-600 hover:bg-pink-100 border-pink-100',
        ];
        foreach ($quickActions as $qa):
        $cls = $colorMap[$qa['color']];
        ?>
        <button id="<?= $qa['id'] ?>" class="<?= $cls ?> border rounded-xl p-3 flex flex-col items-center gap-1.5 transition cursor-pointer text-center" title="<?= $qa['label'] ?>">
            <i class="<?= $qa['icon'] ?> text-xl"></i>
            <span class="text-xs font-semibold leading-tight"><?= $qa['label'] ?></span>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- ── Main layout: sidebar + content ─────────────────────── -->
    <div class="flex flex-col md:flex-row gap-5">

        <!-- Left sidebar nav -->
        <div class="mp-sidenav">
            <div class="bg-white rounded-2xl shadow p-3 flex flex-row md:flex-col gap-1 overflow-x-auto md:overflow-x-visible">
                <p class="hidden md:block text-xs font-semibold text-gray-400 uppercase tracking-widest px-2 mb-1 mt-1">Account</p>

                <button class="mp-nav-btn mp-active" data-mpsec="myinfo">
                    <span class="mp-nav-icon"><i class="fas fa-id-card"></i></span>
                    <span class="hidden md:inline">My Info</span>
                </button>

                <button class="mp-nav-btn" data-mpsec="attendance">
                    <span class="mp-nav-icon"><i class="fas fa-calendar-check"></i></span>
                    <span class="hidden md:inline">Attendance & Leave</span>
                </button>

                <div class="hidden md:block border-t border-gray-100 my-2"></div>
                <p class="hidden md:block text-xs font-semibold text-gray-400 uppercase tracking-widest px-2 mb-1">More</p>

                <button class="mp-nav-btn" data-mpsec="documents">
                    <span class="mp-nav-icon"><i class="fas fa-folder-open"></i></span>
                    <span class="hidden md:inline">Documents</span>
                </button>

                <button class="mp-nav-btn" data-mpsec="credentials">
                    <span class="mp-nav-icon"><i class="fas fa-key"></i></span>
                    <span class="hidden md:inline">Credentials</span>
                </button>

                <button class="mp-nav-btn" data-mpsec="notifications">
                    <span class="mp-nav-icon"><i class="fas fa-bell"></i></span>
                    <span class="hidden md:inline">Notifications</span>
                    <span id="mpNotifBadge" class="mp-nav-badge hidden">0</span>
                </button>

                <button class="mp-nav-btn" data-mpsec="payroll">
                    <span class="mp-nav-icon"><i class="fas fa-money-bill-wave"></i></span>
                    <span class="hidden md:inline">Payroll</span>
                </button>

                <button class="mp-nav-btn" data-mpsec="annual">
                    <span class="mp-nav-icon"><i class="fas fa-calendar-days"></i></span>
                    <span class="hidden md:inline">Annual Calendar</span>
                </button>

                <button class="mp-nav-btn" data-mpsec="explore">
                    <span class="mp-nav-icon"><i class="fas fa-compass"></i></span>
                    <span class="hidden md:inline">Explore</span>
                </button>
            </div>
        </div>

        <!-- Content area -->
        <div class="flex-1 min-w-0">

            <!-- ═══════════════════════════════════════════════
                 SECTION: My Info
            ════════════════════════════════════════════════ -->
            <div id="mpsec-myinfo" class="mp-section bg-white rounded-2xl shadow overflow-hidden">

                <!-- Inner tab bar -->
                <div class="border-b border-gray-100 flex overflow-x-auto px-4" id="mpITabBar">
                    <button class="mp-itab-btn mp-active" data-mptab="info">
                        <i class="fas fa-user mr-1.5"></i>Personal
                    </button>
                    <button class="mp-itab-btn" data-mptab="contact">
                        <i class="fas fa-phone mr-1.5"></i>Contact
                    </button>
                    <button class="mp-itab-btn" data-mptab="emergency">
                        <i class="fas fa-heart-pulse mr-1.5"></i>Emergency
                    </button>
                    <button class="mp-itab-btn" data-mptab="security">
                        <i class="fas fa-lock mr-1.5"></i>Security
                    </button>
                </div>

                <div class="p-5 md:p-6">

                    <!-- Personal + Employment -->
                    <div id="mptab-info" class="mp-tab-pane">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Personal</p>
                                <?php
                                $personal = [
                                    'Full Name'     => mpVal($emp['name'] ?? ''),
                                    'Date of Birth' => mpVal($basicInfo['date_of_birth'] ?? ''),
                                    'Blood Group'   => mpVal($basicInfo['blood_group'] ?? ''),
                                    'NID No.'       => mpVal($companyInfo['nid_no'] ?? ''),
                                    'Father'        => mpVal($companyInfo['father_name'] ?? ''),
                                    'Mother'        => mpVal($companyInfo['mother_name'] ?? ''),
                                    'Spouse'        => mpVal($companyInfo['spouse_name'] ?? ''),
                                ];
                                foreach ($personal as $lbl => $val): ?>
                                <div class="mp-info-row">
                                    <span class="mp-lbl"><?= $lbl ?></span>
                                    <span class="mp-val <?= $val?'':'empty' ?>"><?= $val?:'Not set' ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-6 md:mt-0">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Employment</p>
                                <?php
                                $repDesg = $companyInfo['reporting_to_designation'] ?? '';
                                $repTo   = trim(($companyInfo['reporting_to_name'] ?? '') . ($repDesg ? ' (' . $repDesg . ')' : ''));
                                $employment = [
                                    'Employee Type' => mpVal(ucfirst($emp['type'] ?? '')),
                                    'Department'    => mpVal($emp['department_name'] ?? ''),
                                    'Designation'   => mpVal($companyInfo['designation'] ?? ''),
                                    'Company Role'  => mpVal($companyInfo['company_role'] ?? ''),
                                    'Date of Join'  => mpVal($companyInfo['date_of_join'] ?? ''),
                                    'Reporting To'  => htmlspecialchars($repTo, ENT_QUOTES, 'UTF-8'),
                                ];
                                foreach ($employment as $lbl => $val): ?>
                                <div class="mp-info-row">
                                    <span class="mp-lbl"><?= $lbl ?></span>
                                    <span class="mp-val <?= $val?'':'empty' ?>"><?= $val?:'Not set' ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div id="mptab-contact" class="mp-tab-pane" style="display:none">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Phone</p>
                                <?php
                                $phoneRows = [
                                    'Primary'   => mpVal(is_array($phoneData['primary_no']   ?? '') ? implode(', ', $phoneData['primary_no'])   : ($phoneData['primary_no']   ?? '')),
                                    'Secondary' => mpVal(is_array($phoneData['secondary_no'] ?? '') ? implode(', ', $phoneData['secondary_no']) : ($phoneData['secondary_no'] ?? '')),
                                ];
                                foreach ($phoneRows as $l => $v): ?>
                                <div class="mp-info-row">
                                    <span class="mp-lbl"><?= $l ?></span>
                                    <span class="mp-val <?= $v?'':'empty' ?>"><?= $v?:'Not set' ?></span>
                                </div>
                                <?php endforeach; ?>

                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3 mt-6">Email</p>
                                <?php
                                $emailRows = [
                                    'Primary'   => mpVal(is_array($emailData['primary']   ?? '') ? implode(', ', $emailData['primary'])   : ($emailData['primary']   ?? '')),
                                    'Secondary' => mpVal(is_array($emailData['secondary'] ?? '') ? implode(', ', $emailData['secondary']) : ($emailData['secondary'] ?? '')),
                                ];
                                foreach ($emailRows as $l => $v): ?>
                                <div class="mp-info-row">
                                    <span class="mp-lbl"><?= $l ?></span>
                                    <span class="mp-val <?= $v?'':'empty' ?>"><?= $v?:'Not set' ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-6 md:mt-0">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Address</p>
                                <?php
                                $addrRows = [
                                    'Line 1'  => $addressData['address_line_1'] ?? '',
                                    'Line 2'  => $addressData['address_line_2'] ?? '',
                                    'City'    => $addressData['city']           ?? '',
                                    'State'   => $addressData['state']          ?? '',
                                    'Zip'     => $addressData['zip_code']       ?? '',
                                    'Country' => $addressData['country']        ?? '',
                                ];
                                $addrRows = array_map(fn($v) => mpVal(is_array($v) ? implode(', ', $v) : $v), $addrRows);
                                foreach ($addrRows as $l => $v): ?>
                                <div class="mp-info-row">
                                    <span class="mp-lbl"><?= $l ?></span>
                                    <span class="mp-val <?= $v?'':'empty' ?>"><?= $v?:'Not set' ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency -->
                    <div id="mptab-emergency" class="mp-tab-pane" style="display:none">
                        <div class="max-w-md">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Emergency Contact</p>
                            <?php
                            $ecRows = [
                                'Contact Person' => mpVal($emergencyData['person']   ?? ''),
                                'Relation'       => mpVal($emergencyData['relation'] ?? ''),
                                'Phone'          => mpVal(is_array($emergencyData['phone']   ?? '') ? implode(', ', $emergencyData['phone'])   : ($emergencyData['phone']   ?? '')),
                                'Address'        => mpVal(is_array($emergencyData['address'] ?? '') ? implode(', ', $emergencyData['address']) : ($emergencyData['address'] ?? '')),
                            ];
                            foreach ($ecRows as $l => $v): ?>
                            <div class="mp-info-row">
                                <span class="mp-lbl"><?= $l ?></span>
                                <span class="mp-val <?= $v?'':'empty' ?>"><?= $v?:'Not set' ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-gray-400 mt-6 flex items-center gap-1.5">
                            <i class="fas fa-info-circle"></i> To update emergency contact, please contact HR.
                        </p>
                    </div>

                    <!-- Security / Password -->
                    <div id="mptab-security" class="mp-tab-pane" style="display:none">
                        <div class="max-w-md">
                            <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                <i class="fas fa-key text-blue-500"></i> Change Password
                            </h3>
                            <div id="mpPwAlert" class="hidden mb-4 p-3 rounded-lg text-sm font-medium"></div>
                            <div class="space-y-4">
                                <div>
                                    <label class="mp-lbl block mb-1">Current Password</label>
                                    <div class="relative">
                                        <input type="password" id="mpCurPw"
                                            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 pr-10"
                                            placeholder="Enter current password">
                                        <button type="button" class="mp-eye-btn absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" data-mpfield="mpCurPw">
                                            <i class="fas fa-eye text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="mp-lbl block mb-1">New Password</label>
                                    <div class="relative">
                                        <input type="password" id="mpNewPw"
                                            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 pr-10"
                                            placeholder="Minimum 6 characters">
                                        <button type="button" class="mp-eye-btn absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" data-mpfield="mpNewPw">
                                            <i class="fas fa-eye text-sm"></i>
                                        </button>
                                    </div>
                                    <div class="mt-1.5 bg-gray-100 rounded-full h-1 overflow-hidden">
                                        <div id="mpStrBar" class="mp-pw-bar h-full w-0 bg-gray-300"></div>
                                    </div>
                                    <p id="mpStrLbl" class="text-xs text-gray-400 mt-0.5"></p>
                                </div>
                                <div>
                                    <label class="mp-lbl block mb-1">Confirm New Password</label>
                                    <div class="relative">
                                        <input type="password" id="mpConPw"
                                            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 pr-10"
                                            placeholder="Re-enter new password">
                                        <button type="button" class="mp-eye-btn absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" data-mpfield="mpConPw">
                                            <i class="fas fa-eye text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                                <button id="mpPwBtn"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition flex items-center justify-center gap-2">
                                    <i class="fas fa-lock-open"></i> <span>Update Password</span>
                                </button>
                            </div>
                            <div class="mt-8 p-4 rounded-xl border <?= $empStatus==='active'?'border-green-200 bg-green-50':'border-red-200 bg-red-50' ?>">
                                <p class="text-sm font-semibold <?= $empStatus==='active'?'text-green-700':'text-red-700' ?> mb-1 flex items-center gap-2">
                                    <i class="fas fa-circle-dot"></i> Account Status: <?= ucfirst($empStatus) ?>
                                </p>
                                <p class="text-xs <?= $empStatus==='active'?'text-green-600':'text-red-600' ?>">
                                    <?php if ($empStatus==='active'): ?>Your account is active and in good standing.
                                    <?php else: ?>Your account is inactive. Please contact HR.<?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div><!-- /myinfo -->


            <!-- ═══════════════════════════════════════════════
                 SECTION: Attendance & Leave
            ════════════════════════════════════════════════ -->
            <div id="mpsec-attendance" class="mp-section" style="display:none">

                <!-- Leave balance cards -->
                <div class="bg-white rounded-2xl shadow p-5 mb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-umbrella-beach text-blue-500"></i> Leave Balance
                            <span id="mpLbYear" class="text-sm text-gray-400 font-normal"></span>
                        </h3>
                        <button id="mpApplyLeaveBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg flex items-center gap-2 transition">
                            <i class="fas fa-plus"></i> Apply Leave
                        </button>
                    </div>
                    <div id="mpLeaveBalances" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        <div class="col-span-full text-center py-6 text-gray-400 text-sm">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Loading balances…
                        </div>
                    </div>
                </div>

                <!-- Calendar + Notes -->
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

                    <!-- Calendar -->
                    <div class="lg:col-span-3 bg-white rounded-2xl shadow p-5">
                        <!-- Nav -->
                        <div class="flex items-center justify-between mb-4">
                            <button id="mpCalPrev" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-500 transition">
                                <i class="fas fa-chevron-left text-sm"></i>
                            </button>
                            <h3 id="mpCalTitle" class="font-semibold text-gray-800 text-base"></h3>
                            <button id="mpCalNext" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-500 transition">
                                <i class="fas fa-chevron-right text-sm"></i>
                            </button>
                        </div>

                        <!-- Day headers -->
                        <div class="mp-cal-grid mb-2">
                            <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                            <div class="text-center text-xs font-semibold text-gray-400 py-1"><?= $d ?></div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Day cells -->
                        <div class="mp-cal-grid" id="mpCalGrid">
                            <div class="col-span-7 text-center py-6 text-gray-400 text-sm">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Loading calendar…
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="mt-4 flex flex-wrap gap-2 text-xs">
                            <?php
                            $legends = [
                                ['mp-d-present','P','Present'],
                                ['mp-d-absent','A','Absent'],
                                ['mp-d-late','L','Late'],
                                ['mp-d-on_leave','OL','On Leave'],
                                ['mp-d-holiday','H','Holiday'],
                                ['mp-d-weekend','','Weekend'],
                            ];
                            foreach ($legends as [$cls,$code,$lbl]): ?>
                            <span class="flex items-center gap-1">
                                <span class="w-5 h-5 rounded <?= $cls ?> flex items-center justify-center font-bold text-xs"><?= $code ?></span>
                                <span class="text-gray-500"><?= $lbl ?></span>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Notes for this month (beside calendar) -->
                    <div class="lg:col-span-2 bg-white rounded-2xl shadow p-5 flex flex-col">
                        <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                            <i class="fas fa-sticky-note text-yellow-500"></i> Notes
                            <span id="mpNotesMonthLabel" class="text-xs font-normal text-gray-400 ml-1"></span>
                        </h3>
                        <p id="mpNotesHint" class="text-xs text-gray-400 mb-3">Select a day on the calendar to view or add notes.</p>
                        <div id="mpNotesSidebar" class="flex-1 space-y-2 overflow-y-auto" style="max-height:420px">
                            <p class="text-xs text-gray-400 text-center py-6">No notes this month.</p>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Leaves (below calendar) -->
                <div class="bg-white rounded-2xl shadow p-5 mt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-list-check text-indigo-500"></i> My Leaves
                        </h3>
                        <button id="mpViewAllLeavesBtn" class="text-xs text-blue-600 hover:text-blue-800 font-semibold border border-blue-200 rounded-lg px-3 py-1 hover:bg-blue-50 transition">
                            View All
                        </button>
                    </div>
                    <div id="mpLeaveList" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="col-span-full text-center py-6 text-gray-400 text-sm">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Loading…
                        </div>
                    </div>
                </div>

                <!-- Monthly summary (attendance stats) -->
                <div class="bg-white rounded-2xl shadow p-5 mt-4">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-chart-bar text-green-500"></i> Monthly Summary
                    </h3>
                    <div id="mpAttSummary" class="grid grid-cols-3 sm:grid-cols-6 gap-3 text-center">
                        <?php
                        $sumItems = [
                            ['id'=>'mpS-present',  'label'=>'Present',  'color'=>'text-green-600',  'bg'=>'bg-green-50'],
                            ['id'=>'mpS-absent',   'label'=>'Absent',   'color'=>'text-red-600',    'bg'=>'bg-red-50'],
                            ['id'=>'mpS-late',     'label'=>'Late',     'color'=>'text-yellow-600', 'bg'=>'bg-yellow-50'],
                            ['id'=>'mpS-half_day', 'label'=>'Half Day', 'color'=>'text-indigo-600', 'bg'=>'bg-indigo-50'],
                            ['id'=>'mpS-on_leave', 'label'=>'On Leave', 'color'=>'text-pink-600',   'bg'=>'bg-pink-50'],
                            ['id'=>'mpS-not_marked','label'=>'Unknown', 'color'=>'text-gray-400',   'bg'=>'bg-gray-50'],
                        ];
                        foreach ($sumItems as $s): ?>
                        <div class="<?= $s['bg'] ?> rounded-xl p-3">
                            <div id="<?= $s['id'] ?>" class="text-2xl font-bold <?= $s['color'] ?>">—</div>
                            <div class="text-xs text-gray-500 mt-0.5"><?= $s['label'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div><!-- /attendance -->


            <!-- ═══════════════════════════════════════════════
                 SECTION: Documents
            ════════════════════════════════════════════════ -->
            <div id="mpsec-documents" class="mp-section" style="display:none">
                <div class="bg-white rounded-2xl shadow p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-folder-open text-orange-500"></i> My Documents
                        </h3>
                        <label class="cursor-pointer bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-4 py-2 rounded-lg flex items-center gap-2 transition">
                            <i class="fas fa-upload"></i> Upload
                            <input type="file" id="mpDocUploadInput" class="hidden" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx">
                        </label>
                    </div>
                    <div id="mpDocUploadProgress" class="hidden mb-4 p-3 rounded-lg bg-blue-50 text-sm text-blue-700">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Uploading…
                    </div>
                    <div id="mpDocsList" class="space-y-2">
                        <div class="text-center py-10 text-gray-400 text-sm">
                            <i class="fas fa-spinner fa-spin text-2xl mb-3 block text-gray-300"></i>Loading…
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════
                 SECTION: Credentials (Private Password Manager)
            ════════════════════════════════════════════════ -->
            <div id="mpsec-credentials" class="mp-section" style="display:none">
                <div class="bg-white rounded-2xl shadow p-5 mb-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-key text-indigo-500"></i> My Credentials
                            </h3>
                            <p class="text-xs text-gray-400 mt-0.5">Passwords are encrypted. Only you can see these.</p>
                        </div>
                        <button id="mpCredAddBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg flex items-center gap-2 transition">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                    <div id="mpCredList" class="space-y-2">
                        <div class="text-center py-10 text-gray-400 text-sm">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Loading…
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════
                 SECTION: Notifications
            ════════════════════════════════════════════════ -->
            <div id="mpsec-notifications" class="mp-section" style="display:none">
                <div class="bg-white rounded-2xl shadow p-5">
                    <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-bell text-yellow-500"></i> Notifications
                    </h3>
                    <div id="mpNotifList" class="space-y-2">
                        <div class="text-center py-10 text-gray-400 text-sm">
                            <i class="fas fa-bell text-4xl mb-3 block text-gray-200"></i>
                            No new notifications.
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════
                 SECTION: Payroll
            ════════════════════════════════════════════════ -->
            <div id="mpsec-payroll" class="mp-section" style="display:none">
                <div class="bg-white rounded-2xl shadow p-5 mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-money-bill-wave text-green-600"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-gray-800">My Payroll</h3>
                        <p class="text-xs text-gray-400">Monthly salary slips & breakdown</p>
                    </div>
                    <select id="mpPayrollYear" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"></select>
                </div>
                <div id="mpPayrollList" class="space-y-3">
                    <div class="text-center py-10 text-gray-400 text-sm">
                        <i class="fas fa-spinner fa-spin text-3xl mb-3 block text-gray-200"></i>
                        Loading payroll data…
                    </div>
                </div>

                <!-- Payroll Detail Modal -->
                <div id="mpPayrollModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center" class="flex">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
                        <div class="bg-gradient-to-r from-green-600 to-emerald-500 px-6 py-4 flex items-center justify-between">
                            <div>
                                <div id="mpPayrollModalMonth" class="text-white font-bold text-lg"></div>
                                <div id="mpPayrollModalStatus" class="text-green-100 text-xs mt-0.5"></div>
                            </div>
                            <button id="mpPayrollModalClose" class="text-white/70 hover:text-white text-2xl leading-none">&times;</button>
                        </div>
                        <div id="mpPayrollModalBody" class="p-5 max-h-[70vh] overflow-y-auto"></div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════
                 SECTION: Annual Calendar (Office Journal)
            ════════════════════════════════════════════════ -->
            <div id="mpsec-annual" class="mp-section" style="display:none">
                <div class="bg-white rounded-2xl shadow p-5">

                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-800 flex items-center gap-2 text-base">
                                <i class="fas fa-calendar-days text-blue-500"></i> Annual Calendar
                                <span class="text-xs font-normal text-gray-400">— Office Journal</span>
                            </h3>
                            <p class="text-xs text-gray-400 mt-0.5">Click any day to see details and add planning notes</p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button id="annualPrevYear" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm flex items-center justify-center transition">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span id="annualYearLabel" class="font-bold text-gray-800 text-base min-w-[3rem] text-center"></span>
                            <button id="annualNextYear" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm flex items-center justify-center transition">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            <button id="annualTodayBtn" class="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-medium flex items-center gap-1.5 transition">
                                <i class="fas fa-crosshairs"></i> Today
                            </button>
                            <button id="annualPrintBtn" class="px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-medium flex items-center gap-1.5 transition">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="flex flex-wrap gap-x-4 gap-y-1.5 mb-4 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-red-100 border border-red-300"></span>Public Holiday</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-orange-100 border border-orange-300"></span>Office Closed</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-yellow-50 border border-yellow-300"></span>Optional</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-pink-100 border border-pink-300"></span>On Leave</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-green-100 border border-green-300"></span>Present</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-rose-50 border border-rose-300"></span>Absent</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-slate-100 border border-slate-300"></span>Weekend</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block bg-violet-100 border border-violet-300"></span>Has Note</span>
                    </div>

                    <!-- Stats bar -->
                    <div id="annualStats" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4"></div>

                    <!-- Main layout: calendar + day planner panel -->
                    <div class="flex gap-4 items-start" id="annualMainLayout">

                        <!-- Calendar grid (left) -->
                        <div class="flex-1 min-w-0">
                            <div id="annualGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                <div class="col-span-full text-center py-12 text-gray-400">
                                    <i class="fas fa-spinner fa-spin text-2xl mb-2 block"></i>
                                    Loading calendar…
                                </div>
                            </div>
                        </div>

                        <!-- Day Planner panel (right) — hidden until a day is clicked -->
                        <div id="annualDayPanel" class="hidden w-72 flex-shrink-0 border border-gray-100 rounded-2xl overflow-hidden shadow-sm sticky top-4">
                            <!-- Panel header -->
                            <div class="bg-gradient-to-r from-blue-600 to-blue-500 text-white px-4 py-3 flex items-center justify-between">
                                <div>
                                    <div id="annualDayPanelDate" class="font-bold text-sm leading-tight"></div>
                                    <div id="annualDayPanelSub" class="text-xs opacity-80 mt-0.5"></div>
                                </div>
                                <button id="annualDayPanelClose" class="w-6 h-6 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-xs transition">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <!-- Day status chip -->
                            <div id="annualDayStatusChip" class="px-4 py-2 border-b border-gray-100 text-xs"></div>

                            <!-- Who's away today (leave/absent list) -->
                            <div id="annualDayAwayBlock" class="hidden px-4 py-2 border-b border-gray-100">
                                <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Away This Day</div>
                                <div id="annualDayAwayList" class="space-y-1 text-xs text-gray-700"></div>
                            </div>

                            <!-- Notes section -->
                            <div class="p-4">
                                <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Planning Notes</div>

                                <!-- Add note form -->
                                <div id="annualNoteForm" class="mb-3">
                                    <input type="hidden" id="annualNoteDate">
                                    <input type="hidden" id="annualNoteSysId">
                                    <input id="annualNoteTitle" type="text" placeholder="Title (optional)"
                                        class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs mb-1.5 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                    <textarea id="annualNoteBody" rows="3" placeholder="Write a planning note…"
                                        class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs resize-none focus:outline-none focus:ring-2 focus:ring-blue-300"></textarea>
                                    <!-- Repeat yearly toggle -->
                                    <label class="flex items-center gap-2 mt-2 cursor-pointer select-none">
                                        <div class="relative">
                                            <input type="checkbox" id="annualNoteRepeat" class="sr-only peer">
                                            <div class="w-8 h-4 bg-gray-200 peer-checked:bg-blue-500 rounded-full transition"></div>
                                            <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full shadow transition peer-checked:translate-x-4"></div>
                                        </div>
                                        <span class="text-[10px] text-gray-500 peer-checked:text-blue-600 font-medium" id="annualRepeatLabel">
                                            <i class="fas fa-rotate mr-0.5"></i> Remind every year on this date
                                        </span>
                                    </label>
                                    <div class="flex gap-1.5 mt-2">
                                        <button id="annualNoteSave" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg py-1.5 transition">
                                            <i class="fas fa-save mr-1"></i>Save
                                        </button>
                                        <button id="annualNoteCancel" class="hidden px-3 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs rounded-lg py-1.5 transition">Cancel</button>
                                    </div>
                                </div>

                                <!-- Notes list -->
                                <div id="annualNotesList" class="space-y-2 max-h-64 overflow-y-auto"></div>
                            </div>
                        </div>

                    </div><!-- /annualMainLayout -->

                    <!-- Holiday list -->
                    <div id="annualHolidayList" class="mt-6 hidden">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                            <i class="fas fa-star text-yellow-500"></i> Holidays This Year
                        </h4>
                        <div id="annualHolidayItems" class="divide-y divide-gray-100 rounded-xl border border-gray-100 overflow-hidden text-sm"></div>
                    </div>

                </div>
            </div><!-- /annual -->

            <!-- ═══════════════════════════════════════════════
                 SECTION: Explore (TravHub sitemap)
            ════════════════════════════════════════════════ -->
            <div id="mpsec-explore" class="mp-section" style="display:none">
                <div class="bg-white rounded-2xl shadow p-5">
                    <h3 class="font-semibold text-gray-800 mb-1 flex items-center gap-2">
                        <i class="fas fa-compass text-blue-500"></i> Explore TravHub
                    </h3>
                    <p class="text-xs text-gray-400 mb-5">Quick access to everything you have permission to use.</p>
                    <?php
                    // Build permission-based sitemap
                    $exploreGroups = [];

                    // Always visible (every logged-in user)
                    $exploreGroups['My Workspace'] = [
                        ['fas fa-home',         'Dashboard',    'index.php'],
                        ['fas fa-user-circle',  'My Profile',   'my-profile.php'],
                        ['fas fa-wallet',       'My PMS',       'my-pms.php'],
                    ];

                    // HR & People
                    $hrItems = [];
                    if (canAccess($pdo,'hrm_employee_view')) {
                        $hrItems[] = ['fas fa-user-tie',        'Employees',        'index-employees.php'];
                        $hrItems[] = ['fas fa-calendar-check',  'Leave Management', 'hrm-leave-management.php'];
                        $hrItems[] = ['fas fa-clock-rotate-left','Attendance',      'hrm-attendance.php'];
                    }
                    if (canAccess($pdo,'eps_view')) {
                        $hrItems[] = ['fas fa-wallet',          'PMS (HR)',         'pms.php'];
                    }
                    if ($hrItems) $exploreGroups['HR & People'] = $hrItems;

                    // Stake Holders
                    $exploreGroups['Stake Holders'] = [
                        ['fas fa-users',    'Clients',   'index-clients.php'],
                        ['fas fa-users',    'Travelers', 'index-travelers.php'],
                        ['fas fa-handshake','Vendors',   'index-vendors.php'],
                    ];

                    // Working Area
                    $exploreGroups['Working Area'] = [
                        ['fas fa-tasks',       'Leads',          'index-leads.php'],
                        ['fas fa-circle-plus', 'New Lead',       'create-leads.php'],
                        ['fas fa-tasks',       'Works',          'index-works.php'],
                        ['fas fa-circle-plus', 'New Work',       'create-work.php'],
                        ['fas fa-check-circle','Completed Entry','completed-work-entry.php'],
                    ];

                    // Finance
                    $finItems = [
                        ['fas fa-bangladeshi-taka-sign','Accounting','accounts.php'],
                        ['fas fa-receipt',              'Invoices',  'index-invoice.php'],
                        ['fas fa-chart-bar',            'Analytics', 'analytics.php'],
                    ];
                    if (canAccess($pdo,'entry_expense'))  $finItems[] = ['fas fa-receipt',        'Record Expense',   'accounts-expense.php'];
                    if (canAccess($pdo,'entry_asset'))    $finItems[] = ['fas fa-desktop',         'Record Asset',     'accounts-asset.php'];
                    if (canAccess($pdo,'loans_view'))     $finItems[] = ['fas fa-hand-holding-dollar','Loans',         'loans-list.php'];
                    if (canAccess($pdo,'gateway_settle')) $finItems[] = ['fas fa-building-columns','Settlements',      'pending-gateway-settlements.php'];
                    $exploreGroups['Finance'] = $finItems;

                    // Reports
                    $rpItems = [];
                    if (canAccess($pdo,'report_profit'))     $rpItems[] = ['fas fa-money-bill-trend-up','Profit','report-profit.php'];
                    if (canAccess($pdo,'report_cashflow'))   $rpItems[] = ['fas fa-water',              'Cashflow','report-cashflow.php'];
                    if (canAccess($pdo,'report_payment'))    $rpItems[] = ['fas fa-square-caret-up',    'Payment','report-payment.php'];
                    if (canAccess($pdo,'report_receive'))    $rpItems[] = ['fas fa-square-caret-down',  'Receive','report-receive.php'];
                    if (canAccess($pdo,'report_sale'))       $rpItems[] = ['fas fa-circle-check',       'Sale','report-sale.php'];
                    if (canAccess($pdo,'report_purchase'))   $rpItems[] = ['fas fa-square-check',       'Purchase','report-purchase.php'];
                    if (canAccess($pdo,'report_payable'))    $rpItems[] = ['fas fa-circle-up',          'A/C Payable','report-ac_payable.php'];
                    if (canAccess($pdo,'report_receivable')) $rpItems[] = ['fas fa-circle-down',        'A/C Receivable','report-ac_receivable.php'];
                    if (canAccess($pdo,'report_expense'))    $rpItems[] = ['fas fa-receipt',            'Expense','report-expense.php'];
                    if (canAccess($pdo,'report_asset'))      $rpItems[] = ['fas fa-desktop',            'Fixed Assets','report-asset.php'];
                    $rpItems[] = ['fas fa-gauge-high',       'KPI Report','report-kpi.php'];
                    $rpItems[] = ['fas fa-diagram-project',  'Work Coverage','report-work-coverage.php'];
                    if ($rpItems) $exploreGroups['Reports'] = $rpItems;

                    // Tools
                    $exploreGroups['Tools'] = [
                        ['fas fa-passport',    'Air Ticket Calc',       'index-at-calculation.php'],
                        ['fas fa-passport',    'Passport Extraction',   'passport-info-extraction.php'],
                        ['fas fa-hotel',       'Hotel Extraction',      'hotel-info-extraction.php'],
                        ['fas fa-compress',    'File Compressor',       'file-compressor.php'],
                        ['fab fa-flickr',      'Social Media Post',     'index-smpost.php'],
                    ];

                    // Quotations
                    $exploreGroups['Quotations'] = [
                        ['fas fa-ticket',  'Air Ticket Quotations', 'index-air-ticket-quotations.php'],
                        ['fas fa-hotel',   'Hotel Quotations',      'index-hotel-quotations.php'],
                    ];

                    // Packages
                    $exploreGroups['Packages'] = [
                        ['fas fa-circle-plus',     'Create Package', 'create-package.php'],
                        ['fas fa-table-list',       'Package List',  'index-packages.php'],
                    ];

                    // Settings
                    $settingsItems = [['fas fa-cog','Settings','settings.php']];
                    if (($_SESSION['user_role'] ?? '') === '0') $settingsItems[] = ['fas fa-user-shield','Permissions','manage-permissions.php'];
                    $exploreGroups['Settings'] = $settingsItems;

                    foreach ($exploreGroups as $group => $items):
                    ?>
                    <div class="mb-5">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2"><?= htmlspecialchars($group) ?></p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
                            <?php foreach($items as [$icon,$label,$href]): ?>
                            <a href="<?= htmlspecialchars($href) ?>" class="flex items-center gap-2.5 p-3 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50 transition group">
                                <span class="w-8 h-8 rounded-lg bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center text-gray-400 group-hover:text-blue-600 transition flex-shrink-0">
                                    <i class="<?= $icon ?> text-sm"></i>
                                </span>
                                <span class="text-sm text-gray-600 group-hover:text-blue-700 font-medium leading-tight"><?= htmlspecialchars($label) ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div><!-- /content -->
    </div><!-- /main layout -->
</div>
</main>

<!-- ═══════════════════════════════════════
     APPLY LEAVE MODAL
════════════════════════════════════════ -->
<div id="mpLeaveModal" class="mp-modal-bg" style="display:none">
    <div class="mp-modal">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-calendar-plus text-blue-500"></i> Apply for Leave
            </h3>
            <button id="mpLeaveModalClose" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <div id="mpLeaveFormAlert" class="hidden mb-4 p-3 rounded-lg text-sm font-medium"></div>

        <div class="space-y-4">
            <div>
                <label class="mp-lbl block mb-1">Leave Type</label>
                <select id="mpLeaveType" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Loading types…</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mp-lbl block mb-1">From Date</label>
                    <input type="date" id="mpLeaveFrom" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="mp-lbl block mb-1">To Date</label>
                    <input type="date" id="mpLeaveTo" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
            </div>

            <!-- Bridge calculation preview -->
            <div id="mpBridgeInfo" class="hidden p-3 bg-blue-50 rounded-lg text-sm text-blue-700">
                <i class="fas fa-info-circle mr-1"></i>
                <span id="mpBridgeText"></span>
            </div>

            <div>
                <label class="mp-lbl block mb-1">Reason</label>
                <textarea id="mpLeaveReason" rows="3"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none"
                    placeholder="Optional reason for leave"></textarea>
            </div>

            <button id="mpLeaveSubmitBtn"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition flex items-center justify-center gap-2">
                <i class="fas fa-paper-plane"></i> Submit Application
            </button>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════
     ALL LEAVES MODAL
════════════════════════════════════════ -->
<div id="mpAllLeavesModal" class="mp-modal-bg" style="display:none">
    <div class="mp-modal" style="max-width:640px">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-list-check text-indigo-500"></i> All Leave Applications
            </h3>
            <button id="mpAllLeavesClose" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div id="mpAllLeavesList" class="space-y-2 max-h-[60vh] overflow-y-auto"></div>
    </div>
</div>

<!-- ═══════════════════════════════════════
     DAY DETAIL MODAL
════════════════════════════════════════ -->
<div id="mpDayModal" class="mp-modal-bg" style="display:none">
    <div class="mp-modal max-w-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 id="mpDayModalTitle" class="text-base font-bold text-gray-800"></h3>
            <button id="mpDayModalClose" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div id="mpDayModalBody"></div>
    </div>
</div>

<!-- ═══════════════════════════════════════
     CREDENTIAL MODAL
════════════════════════════════════════ -->
<div id="mpCredModal" class="mp-modal-bg" style="display:none">
    <div class="mp-modal">
        <div class="flex items-center justify-between mb-5">
            <h3 id="mpCredModalTitle" class="text-base font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-key text-indigo-500"></i> Add Credential
            </h3>
            <button id="mpCredModalClose" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div id="mpCredFormAlert" class="hidden mb-4 p-3 rounded-lg text-sm font-medium"></div>
        <input type="hidden" id="mpCredSysId">
        <div class="space-y-3">
            <div>
                <label class="mp-lbl block mb-1">Title *</label>
                <input type="text" id="mpCredTitle" placeholder="e.g. Company Email" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>
            <div>
                <label class="mp-lbl block mb-1">URL</label>
                <input type="url" id="mpCredUrl" placeholder="https://" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>
            <div>
                <label class="mp-lbl block mb-1">Username / Email</label>
                <input type="text" id="mpCredUsername" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>
            <div>
                <label class="mp-lbl block mb-1">Password</label>
                <div class="relative">
                    <input type="password" id="mpCredPassword" placeholder="Leave blank to keep unchanged" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 pr-10">
                    <button type="button" id="mpCredPwEye" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye text-sm"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="mp-lbl block mb-1">Notes</label>
                <textarea id="mpCredNotes" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"></textarea>
            </div>
            <button id="mpCredSaveBtn" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition flex items-center justify-center gap-2">
                <i class="fas fa-save"></i> Save
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════
     SIGN OUT CONFIRM MODAL
════════════════════════════════════════ -->
<div id="mpSignOutModal" class="mp-modal-bg" style="display:none">
    <div class="mp-modal max-w-sm text-center">
        <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-sign-out-alt text-2xl text-red-500"></i>
        </div>
        <h3 class="text-base font-bold text-gray-800 mb-1">Sign Out for Today?</h3>
        <p id="mpSignOutDuration" class="text-sm text-gray-500 mb-5"></p>
        <div class="flex gap-3">
            <button id="mpSignOutCancel" class="flex-1 border border-gray-200 text-gray-600 font-semibold rounded-lg px-4 py-2.5 text-sm hover:bg-gray-50 transition">Cancel</button>
            <button id="mpSignOutConfirm" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition">
                <i class="fas fa-check mr-1.5"></i> Confirm
            </button>
        </div>
    </div>
</div>

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
<script src="../assets/js/functional/dashboard.js?time=<?php echo time(); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const API_BASE   = "<?= rtrim($API_BASE, '/') ?>";
    const MY_SYS_ID  = "<?= htmlspecialchars($myEmpId, ENT_QUOTES) ?>";
    const IS_HR      = <?= $isHR ? 'true' : 'false' ?>;

    const MONTH_NAMES = ['January','February','March','April','May','June',
                         'July','August','September','October','November','December'];

    /* ══════════════════════════════════════════
       1. LEFT SIDEBAR NAVIGATION
    ══════════════════════════════════════════ */
    const navBtns = document.querySelectorAll('.mp-nav-btn[data-mpsec]');
    navBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.disabled) return;
            const sec = this.getAttribute('data-mpsec');
            navBtns.forEach(b => b.classList.remove('mp-active'));
            this.classList.add('mp-active');
            document.querySelectorAll('.mp-section').forEach(s => { s.style.display = 'none'; });
            const el = document.getElementById('mpsec-' + sec);
            if (el) el.style.display = 'block';
            if (sec === 'attendance'    && !mpAttLoaded)    { mpAttLoaded    = true; loadAttendanceFull(); }
            if (sec === 'documents'     && !mpDocsLoaded)  { mpDocsLoaded   = true; loadDocs(); }
            if (sec === 'notifications' && !mpNotifLoaded) { mpNotifLoaded  = true; loadNotifications(); }
            if (sec === 'payroll'       && !mpPayrollLoaded){ mpPayrollLoaded= true; loadPayroll(); }
            if (sec === 'annual'        && !mpAnnualLoaded){ mpAnnualLoaded  = true; initAnnualCalendar(); }
        });
    });

    /* ══════════════════════════════════════════
       2. INNER TABS (My Info section)
    ══════════════════════════════════════════ */
    const iTabBtns  = document.querySelectorAll('#mpITabBar .mp-itab-btn');
    const iTabPanes = document.querySelectorAll('#mpsec-myinfo .mp-tab-pane');

    // Init: first pane visible
    iTabPanes.forEach((p, i) => { p.style.display = i === 0 ? 'block' : 'none'; });
    if (iTabBtns[0]) iTabBtns[0].classList.add('mp-active');

    function switchInnerTab(tabId) {
        iTabBtns.forEach(b => b.classList.remove('mp-active'));
        iTabPanes.forEach(p => { p.style.display = 'none'; });
        const btn  = document.querySelector('#mpITabBar [data-mptab="' + tabId + '"]');
        const pane = document.getElementById('mptab-' + tabId);
        if (btn)  btn.classList.add('mp-active');
        if (pane) pane.style.display = 'block';
    }
    iTabBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); e.stopPropagation();
            switchInnerTab(this.getAttribute('data-mptab'));
        });
    });

    /* ══════════════════════════════════════════
       3. PASSWORD EYE + STRENGTH
    ══════════════════════════════════════════ */
    document.querySelectorAll('.mp-eye-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const inp = document.getElementById(this.getAttribute('data-mpfield'));
            if (!inp) return;
            const toText = inp.type === 'password';
            inp.type = toText ? 'text' : 'password';
            this.querySelector('i').className = toText ? 'fas fa-eye-slash text-sm' : 'fas fa-eye text-sm';
        });
    });

    const newPwInput = document.getElementById('mpNewPw');
    if (newPwInput) {
        newPwInput.addEventListener('input', function() {
            const v = this.value;
            let score = 0;
            if (v.length >= 6)          score++;
            if (v.length >= 10)         score++;
            if (/[A-Z]/.test(v))        score++;
            if (/[0-9]/.test(v))        score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            const levels = [
                {w:'0%',  cls:'bg-gray-300',  txt:''},
                {w:'25%', cls:'bg-red-400',    txt:'Weak'},
                {w:'50%', cls:'bg-orange-400', txt:'Fair'},
                {w:'75%', cls:'bg-yellow-400', txt:'Good'},
                {w:'90%', cls:'bg-blue-400',   txt:'Strong'},
                {w:'100%',cls:'bg-green-500',  txt:'Very Strong'},
            ];
            const lvl = v.length ? levels[Math.min(score,5)] : levels[0];
            const bar = document.getElementById('mpStrBar');
            const lbl = document.getElementById('mpStrLbl');
            bar.style.width = lvl.w;
            bar.className   = 'mp-pw-bar h-full ' + lvl.cls;
            lbl.textContent = lvl.txt;
            lbl.className   = 'text-xs mt-0.5 ' + lvl.cls.replace('bg-','text-');
        });
    }

    /* ══════════════════════════════════════════
       4. CHANGE PASSWORD
    ══════════════════════════════════════════ */
    const pwBtn = document.getElementById('mpPwBtn');
    if (pwBtn) {
        pwBtn.addEventListener('click', async function() {
            const alertEl = document.getElementById('mpPwAlert');
            const curPw   = document.getElementById('mpCurPw').value.trim();
            const newPw   = document.getElementById('mpNewPw').value;
            const conPw   = document.getElementById('mpConPw').value;

            const showAlert = (msg, ok) => {
                alertEl.textContent = msg;
                alertEl.className = 'mb-4 p-3 rounded-lg text-sm font-medium '
                    + (ok ? 'bg-green-50 text-green-700 border border-green-200'
                          : 'bg-red-50 text-red-700 border border-red-200');
                alertEl.classList.remove('hidden');
            };

            if (!curPw||!newPw||!conPw) { showAlert('Please fill all fields.',false); return; }
            if (newPw !== conPw)        { showAlert('New passwords do not match.',false); return; }
            if (newPw.length < 6)       { showAlert('Password must be at least 6 characters.',false); return; }

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating…';
            try {
                const res  = await fetch(API_BASE + '/api/auth/change-password.php', {
                    method:'POST', headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({current_password:curPw, new_password:newPw, confirm_password:conPw})
                });
                const data = await res.json();
                showAlert(data.message, data.success);
                if (data.success) {
                    ['mpCurPw','mpNewPw','mpConPw'].forEach(id => document.getElementById(id).value='');
                    const bar = document.getElementById('mpStrBar');
                    if (bar) { bar.style.width='0%'; bar.className='mp-pw-bar h-full bg-gray-300'; }
                    document.getElementById('mpStrLbl').textContent='';
                }
            } catch(_) { showAlert('Network error. Please try again.',false); }
            finally {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-lock-open"></i> <span>Update Password</span>';
            }
        });
    }

    /* ══════════════════════════════════════════
       5. PROFILE PHOTO UPLOAD
    ══════════════════════════════════════════ */
    const photoRing  = document.getElementById('mpPhotoRing');
    const photoInput = document.getElementById('mpPhotoInput');
    const photoMsg   = document.getElementById('mpPhotoMsg');

    if (photoRing && photoInput) {
        photoRing.addEventListener('click', () => photoInput.click());
        photoInput.addEventListener('change', async function() {
            if (!this.files[0]) return;
            photoMsg.textContent = 'Uploading…'; photoMsg.classList.remove('hidden');
            const fd = new FormData();
            fd.append('sys_id', MY_SYS_ID);
            fd.append('photo',  this.files[0]);
            try {
                const res  = await fetch(API_BASE + '/api/employees/upload-photo.php', {method:'POST', body:fd});
                const data = await res.json();
                if (data.success) {
                    document.getElementById('mpProfileImg').src = API_BASE+'/uploads/'+data.path+'?t='+Date.now();
                    photoMsg.textContent = '✓ Photo updated!';
                    setTimeout(() => photoMsg.classList.add('hidden'), 2500);
                } else {
                    photoMsg.textContent = '✗ ' + (data.message || 'Upload failed');
                }
            } catch(_) { photoMsg.textContent = '✗ Network error'; }
            this.value = '';
        });
    }

    /* ══════════════════════════════════════════
       6. ATTENDANCE & LEAVE MODULE
    ══════════════════════════════════════════ */
    let mpAttLoaded    = false;
    let mpAnnualLoaded = false;
    let mpCalYear    = new Date().getFullYear();
    let mpCalMonth   = new Date().getMonth() + 1; // 1-based
    let mpLeaveTypes = [];

    const STATUS_MAP = {
        present:   {cls:'mp-d-present',  code:'P'},
        absent:    {cls:'mp-d-absent',   code:'A'},
        late:      {cls:'mp-d-late',     code:'L'},
        half_day:  {cls:'mp-d-half_day', code:'H'},
        on_leave:  {cls:'mp-d-on_leave', code:'OL'},
        weekend:   {cls:'mp-d-weekend',  code:''},
        holiday:   {cls:'mp-d-holiday',  code:'H'},
        future:    {cls:'mp-d-future',   code:''},
        not_marked:{cls:'mp-d-not_marked',code:'?'},
    };

    async function apiPost(endpoint, body) {
        const res = await fetch(API_BASE + endpoint, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify(body)
        });
        return res.json();
    }

    async function loadAttendance() {
        await Promise.all([loadLeaveBalances(), loadCalendar(), loadLeaveList(), loadLeaveTypes()]);
    }

    /* ── Leave balances ── */
    async function loadLeaveBalances() {
        const el = document.getElementById('mpLeaveBalances');
        const yr = document.getElementById('mpLbYear');
        try {
            const data = await apiPost('/api/leaves/endpoints.php', {action:'balances', year: mpCalYear});
            if (yr) yr.textContent = '(' + mpCalYear + ')';
            if (!data.success) { el.innerHTML='<p class="text-sm text-red-500 col-span-full">'+data.message+'</p>'; return; }
            if (!data.data.length) { el.innerHTML='<p class="text-sm text-gray-400 col-span-full text-center py-4">No leave types configured.</p>'; return; }
            el.innerHTML = data.data.map(b => `
                <div class="rounded-xl p-3 border border-gray-100 hover:shadow-sm transition">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-3 h-3 rounded-full flex-shrink-0" style="background:${b.color}"></span>
                        <span class="text-xs font-semibold text-gray-700 truncate">${b.name}</span>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <span class="text-2xl font-bold text-gray-800">${b.remaining}</span>
                            <span class="text-xs text-gray-400">/${b.allocated}</span>
                        </div>
                        <span class="text-xs text-gray-400">Used: ${b.used}${b.pending>0 ? ' · Pend: '+b.pending : ''}</span>
                    </div>
                    <div class="mt-2 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full transition-all" style="width:${b.allocated>0?Math.min(100,Math.round((b.used/b.allocated)*100)):0}%; background:${b.color}"></div>
                    </div>
                </div>
            `).join('');
        } catch(_) {
            el.innerHTML='<p class="text-sm text-red-500 col-span-full">Failed to load balances.</p>';
        }
    }

    /* ── Calendar ── */
    async function loadCalendar() {
        const grid  = document.getElementById('mpCalGrid');
        const title = document.getElementById('mpCalTitle');
        title.textContent = MONTH_NAMES[mpCalMonth-1] + ' ' + mpCalYear;
        grid.innerHTML = '<div class="col-span-7 text-center py-6 text-gray-400 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i>Loading…</div>';

        try {
            const data = await apiPost('/api/attendance/endpoints.php', {action:'my_month', year:mpCalYear, month:mpCalMonth});
            if (!data.success) { grid.innerHTML='<p class="text-red-500 col-span-7 text-sm text-center py-4">'+data.message+'</p>'; return; }

            // Update summary
            const s = data.summary || {};
            Object.keys(s).forEach(k => {
                const el = document.getElementById('mpS-'+k);
                if (el) el.textContent = s[k];
            });

            // Build calendar grid
            // Find what weekday the first day is (JS: 0=Sun, so we need offset)
            const firstDow = new Date(mpCalYear, mpCalMonth-1, 1).getDay(); // 0=Sun
            let html = '';
            // Empty cells before first day
            for (let i=0; i<firstDow; i++) {
                html += '<div class="mp-cal-day mp-d-empty"></div>';
            }

            data.data.forEach(d => {
                const st  = STATUS_MAP[d.att_status] || {cls:'mp-d-not_marked', code:'?'};
                const tip = d.is_holiday ? (d.holiday_title||'Holiday') :
                            d.att_status === 'on_leave' ? (d.leave_name||'On Leave') :
                            d.att_status;
                html += `<div class="mp-cal-day ${st.cls}" title="${tip} · ${d.date}">
                    <span class="mp-day-num">${d.day}</span>
                    ${st.code ? `<span class="mp-day-code">${st.code}</span>` : ''}
                </div>`;
            });

            grid.innerHTML = html;
        } catch(_) {
            grid.innerHTML='<p class="text-red-500 col-span-7 text-sm text-center py-4">Failed to load calendar.</p>';
        }
    }

    // NOTE: Calendar nav listeners attached in loadCalendarWithNotes block below
    // to avoid double-fire. Do NOT add listeners here.

    /* ── Leave list ── */
    const STATUS_BADGE = {
        pending:   'bg-yellow-100 text-yellow-700',
        approved:  'bg-green-100 text-green-700',
        rejected:  'bg-red-100 text-red-700',
        cancelled: 'bg-gray-100 text-gray-500',
    };

    let mpAllLeaves = []; // full list for view-all modal

    async function loadLeaveList() {
        const el = document.getElementById('mpLeaveList');
        try {
            const data = await apiPost('/api/leaves/endpoints.php', {action:'list', year: new Date().getFullYear()});
            if (!data.success) { el.innerHTML='<p class="text-sm text-red-500 col-span-full">'+data.message+'</p>'; return; }
            mpAllLeaves = data.data || [];

            if (!mpAllLeaves.length) {
                el.innerHTML='<p class="text-sm text-gray-400 text-center py-8 col-span-full">No leave applications this year.</p>';
                return;
            }

            const today   = new Date().toISOString().slice(0,10);
            const upcoming = mpAllLeaves.filter(la => la.date_from >= today || la.status === 'pending').slice(0, 3);
            const toShow   = upcoming.length ? upcoming : mpAllLeaves.slice(0,3);

            el.innerHTML = toShow.map(la => renderLeaveCard(la)).join('');
        } catch(_) {
            el.innerHTML='<p class="text-sm text-red-500 text-center py-4 col-span-full">Failed to load.</p>';
        }
    }

    function renderLeaveCard(la) {
        const badge = STATUS_BADGE[la.status] || 'bg-gray-100 text-gray-500';
        return `<div class="p-3 rounded-xl border border-gray-100 hover:bg-gray-50 transition">
            <div class="flex items-start justify-between gap-2 mb-1">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 mt-0.5" style="background:${la.color||'#94a3b8'}"></span>
                    <span class="text-sm font-semibold text-gray-700 truncate">${la.leave_name}</span>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full flex-shrink-0 ${badge}">${la.status}</span>
            </div>
            <div class="text-xs text-gray-500 ml-4">
                ${la.date_from} → ${la.date_to} · <strong>${la.total_days}d</strong>
                ${la.bridged_days > 0 ? '<span class="text-blue-500 ml-1">+'+la.bridged_days+' bridged</span>' : ''}
            </div>
            ${la.status==='pending' ? `<button class="mt-2 ml-4 text-xs text-red-500 hover:text-red-700 font-semibold" onclick="mpCancelLeave('${la.sys_id}')">Cancel</button>` : ''}
        </div>`;
    }

    window.mpCancelLeave = async function(sysId) {
        if (!confirm('Cancel this leave application?')) return;
        try {
            const data = await apiPost('/api/leaves/endpoints.php', {action:'cancel', sys_id:sysId});
            if (data.success) {
                await Promise.all([loadLeaveList(), loadLeaveBalances(), loadCalendarWithNotes()]);
            } else {
                alert(data.message || 'Failed to cancel.');
            }
        } catch(_) { alert('Network error.'); }
    };

    /* ── Leave types (for apply modal) ── */
    async function loadLeaveTypes() {
        try {
            const data = await apiPost('/api/leaves/endpoints.php', {action:'types'});
            if (data.success) {
                mpLeaveTypes = data.data;
                const sel = document.getElementById('mpLeaveType');
                sel.innerHTML = '<option value="">— Select leave type —</option>'
                    + data.data.map(t => `<option value="${t.sys_id}">${t.name} (${t.code})</option>`).join('');
            }
        } catch(_) {}
    }

    /* ══════════════════════════════════════════
       7. APPLY LEAVE MODAL
    ══════════════════════════════════════════ */
    const leaveModal      = document.getElementById('mpLeaveModal');
    const leaveModalClose = document.getElementById('mpLeaveModalClose');

    // View All Leaves modal
    const allLeavesModal  = document.getElementById('mpAllLeavesModal');
    document.getElementById('mpAllLeavesClose').addEventListener('click', () => { allLeavesModal.style.display='none'; });
    allLeavesModal.addEventListener('click', e => { if(e.target===allLeavesModal) allLeavesModal.style.display='none'; });
    document.getElementById('mpViewAllLeavesBtn').addEventListener('click', () => {
        const listEl = document.getElementById('mpAllLeavesList');
        if (!mpAllLeaves.length) {
            listEl.innerHTML = '<p class="text-sm text-gray-400 text-center py-8">No leave applications this year.</p>';
        } else {
            listEl.innerHTML = mpAllLeaves.map(la => renderLeaveCard(la)).join('');
        }
        allLeavesModal.style.display='flex';
    });
    const leaveFromInput  = document.getElementById('mpLeaveFrom');
    const leaveToInput    = document.getElementById('mpLeaveTo');
    const bridgeInfo      = document.getElementById('mpBridgeInfo');
    const bridgeText      = document.getElementById('mpBridgeText');

    document.getElementById('mpApplyLeaveBtn').addEventListener('click', () => {
        leaveModal.style.display = 'flex';
        document.getElementById('mpLeaveFormAlert').classList.add('hidden');
        bridgeInfo.classList.add('hidden');
        // Set min date to today
        const today = new Date().toISOString().split('T')[0];
        leaveFromInput.min = today;
        leaveToInput.min   = today;
    });
    leaveModalClose.addEventListener('click', () => { leaveModal.style.display='none'; });
    leaveModal.addEventListener('click', e => { if(e.target===leaveModal) leaveModal.style.display='none'; });

    /* Bridge preview calc (client-side: counts Fri+Sat only, no live holiday check) */
    function updateBridgePreview() {
        const f = leaveFromInput.value;
        const t = leaveToInput.value;
        if (!f || !t || f > t) { bridgeInfo.classList.add('hidden'); return; }
        let calDays = 0, workDays = 0;
        const cur = new Date(f);
        const end = new Date(t);
        while (cur <= end) {
            calDays++;
            const dow = cur.getDay(); // 0=Sun…6=Sat; Fri=5,Sat=6
            if (dow !== 5 && dow !== 6) workDays++;
            cur.setDate(cur.getDate() + 1);
        }
        const bridged = calDays - workDays;
        bridgeText.innerHTML = `<strong>${workDays} working day(s)</strong> selected.`
            + (bridged > 0 ? ` <strong>${bridged}</strong> weekend/holiday day(s) bridged (not deducted).` : '');
        bridgeInfo.classList.remove('hidden');
    }
    leaveFromInput.addEventListener('change', () => { if(leaveToInput.value && leaveToInput.value<leaveFromInput.value) leaveToInput.value=leaveFromInput.value; updateBridgePreview(); });
    leaveToInput.addEventListener('change', updateBridgePreview);

    document.getElementById('mpLeaveSubmitBtn').addEventListener('click', async function() {
        const alertEl = document.getElementById('mpLeaveFormAlert');
        const showFA  = (msg, ok) => {
            alertEl.textContent = msg;
            alertEl.className = 'mb-4 p-3 rounded-lg text-sm font-medium '
                + (ok ? 'bg-green-50 text-green-700 border border-green-200'
                      : 'bg-red-50 text-red-700 border border-red-200');
            alertEl.classList.remove('hidden');
        };

        const ltId   = document.getElementById('mpLeaveType').value;
        const from   = leaveFromInput.value;
        const to     = leaveToInput.value;
        const reason = document.getElementById('mpLeaveReason').value.trim();

        if (!ltId)    { showFA('Please select a leave type.',false); return; }
        if (!from||!to) { showFA('Please select both dates.',false); return; }
        if (from > to) { showFA('From date cannot be after To date.',false); return; }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';

        try {
            const data = await apiPost('/api/leaves/endpoints.php', {
                action:'apply', leave_type_sys_id:ltId, date_from:from, date_to:to, reason
            });
            if (data.success) {
                showFA('Leave application submitted successfully!', true);
                document.getElementById('mpLeaveType').value  = '';
                leaveFromInput.value = ''; leaveToInput.value = '';
                document.getElementById('mpLeaveReason').value = '';
                bridgeInfo.classList.add('hidden');
                setTimeout(() => { leaveModal.style.display='none'; }, 1800);
                await Promise.all([loadLeaveBalances(), loadLeaveList(), loadCalendar()]);
            } else {
                showFA(data.message || 'Failed to submit.', false);
            }
        } catch(_) { showFA('Network error. Please try again.', false); }
        finally {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Application';
        }
    });

    /* ══════════════════════════════════════════
       7b. CLICKABLE CALENDAR DAYS
    ══════════════════════════════════════════ */
    let mpCalDayData = {}; // date → day object
    let mpNotesCache    = {}; // date → [note, note, ...] (multi-note per day)
    let mpSelectedDate  = null; // currently selected calendar date

    // Override loadCalendar to also make days clickable and store data
    const _origLoadCalendar = loadCalendar;

    async function loadCalendarWithNotes() {
        const grid  = document.getElementById('mpCalGrid');
        const title = document.getElementById('mpCalTitle');
        title.textContent = MONTH_NAMES[mpCalMonth-1] + ' ' + mpCalYear;
        grid.innerHTML = '<div class="col-span-7 text-center py-6 text-gray-400 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i>Loading…</div>';

        try {
            const attData = await apiPost('/api/attendance/endpoints.php', {action:'my_month', year:mpCalYear, month:mpCalMonth});
            if (!attData.success) { grid.innerHTML='<p class="text-red-500 col-span-7 text-sm text-center py-4">'+attData.message+'</p>'; return; }

            // Fetch notes separately — graceful if table not ready
            let notesRaw = {};
            let notesHasNote = {};
            try {
                const notesData = await apiPost('/api/notes/endpoints.php', {action:'month', year:mpCalYear, month:mpCalMonth});
                if (notesData.success) { notesRaw = notesData.data || {}; notesHasNote = notesData.has_note || {}; }
            } catch(_) {}

            // Update summary
            const s = attData.summary || {};
            Object.keys(s).forEach(k => { const el=document.getElementById('mpS-'+k); if(el) el.textContent=s[k]; });

            // Store day data and notes
            mpCalDayData = {};
            attData.data.forEach(d => { mpCalDayData[d.date] = d; });
            mpNotesCache = notesRaw; // date → [note, note, ...]
            const mpHasNote = notesHasNote;

            // Update month label on notes sidebar
            const lbl = document.getElementById('mpNotesMonthLabel');
            if (lbl) lbl.textContent = MONTH_NAMES[mpCalMonth-1] + ' ' + mpCalYear;
            // Reset selected date if it's not in the new month
            if (mpSelectedDate && !attData.data.find(d => d.date === mpSelectedDate)) {
                mpSelectedDate = null;
                const hint = document.getElementById('mpNotesHint');
                if (hint) hint.style.display = '';
            }

            // Render notes sidebar
            const selD = mpSelectedDate ? mpCalDayData[mpSelectedDate] : null;
            renderNotesSidebar(selD || null);

            const firstDow = new Date(mpCalYear, mpCalMonth-1, 1).getDay();
            let html = '';
            for (let i=0; i<firstDow; i++) html += '<div class="mp-cal-day mp-d-empty"></div>';

            attData.data.forEach(d => {
                const st      = STATUS_MAP[d.att_status] || {cls:'mp-d-not_marked', code:'?'};
                const hasNote = !!(mpHasNote && mpHasNote[d.date]);
                html += `<div class="mp-cal-day ${st.cls} cursor-pointer" data-mpdate="${d.date}" title="${d.att_status} · ${d.date}">
                    <span class="mp-day-num">${d.day}</span>
                    ${st.code ? `<span class="mp-day-code">${st.code}</span>` : ''}
                    ${hasNote ? '<span style="position:absolute;top:2px;right:3px;font-size:.5rem;color:#6366f1">●</span>' : ''}
                </div>`;
            });

            grid.innerHTML = html;

            // Attach click handlers — select day, update notes panel inline (no modal)
            grid.querySelectorAll('.mp-cal-day[data-mpdate]').forEach(el => {
                const d = mpCalDayData[el.getAttribute('data-mpdate')];
                if (d) { el.addEventListener('click', () => selectCalendarDay(d)); }
            });
            // Re-highlight if a day was already selected this month
            if (mpSelectedDate && mpCalDayData[mpSelectedDate]) {
                grid.querySelectorAll('.mp-cal-day[data-mpdate]').forEach(el => {
                    el.classList.toggle('mp-d-selected', el.getAttribute('data-mpdate') === mpSelectedDate);
                });
            }
        } catch(_) {
            grid.innerHTML='<p class="text-red-500 col-span-7 text-sm text-center py-4">Failed to load calendar.</p>';
        }
    }

    // Bind calendar nav buttons ONCE (clone to strip any stale listeners)
    (function() {
        const prev = document.getElementById('mpCalPrev');
        const next = document.getElementById('mpCalNext');
        const np   = prev.cloneNode(true);
        const nn   = next.cloneNode(true);
        prev.parentNode.replaceChild(np, prev);
        next.parentNode.replaceChild(nn, next);
        np.addEventListener('click', () => {
            mpCalMonth--; if(mpCalMonth<1){mpCalMonth=12;mpCalYear--;} loadCalendarWithNotes();
        });
        nn.addEventListener('click', () => {
            mpCalMonth++; if(mpCalMonth>12){mpCalMonth=1;mpCalYear++;} loadCalendarWithNotes();
        });
    })();

    function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    /* ── Day selector (replaces modal popup) ── */
    function selectCalendarDay(d) {
        mpSelectedDate = d.date;
        // Highlight selected day in grid
        document.querySelectorAll('#mpCalGrid .mp-cal-day[data-mpdate]').forEach(el => {
            el.classList.toggle('mp-d-selected', el.getAttribute('data-mpdate') === d.date);
        });
        // Update notes panel header
        const lbl = document.getElementById('mpNotesMonthLabel');
        if (lbl) lbl.textContent = d.day_name + ', ' + d.date;
        const hint = document.getElementById('mpNotesHint');
        if (hint) hint.style.display = 'none';
        renderNotesSidebar(d);
    }

    /* ── Notes sidebar renderer ── */
    function renderNotesSidebar(selectedDay) {
        const sb = document.getElementById('mpNotesSidebar');
        if (!sb) return;

        // ── DAY MODE: a specific day is selected ──────────────
        if (selectedDay) {
            const d = selectedDay;
            const st = STATUS_MAP[d.att_status] || {cls:'mp-d-not_marked',code:'?'};
            const notes = Array.isArray(mpNotesCache[d.date]) ? mpNotesCache[d.date] : [];
            const todayStr = new Date().toISOString().slice(0,10);

            let html = '';

            // Attendance status chip
            html += `<div class="flex items-center gap-2 mb-3 pb-3 border-b border-gray-100">
                <span class="w-7 h-7 rounded-lg ${st.cls} flex items-center justify-center font-bold text-xs flex-shrink-0">${st.code||'—'}</span>
                <span class="text-xs font-semibold text-gray-600 capitalize">${d.att_status.replace(/_/g,' ')}</span>
                ${d.check_in  ? `<span class="ml-auto text-xs text-green-600"><i class="fas fa-sign-in-alt mr-1"></i>${d.check_in}</span>` : ''}
                ${d.check_out ? `<span class="text-xs text-red-500"><i class="fas fa-sign-out-alt mr-1"></i>${d.check_out}</span>` : ''}
            </div>`;

            if (d.leave_name) {
                html += `<div class="mb-3 px-2 py-1.5 rounded-lg bg-pink-50 text-xs text-pink-700"><i class="fas fa-umbrella-beach mr-1"></i>${escHtml(d.leave_name)}</div>`;
            }
            if (d.is_holiday && d.holiday_title) {
                html += `<div class="mb-3 px-2 py-1.5 rounded-lg bg-green-50 text-xs text-green-700"><i class="fas fa-star mr-1"></i>${escHtml(d.holiday_title)}</div>`;
            }

            // ── Add/Edit note form (always at top) ──
            html += `<div class="mb-3">
                <p class="text-xs font-semibold text-gray-500 mb-2" id="mpSbFormLabel">Add Note</p>
                <input id="mpSbTitle" type="text" maxlength="200" placeholder="Title (optional)"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs mb-1.5 focus:outline-none focus:ring-2 focus:ring-blue-300">
                <textarea id="mpSbBody" rows="3" placeholder="Write your note…"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs resize-none focus:outline-none focus:ring-2 focus:ring-blue-300"></textarea>
                <input type="hidden" id="mpSbSysId" value="">
                <div class="flex gap-2 mt-1.5">
                    <button id="mpSbSaveBtn" data-date="${d.date}"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg px-3 py-1.5 transition flex items-center justify-center gap-1.5">
                        <i class="fas fa-save"></i> Save Note
                    </button>
                    <button id="mpSbCancelEdit" class="hidden border border-gray-200 text-gray-500 hover:bg-gray-50 text-xs font-semibold rounded-lg px-3 py-1.5 transition">
                        Cancel
                    </button>
                </div>
            </div>`;

            // ── Existing notes (newest first, below form) ──
            if (notes.length) {
                const sorted = [...notes].sort((a,b) => (b.created_at||'') > (a.created_at||'') ? 1 : -1);
                html += `<div class="border-t border-gray-100 pt-3 space-y-2">`;
                sorted.forEach(n => {
                    const ts  = n.created_at ? n.created_at.slice(0,16).replace('T',' ') : '';
                    const tid = 'mpns-' + n.sys_id;
                    html += `<div class="rounded-xl border border-yellow-100 bg-yellow-50 overflow-hidden text-xs">
                        <div class="flex items-center gap-1.5 px-3 py-2 cursor-pointer hover:bg-yellow-100 transition"
                             onclick="document.getElementById('${tid}').classList.toggle('hidden')">
                            <i class="fas fa-sticky-note text-yellow-500 flex-shrink-0"></i>
                            <div class="flex-1 min-w-0">
                                ${n.title ? `<span class="font-semibold text-gray-700 block truncate">${escHtml(n.title)}</span>` : ''}
                                ${!n.title && n.note_text ? `<span class="text-gray-500 truncate block">${escHtml((n.note_text||'').slice(0,40))}</span>` : ''}
                            </div>
                            <i class="fas fa-chevron-down text-gray-300 flex-shrink-0"></i>
                        </div>
                        <div id="${tid}" class="hidden px-3 pb-3 border-t border-yellow-100">
                            ${n.title ? `<p class="font-bold text-gray-700 mt-2">${escHtml(n.title)}</p>` : ''}
                            ${n.note_text ? `<p class="text-gray-600 mt-1 whitespace-pre-wrap">${escHtml(n.note_text)}</p>` : ''}
                            ${ts ? `<p class="text-gray-300 mt-1.5">${ts}</p>` : ''}
                            <div class="flex gap-3 mt-2">
                                <button class="text-blue-500 hover:underline mpInlineEdit"
                                    data-id="${n.sys_id}" data-title="${escHtml(n.title||'')}" data-body="${escHtml(n.note_text||'')}">
                                    <i class="fas fa-edit mr-0.5"></i>Edit
                                </button>
                                <button class="text-red-400 hover:underline mpInlineDel" data-id="${n.sys_id}" data-date="${d.date}">
                                    <i class="fas fa-trash mr-0.5"></i>Delete
                                </button>
                            </div>
                        </div>
                    </div>`;
                });
                html += `</div>`;
            }

            // Apply leave button
            if (!d.is_weekend && d.date >= todayStr && !['on_leave','holiday','weekend'].includes(d.att_status)) {
                html += `<button id="mpSbApplyLeave" data-date="${d.date}"
                    class="mt-2 w-full border border-blue-200 text-blue-600 hover:bg-blue-50 text-xs font-semibold rounded-lg px-3 py-1.5 transition flex items-center justify-center gap-1.5">
                    <i class="fas fa-calendar-plus"></i> Apply Leave from this date
                </button>`;
            }

            sb.innerHTML = html;

            // Wire up save button
            document.getElementById('mpSbSaveBtn').addEventListener('click', async function() {
                const date  = this.getAttribute('data-date');
                const title = (document.getElementById('mpSbTitle').value||'').trim();
                const text  = (document.getElementById('mpSbBody').value||'').trim();
                const sysId = document.getElementById('mpSbSysId').value;
                if (!title && !text) { alert('Please enter a title or note.'); return; }
                this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                try {
                    let res;
                    if (sysId) {
                        res = await apiPost('/api/notes/endpoints.php', {action:'update', sys_id:sysId, title, note_text:text});
                        if (res.success) {
                            const arr = mpNotesCache[date]||[];
                            const idx = arr.findIndex(n=>n.sys_id===sysId);
                            if (idx>=0) { arr[idx].title=title; arr[idx].note_text=text; }
                        }
                    } else {
                        res = await apiPost('/api/notes/endpoints.php', {action:'add', date, title, note_text:text});
                        if (res.success && res.note) {
                            if (!mpNotesCache[date]) mpNotesCache[date]=[];
                            mpNotesCache[date].push(res.note);
                        }
                    }
                    if (res.success) {
                        // Refresh calendar dot and re-render panel
                        loadCalendarWithNotes().then(() => {
                            const freshD = mpCalDayData[date];
                            if (freshD) selectCalendarDay(freshD);
                        });
                    } else { alert(res.message||'Failed.'); this.disabled=false; }
                } catch(_){ alert('Network error.'); this.disabled=false; }
            });

            // Cancel edit button
            const cancelEditBtn = document.getElementById('mpSbCancelEdit');
            if (cancelEditBtn) {
                cancelEditBtn.addEventListener('click', function() {
                    document.getElementById('mpSbTitle').value = '';
                    document.getElementById('mpSbBody').value  = '';
                    document.getElementById('mpSbSysId').value = '';
                    document.getElementById('mpSbFormLabel').textContent = 'Add Note';
                    document.getElementById('mpSbSaveBtn').innerHTML = '<i class="fas fa-save"></i> Save Note';
                    cancelEditBtn.classList.add('hidden');
                });
            }

            // Wire up inline edit buttons
            sb.querySelectorAll('.mpInlineEdit').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('mpSbTitle').value = this.getAttribute('data-title');
                    document.getElementById('mpSbBody').value  = this.getAttribute('data-body');
                    document.getElementById('mpSbSysId').value = this.getAttribute('data-id');
                    document.getElementById('mpSbFormLabel').textContent = 'Edit Note';
                    document.getElementById('mpSbSaveBtn').innerHTML = '<i class="fas fa-save"></i> Update Note';
                    const cb = document.getElementById('mpSbCancelEdit');
                    if (cb) cb.classList.remove('hidden');
                    document.getElementById('mpSbTitle').focus();
                });
            });

            // Wire up inline delete buttons
            sb.querySelectorAll('.mpInlineDel').forEach(btn => {
                btn.addEventListener('click', async function() {
                    if (!confirm('Delete this note?')) return;
                    const sysId = this.getAttribute('data-id');
                    const date2 = this.getAttribute('data-date');
                    try {
                        const r = await apiPost('/api/notes/endpoints.php', {action:'delete', sys_id:sysId});
                        if (r.success) {
                            if (mpNotesCache[date2]) {
                                mpNotesCache[date2] = mpNotesCache[date2].filter(n=>n.sys_id!==sysId);
                                if (!mpNotesCache[date2].length) delete mpNotesCache[date2];
                            }
                            loadCalendarWithNotes().then(() => {
                                const freshD = mpCalDayData[date2];
                                if (freshD) selectCalendarDay(freshD);
                            });
                        } else { alert(r.message||'Failed.'); }
                    } catch(_){ alert('Network error.'); }
                });
            });

            // Wire up Apply Leave
            const applyBtn = document.getElementById('mpSbApplyLeave');
            if (applyBtn) {
                applyBtn.addEventListener('click', function() {
                    const date = this.getAttribute('data-date');
                    document.getElementById('mpLeaveFrom').value = date;
                    document.getElementById('mpLeaveTo').value   = date;
                    leaveModal.style.display='flex';
                    document.getElementById('mpLeaveFormAlert').classList.add('hidden');
                    bridgeInfo.classList.add('hidden');
                    setTimeout(updateBridgePreview, 50);
                });
            }

            return;
        }

        // ── MONTH MODE: no day selected, show all month notes ────
        const allNotes = [];
        Object.entries(mpNotesCache).forEach(([date, notes]) => {
            if (Array.isArray(notes)) notes.forEach(n => allNotes.push({...n, _date: date}));
        });
        allNotes.sort((a,b) => (a._date+(a.created_at||'')) < (b._date+(b.created_at||'')) ? -1 : 1);

        if (!allNotes.length) {
            sb.innerHTML = '<p class="text-xs text-gray-400 text-center py-6">No notes this month. Select a day to add one.</p>';
            return;
        }

        sb.innerHTML = allNotes.map(n => {
            const d   = mpCalDayData[n._date] || {};
            const ts  = n.created_at ? n.created_at.slice(0,16).replace('T',' ') : '';
            const tid = 'mpnote-' + n.sys_id;
            return `<div class="rounded-xl border border-yellow-100 bg-yellow-50 overflow-hidden text-xs">
                <div class="flex items-center gap-1.5 px-3 py-2 cursor-pointer hover:bg-yellow-100 transition"
                     onclick="document.getElementById('${tid}').classList.toggle('hidden')">
                    <i class="fas fa-sticky-note text-yellow-500 flex-shrink-0"></i>
                    <div class="flex-1 min-w-0">
                        <span class="font-semibold text-gray-700">${n._date}${d.day_name?' ('+d.day_name+')':''}</span>
                        ${n.title ? `<span class="block text-gray-500 truncate">${escHtml(n.title)}</span>` : ''}
                        ${!n.title && n.note_text ? `<span class="block text-gray-400 truncate">${escHtml((n.note_text||'').slice(0,40))}</span>` : ''}
                    </div>
                    <i class="fas fa-chevron-down text-gray-300 flex-shrink-0"></i>
                </div>
                <div id="${tid}" class="hidden px-3 pb-3 border-t border-yellow-100">
                    ${n.title ? `<p class="font-bold text-gray-700 mt-2">${escHtml(n.title)}</p>` : ''}
                    ${n.note_text ? `<p class="text-gray-600 mt-1 whitespace-pre-wrap">${escHtml(n.note_text)}</p>` : ''}
                    ${ts ? `<p class="text-gray-300 mt-1.5">${ts}</p>` : ''}
                    <button class="text-blue-500 hover:underline mt-1.5"
                        onclick="const fd=mpCalDayData['${n._date}']; if(fd) selectCalendarDay(fd);">
                        <i class="fas fa-arrow-right mr-0.5"></i>Open day
                    </button>
                </div>
            </div>`;
        }).join('');
    }

    window.mpEditNoteSidebar = function(sysId, title, body, date) {
        const d = mpCalDayData[date];
        if (d) selectCalendarDay(d);
    };
    window.mpDeleteNote = async function(sysId, date) {
        if (!confirm('Delete this note?')) return;
        try {
            const r = await apiPost('/api/notes/endpoints.php', {action:'delete', sys_id:sysId});
            if (r.success) {
                if (mpNotesCache[date]) {
                    mpNotesCache[date] = mpNotesCache[date].filter(n => n.sys_id !== sysId);
                    if (!mpNotesCache[date].length) delete mpNotesCache[date];
                }
                renderNotesSidebar();
                loadCalendarWithNotes();
            } else { alert(r.message||'Failed.'); }
        } catch(_){ alert('Network error.'); }
    };
    window.mpOpenNoteDay = function(date) {
        const d = mpCalDayData[date];
        if (d) selectCalendarDay(d);
    };

    async function loadAttendanceFull() {
        await Promise.all([loadLeaveBalances(), loadCalendarWithNotes(), loadLeaveList(), loadLeaveTypes()]);
    }

    /* ── Day detail modal ── */
    const dayModal      = document.getElementById('mpDayModal');
    const dayModalClose = document.getElementById('mpDayModalClose');
    dayModalClose.addEventListener('click', () => { dayModal.style.display='none'; });
    dayModal.addEventListener('click', e => { if(e.target===dayModal) dayModal.style.display='none'; });

    // openDayModal kept as alias — UI now uses selectCalendarDay (no popup)
    function openDayModal(d, opts) {
        selectCalendarDay(d);
        return;
        // (legacy modal code below is unused but kept for reference)
        const titleEl = document.getElementById('mpDayModalTitle');
        const bodyEl  = document.getElementById('mpDayModalBody');
        const st      = STATUS_MAP[d.att_status] || {cls:'mp-d-not_marked',code:'?'};
        const existingNotes = Array.isArray(mpNotesCache[d.date]) ? mpNotesCache[d.date] : [];

        titleEl.textContent = d.date + ' · ' + d.day_name;

        // ── Attendance status row ──
        let html = `<div class="flex items-center gap-2 mb-4">
            <span class="w-8 h-8 rounded-lg ${st.cls} flex items-center justify-center font-bold text-sm">${st.code||'—'}</span>
            <span class="text-sm font-semibold text-gray-700">${d.att_status.replace(/_/g,' ')}</span>
        </div>`;

        if (d.check_in || d.check_out) {
            html += `<div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-green-50 rounded-xl p-3 text-center">
                    <div class="text-xs text-gray-500">Check In</div>
                    <div class="text-base font-bold text-green-700">${d.check_in||'—'}</div>
                </div>
                <div class="bg-red-50 rounded-xl p-3 text-center">
                    <div class="text-xs text-gray-500">Check Out</div>
                    <div class="text-base font-bold text-red-700">${d.check_out||'—'}</div>
                </div>
            </div>`;
        }

        if (d.leave_name) {
            html += `<div class="mb-4 p-3 rounded-xl bg-pink-50 text-sm text-pink-700"><i class="fas fa-umbrella-beach mr-1.5"></i>${d.leave_name}${d.leave_code?' ('+d.leave_code+')':''}</div>`;
        }

        if (d.is_holiday && d.holiday_title) {
            html += `<div class="mb-4 p-3 rounded-xl bg-green-50 text-sm text-green-700"><i class="fas fa-star mr-1.5"></i>${d.holiday_title}</div>`;
        }

        // ── Existing notes list ──
        if (existingNotes.length > 0) {
            html += `<div class="mb-3">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Notes for this day</div>
                <div id="mpDayNotesList" class="flex flex-col gap-2">`;
            existingNotes.forEach(n => {
                const nTitle = (n.title||'').replace(/</g,'&lt;');
                const nBody  = (n.note_text||'').replace(/</g,'&lt;');
                const nTime  = n.created_at ? n.created_at.slice(0,16).replace('T',' ') : '';
                html += `<div class="border border-gray-200 rounded-xl p-3 bg-gray-50" data-note-id="${n.sys_id}">
                    ${nTitle ? `<div class="text-sm font-semibold text-gray-800 mb-1">${nTitle}</div>` : ''}
                    ${nBody  ? `<div class="text-sm text-gray-600 whitespace-pre-line">${nBody}</div>` : ''}
                    ${nTime  ? `<div class="text-xs text-gray-400 mt-1">${nTime}</div>` : ''}
                    <div class="flex gap-2 mt-2">
                        <button class="mpEditNoteBtn text-xs text-blue-600 hover:underline" data-id="${n.sys_id}" data-title="${(n.title||'').replace(/"/g,'&quot;')}" data-body="${(n.note_text||'').replace(/"/g,'&quot;')}"><i class="fas fa-edit mr-1"></i>Edit</button>
                        <button class="mpDelNoteBtn text-xs text-red-500 hover:underline" data-id="${n.sys_id}" data-date="${d.date}"><i class="fas fa-trash mr-1"></i>Delete</button>
                    </div>
                </div>`;
            });
            html += `</div></div>`;
        }

        // ── Add / Edit note form ──
        const isEdit   = !!(opts && opts.editSysId);
        const fTitle   = isEdit ? (opts.editTitle||'') : '';
        const fBody    = isEdit ? (opts.editBody||'')  : '';
        const fSysId   = isEdit ? opts.editSysId       : '';
        const btnLabel = isEdit ? 'Update Note' : 'Add Note';

        html += `<div class="mt-1" id="mpNoteFormWrap">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">${isEdit ? 'Edit Note' : 'New Note'}</div>
            <input id="mpDayNoteTitle" type="text" maxlength="200"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm mb-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                placeholder="Title (optional)" value="${fTitle.replace(/"/g,'&quot;')}">
            <textarea id="mpDayNoteText" rows="3"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-400"
                placeholder="Write your note…">${fBody.replace(/</g,'&lt;')}</textarea>
            <input type="hidden" id="mpDayNoteSysId" value="${fSysId}">
            <button id="mpDayNoteSave" data-date="${d.date}"
                class="mt-2 w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg px-4 py-2 transition flex items-center justify-center gap-2">
                <i class="fas fa-save"></i> ${btnLabel}
            </button>
        </div>`;

        // Apply leave only for today or future non-weekend, non-holiday days
        const todayStr = new Date().toISOString().slice(0,10);
        if (!d.is_weekend && d.date >= todayStr && !['on_leave','holiday','weekend'].includes(d.att_status)) {
            html += `<button id="mpDayApplyLeave" data-date="${d.date}"
                class="mt-3 w-full border border-blue-200 text-blue-600 hover:bg-blue-50 text-sm font-semibold rounded-lg px-4 py-2 transition flex items-center justify-center gap-2">
                <i class="fas fa-calendar-plus"></i> Apply Leave from this date
            </button>`;
        }

        bodyEl.innerHTML = html;

        // ── Save / Update ──
        document.getElementById('mpDayNoteSave').addEventListener('click', async function() {
            const date   = this.getAttribute('data-date');
            const title  = document.getElementById('mpDayNoteTitle').value.trim();
            const text   = document.getElementById('mpDayNoteText').value.trim();
            const sysId  = document.getElementById('mpDayNoteSysId').value;
            if (!title && !text) { alert('Please enter a title or note text.'); return; }
            this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            try {
                let res;
                if (sysId) {
                    res = await apiPost('/api/notes/endpoints.php', {action:'update', sys_id:sysId, title, note_text:text});
                    if (res.success) {
                        // Update cache
                        const arr = mpNotesCache[date] || [];
                        const idx = arr.findIndex(n=>n.sys_id===sysId);
                        if (idx>=0) { arr[idx].title=title; arr[idx].note_text=text; }
                    }
                } else {
                    res = await apiPost('/api/notes/endpoints.php', {action:'add', date, title, note_text:text});
                    if (res.success && res.note) {
                        if (!mpNotesCache[date]) mpNotesCache[date] = [];
                        mpNotesCache[date].push(res.note);
                    }
                }
                if (res.success) {
                    this.innerHTML = '<i class="fas fa-check"></i> Saved!';
                    renderNotesSidebar();
                    loadCalendarWithNotes();
                    setTimeout(() => { openDayModal(d); }, 600);
                } else { alert(res.message||'Failed to save.'); this.disabled=false; this.innerHTML='<i class="fas fa-save"></i> '+btnLabel; }
            } catch(_){ alert('Network error.'); this.disabled=false; this.innerHTML='<i class="fas fa-save"></i> '+btnLabel; }
        });

        // ── Per-note Edit ──
        bodyEl.querySelectorAll('.mpEditNoteBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                const sysId = this.getAttribute('data-id');
                const title = this.getAttribute('data-title');
                const body2 = this.getAttribute('data-body');
                document.getElementById('mpDayNoteTitle').value = title;
                document.getElementById('mpDayNoteText').value  = body2;
                document.getElementById('mpDayNoteSysId').value = sysId;
                document.querySelector('#mpNoteFormWrap .text-xs.font-semibold').textContent = 'Edit Note';
                document.getElementById('mpDayNoteSave').innerHTML = '<i class="fas fa-save"></i> Update Note';
                document.getElementById('mpDayNoteSave').setAttribute('data-date', d.date);
                document.getElementById('mpDayNoteTitle').focus();
            });
        });

        // ── Per-note Delete ──
        bodyEl.querySelectorAll('.mpDelNoteBtn').forEach(btn => {
            btn.addEventListener('click', async function() {
                if (!confirm('Delete this note?')) return;
                const sysId = this.getAttribute('data-id');
                const date2 = this.getAttribute('data-date');
                try {
                    const res = await apiPost('/api/notes/endpoints.php', {action:'delete', sys_id:sysId});
                    if (res.success) {
                        if (mpNotesCache[date2]) {
                            mpNotesCache[date2] = mpNotesCache[date2].filter(n=>n.sys_id!==sysId);
                            if (!mpNotesCache[date2].length) delete mpNotesCache[date2];
                        }
                        renderNotesSidebar();
                        loadCalendarWithNotes();
                        openDayModal(d);
                    } else { alert(res.message||'Failed to delete.'); }
                } catch(_){ alert('Network error.'); }
            });
        });

        const applyBtn = document.getElementById('mpDayApplyLeave');
        if (applyBtn) {
            applyBtn.addEventListener('click', function() {
                dayModal.style.display='none';
                const date = this.getAttribute('data-date');
                document.getElementById('mpLeaveFrom').value = date;
                document.getElementById('mpLeaveTo').value   = date;
                leaveModal.style.display='flex';
                document.getElementById('mpLeaveFormAlert').classList.add('hidden');
                bridgeInfo.classList.add('hidden');
                setTimeout(updateBridgePreview, 50);
            });
        }

        dayModal.style.display = 'flex';
    }

    // Credentials lazy-load (attendance handled in main nav listener above)
    document.querySelectorAll('.mp-nav-btn[data-mpsec]').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.getAttribute('data-mpsec') === 'credentials' && !mpCredLoaded) {
                mpCredLoaded = true; loadCredentials();
            }
        });
    });

    /* ══════════════════════════════════════════
       8. WIFI CHECK-IN / CHECK-OUT
    ══════════════════════════════════════════ */
    let mpTodayStatus = null;

    async function loadTodayStatus(autoCheckIn = false) {
        try {
            const data = await apiPost('/api/attendance/endpoints.php', {action:'today_status'});
            mpTodayStatus = data;
            renderCheckinWidget(data);

            // Auto check-in: only if not weekend, not holiday, not already checked in
            if (autoCheckIn && !data.is_weekend && !data.is_holiday
                && data.att_status === 'not_marked' && !data.check_in) {
                const ci = await apiPost('/api/attendance/endpoints.php', {action:'checkin'});
                if (ci.success) {
                    const data2 = await apiPost('/api/attendance/endpoints.php', {action:'today_status'});
                    mpTodayStatus = data2;
                    renderCheckinWidget(data2);
                }
            }
        } catch(_) {}
    }

    function fmtDuration(secs) {
        const h = Math.floor(secs/3600);
        const m = Math.floor((secs%3600)/60);
        return h > 0 ? `${h}h ${m}m` : `${m}m`;
    }

    function renderCheckinWidget(d) {
        const statusEl  = document.getElementById('mpCheckinStatus');
        const timeEl    = document.getElementById('mpCheckinTime');
        const signOutBtn= document.getElementById('mpSignOutBtn');
        const ciManual  = document.getElementById('mpCheckInManualBtn');

        if (!d || d.is_weekend || d.is_holiday) {
            statusEl.textContent = d?.is_holiday ? '🎉 ' + (d.holiday_title||'Holiday') : '🌴 Weekend';
            timeEl.textContent   = '';
            signOutBtn.classList.add('hidden');
            ciManual.classList.add('hidden');
            return;
        }

        if (d.check_out) {
            statusEl.textContent = '✅ Checked Out';
            timeEl.textContent   = d.check_in + ' → ' + d.check_out;
            signOutBtn.classList.add('hidden');
            ciManual.classList.add('hidden');
        } else if (d.check_in) {
            statusEl.textContent = '🟢 Checked In';
            timeEl.textContent   = 'Since ' + d.check_in;
            signOutBtn.classList.remove('hidden');
            ciManual.classList.add('hidden');
        } else if (d.att_status === 'on_leave') {
            statusEl.textContent = '🏖️ On Leave';
            timeEl.textContent   = d.leave_name || '';
            signOutBtn.classList.add('hidden');
            ciManual.classList.add('hidden');
        } else {
            statusEl.textContent = '⚪ Not Checked In';
            timeEl.textContent   = '';
            signOutBtn.classList.add('hidden');
            ciManual.classList.remove('hidden');
        }
    }

    // Manual check-in button
    document.getElementById('mpCheckInManualBtn').addEventListener('click', async function() {
        this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        try {
            const ci = await apiPost('/api/attendance/endpoints.php', {action:'checkin'});
            if (ci.success) {
                await loadTodayStatus(false);
            } else { alert(ci.message||'Check-in failed.'); }
        } catch(_){ alert('Network error.'); }
        this.disabled = false; this.innerHTML = '<i class="fas fa-fingerprint mr-1"></i> Check In';
    });

    // Sign out button → show confirm modal
    document.getElementById('mpSignOutBtn').addEventListener('click', function() {
        const dur = document.getElementById('mpSignOutDuration');
        if (mpTodayStatus?.check_in) {
            const now  = new Date();
            const ci   = mpTodayStatus.check_in.split(':');
            const then = new Date();
            then.setHours(parseInt(ci[0]), parseInt(ci[1]), parseInt(ci[2]||0));
            const secs = Math.max(0, Math.floor((now - then) / 1000));
            dur.textContent = `আজকে ${fmtDuration(secs)} কাজ করেছ — বের হচ্ছ?`;
        } else { dur.textContent = 'Confirm sign out for today?'; }
        document.getElementById('mpSignOutModal').style.display = 'flex';
    });

    document.getElementById('mpSignOutCancel').addEventListener('click', () => {
        document.getElementById('mpSignOutModal').style.display = 'none';
    });

    document.getElementById('mpSignOutConfirm').addEventListener('click', async function() {
        this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        try {
            const co = await apiPost('/api/attendance/endpoints.php', {action:'checkout'});
            document.getElementById('mpSignOutModal').style.display = 'none';
            if (co.success) {
                await loadTodayStatus(false);
                const worked = co.worked_seconds ? fmtDuration(co.worked_seconds) : '';
                if (worked) alert('✅ Signed out! আজকে ' + worked + ' কাজ করেছ।');
            } else { alert(co.message||'Check-out failed.'); }
        } catch(_){ alert('Network error.'); }
        this.disabled = false; this.innerHTML = '<i class="fas fa-check mr-1.5"></i> Confirm';
    });

    // Load today status + auto check-in on page load
    loadTodayStatus(true);

    /* ══════════════════════════════════════════
       9. CREDENTIALS MODULE
    ══════════════════════════════════════════ */
    let mpCredLoaded = false;

    async function loadCredentials() {
        const el = document.getElementById('mpCredList');
        try {
            const data = await apiPost('/api/credentials/endpoints.php', {action:'list'});
            if (!data.success) { el.innerHTML='<p class="text-sm text-red-500">'+data.message+'</p>'; return; }
            if (!data.data.length) {
                el.innerHTML=`<div class="text-center py-10 text-gray-400 text-sm">
                    <i class="fas fa-key text-4xl mb-3 block text-gray-200"></i>
                    No credentials saved yet. Click <strong>Add</strong> to save your first one.
                </div>`;
                return;
            }
            el.innerHTML = data.data.map(c => `
                <div class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:bg-gray-50 transition">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-globe text-indigo-400 text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm text-gray-800 truncate">${escHtml(c.title)}</div>
                        <div class="text-xs text-gray-400 truncate">${c.username ? escHtml(c.username) : (c.url ? escHtml(c.url) : 'No username')}</div>
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <button class="w-8 h-8 rounded-lg hover:bg-indigo-50 text-gray-400 hover:text-indigo-600 transition flex items-center justify-center text-sm"
                                onclick="mpEditCred('${c.sys_id}')" title="View / Edit">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="w-8 h-8 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500 transition flex items-center justify-center text-sm"
                                onclick="mpDeleteCred('${c.sys_id}','${escHtml(c.title)}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        } catch(_) { el.innerHTML='<p class="text-sm text-red-500">Failed to load.</p>'; }
    }

    function escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    const credModal      = document.getElementById('mpCredModal');
    const credModalClose = document.getElementById('mpCredModalClose');
    credModalClose.addEventListener('click', () => { credModal.style.display='none'; });
    credModal.addEventListener('click', e => { if(e.target===credModal) credModal.style.display='none'; });

    document.getElementById('mpCredAddBtn').addEventListener('click', () => {
        document.getElementById('mpCredModalTitle').innerHTML = '<i class="fas fa-key text-indigo-500"></i> Add Credential';
        document.getElementById('mpCredSysId').value = '';
        ['mpCredTitle','mpCredUrl','mpCredUsername','mpCredPassword','mpCredNotes'].forEach(id => document.getElementById(id).value='');
        document.getElementById('mpCredFormAlert').classList.add('hidden');
        credModal.style.display = 'flex';
    });

    document.getElementById('mpCredPwEye').addEventListener('click', function() {
        const inp = document.getElementById('mpCredPassword');
        const toText = inp.type === 'password';
        inp.type = toText ? 'text' : 'password';
        this.querySelector('i').className = toText ? 'fas fa-eye-slash text-sm' : 'fas fa-eye text-sm';
    });

    window.mpEditCred = async function(sysId) {
        try {
            const data = await apiPost('/api/credentials/endpoints.php', {action:'get', sys_id:sysId});
            if (!data.success) { alert(data.message||'Failed.'); return; }
            const c = data.data;
            document.getElementById('mpCredModalTitle').innerHTML = '<i class="fas fa-pen text-indigo-500"></i> Edit Credential';
            document.getElementById('mpCredSysId').value    = c.sys_id;
            document.getElementById('mpCredTitle').value    = c.title    || '';
            document.getElementById('mpCredUrl').value      = c.url      || '';
            document.getElementById('mpCredUsername').value = c.username || '';
            document.getElementById('mpCredPassword').value = c.password || '';
            document.getElementById('mpCredNotes').value    = c.notes    || '';
            document.getElementById('mpCredFormAlert').classList.add('hidden');
            credModal.style.display = 'flex';
        } catch(_){ alert('Network error.'); }
    };

    window.mpDeleteCred = async function(sysId, title) {
        if (!confirm('Delete credential "' + title + '"?')) return;
        try {
            const data = await apiPost('/api/credentials/endpoints.php', {action:'delete', sys_id:sysId});
            if (data.success) { await loadCredentials(); }
            else { alert(data.message||'Failed.'); }
        } catch(_){ alert('Network error.'); }
    };

    document.getElementById('mpCredSaveBtn').addEventListener('click', async function() {
        const alertEl = document.getElementById('mpCredFormAlert');
        const showA   = (msg, ok) => {
            alertEl.textContent = msg;
            alertEl.className = 'mb-4 p-3 rounded-lg text-sm font-medium '+(ok?'bg-green-50 text-green-700 border border-green-200':'bg-red-50 text-red-700 border border-red-200');
            alertEl.classList.remove('hidden');
        };

        const sysId    = document.getElementById('mpCredSysId').value.trim();
        const title    = document.getElementById('mpCredTitle').value.trim();
        const url      = document.getElementById('mpCredUrl').value.trim();
        const username = document.getElementById('mpCredUsername').value.trim();
        const password = document.getElementById('mpCredPassword').value;
        const notes    = document.getElementById('mpCredNotes').value.trim();

        if (!title) { showA('Title is required.', false); return; }

        this.disabled = true; this.innerHTML='<i class="fas fa-spinner fa-spin"></i> Saving…';
        try {
            const payload = sysId
                ? {action:'update', sys_id:sysId, title, url, username, password, notes}
                : {action:'add', title, url, username, password, notes};
            const data = await apiPost('/api/credentials/endpoints.php', payload);
            if (data.success) {
                showA('Saved!', true);
                await loadCredentials();
                setTimeout(() => { credModal.style.display='none'; }, 1200);
            } else { showA(data.message||'Failed.', false); }
        } catch(_){ showA('Network error.', false); }
        this.disabled = false; this.innerHTML='<i class="fas fa-save"></i> Save';
    });

    /* ══════════════════════════════════════════
       10. DOCUMENTS MODULE
    ══════════════════════════════════════════ */
    let mpDocsLoaded = false;

    async function loadDocs() {
        const el = document.getElementById('mpDocsList');
        try {
            const data = await apiPost('/api/employee-documents/endpoints.php', {action:'list'});
            if (!data.success) { el.innerHTML = `<p class="text-sm text-red-500">${escHtml(data.message)}</p>`; return; }
            if (!data.data.length) {
                el.innerHTML = `<div class="text-center py-10 text-gray-400 text-sm">
                    <i class="fas fa-folder-open text-4xl mb-3 block text-gray-200"></i>
                    No documents yet. Upload your first document above.
                </div>`;
                return;
            }
            const iconMap = {
                'application/pdf': {icon:'fa-file-pdf', cls:'text-red-400 bg-red-50'},
                'application/msword': {icon:'fa-file-word', cls:'text-blue-400 bg-blue-50'},
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document': {icon:'fa-file-word', cls:'text-blue-400 bg-blue-50'},
                'image/jpeg': {icon:'fa-file-image', cls:'text-purple-400 bg-purple-50'},
                'image/png':  {icon:'fa-file-image', cls:'text-purple-400 bg-purple-50'},
                'application/vnd.ms-excel': {icon:'fa-file-excel', cls:'text-green-500 bg-green-50'},
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': {icon:'fa-file-excel', cls:'text-green-500 bg-green-50'},
            };
            el.innerHTML = data.data.map(doc => {
                const ic = iconMap[doc.file_type] || {icon:'fa-file', cls:'text-gray-400 bg-gray-100'};
                const hrBadge = doc.is_hr_issued == '1'
                    ? `<span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-indigo-50 text-indigo-600 font-semibold">HR Issued</span>` : '';
                const size = doc.file_size > 0 ? (doc.file_size > 1048576
                    ? (doc.file_size/1048576).toFixed(1)+' MB'
                    : (doc.file_size/1024).toFixed(0)+' KB') : '';
                const date = doc.created_at ? doc.created_at.slice(0,10) : '';
                return `<div class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:bg-gray-50 transition">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${ic.cls}">
                        <i class="fas ${ic.icon} text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm text-gray-800 truncate">${escHtml(doc.title)}${hrBadge}</div>
                        <div class="text-xs text-gray-400 truncate">${escHtml(doc.file_name)} ${size ? '· '+size : ''} ${date ? '· '+date : ''}</div>
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <button class="w-8 h-8 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500 transition flex items-center justify-center text-sm"
                                onclick="mpDeleteDoc('${escHtml(doc.sys_id)}','${escHtml(doc.title)}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>`;
            }).join('');
        } catch(_) { el.innerHTML='<p class="text-sm text-red-500">Failed to load documents.</p>'; }
    }

    window.mpDeleteDoc = async function(sysId, title) {
        if (!confirm('Delete "' + title + '"? This cannot be undone.')) return;
        try {
            const data = await apiPost('/api/employee-documents/endpoints.php', {action:'delete', sys_id:sysId});
            if (data.success) { await loadDocs(); }
            else { alert(data.message || 'Delete failed.'); }
        } catch(_){ alert('Network error.'); }
    };

    document.getElementById('mpDocUploadInput').addEventListener('change', async function() {
        const files = Array.from(this.files);
        if (!files.length) return;
        const progress = document.getElementById('mpDocUploadProgress');
        progress.classList.remove('hidden');
        let done = 0, failed = 0;
        for (const file of files) {
            const fd = new FormData();
            fd.append('action', 'upload');
            fd.append('file', file);
            fd.append('title', file.name.replace(/\.[^.]+$/, ''));
            progress.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>Uploading ${escHtml(file.name)}… (${done+1}/${files.length})`;
            try {
                const resp = await fetch('/api/employee-documents/endpoints.php', {method:'POST', body:fd});
                const json = await resp.json();
                if (json.success) done++; else { failed++; alert('Upload failed: ' + (json.message||'Unknown error')); }
            } catch(_){ failed++; alert('Network error uploading ' + file.name); }
        }
        progress.classList.add('hidden');
        this.value = '';
        await loadDocs();
        if (done > 0 && failed === 0) {
            const alert2 = document.createElement('div');
            alert2.className = 'mb-3 p-3 rounded-lg bg-green-50 text-green-700 text-sm font-medium';
            alert2.textContent = `✅ ${done} document${done>1?'s':''} uploaded successfully.`;
            document.getElementById('mpDocsList').before(alert2);
            setTimeout(() => alert2.remove(), 3000);
        }
    });

    /* ══════════════════════════════════════════
       11. NOTIFICATIONS MODULE
    ══════════════════════════════════════════ */
    let mpNotifLoaded = false;

    async function loadNotifications() {
        const el = document.getElementById('mpNotifList');
        try {
            const data = await apiPost('/api/notifications/endpoints.php', {action:'list'});
            if (data.status !== 'success') { el.innerHTML = `<p class="text-sm text-red-500">${escHtml(data.message||'Failed to load notifications.')}</p>`; return; }
            const items = data.data || [];
            updateNotifBadge(items.filter(n => !n.is_read).length);
            if (!items.length) {
                el.innerHTML = `<div class="text-center py-10 text-gray-400 text-sm">
                    <i class="fas fa-bell text-4xl mb-3 block text-gray-200"></i>No notifications yet.</div>`;
                return;
            }
            const typeIcon = {leave:'fa-calendar-check text-blue-500', attendance:'fa-clock text-orange-500', hr:'fa-building text-indigo-500', info:'fa-info-circle text-gray-400'};
            el.innerHTML = `<div class="flex justify-end mb-2">
                <button onclick="mpMarkAllRead()" class="text-xs text-blue-600 hover:underline">Mark all as read</button>
            </div>` + items.map(n => {
                const ic = typeIcon[n.type] || typeIcon.info;
                const unread = !n.is_read ? 'bg-blue-50 border-blue-100' : 'bg-white border-gray-100';
                const dot = !n.is_read ? '<span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0 mt-1.5"></span>' : '<span class="w-2 h-2 flex-shrink-0"></span>';
                const dt = n.created_at ? n.created_at.slice(0,16).replace('T',' ') : '';
                return `<div class="flex items-start gap-3 p-3 rounded-xl border ${unread} transition" id="mpn-${escHtml(n.sys_id)}">
                    ${dot}
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 bg-gray-100">
                        <i class="fas ${ic} text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-gray-800">${escHtml(n.title)}</div>
                        ${n.body ? `<div class="text-xs text-gray-500 mt-0.5">${escHtml(n.body)}</div>` : ''}
                        <div class="text-xs text-gray-400 mt-1">${dt}</div>
                    </div>
                    ${!n.is_read ? `<button onclick="mpMarkRead('${escHtml(n.sys_id)}')" class="text-xs text-blue-500 hover:underline flex-shrink-0">Read</button>` : ''}
                </div>`;
            }).join('');
        } catch(_) { el.innerHTML='<p class="text-sm text-red-500">Failed to load notifications.</p>'; }
    }

    function updateNotifBadge(count) {
        const badge = document.getElementById('mpNotifBadge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    window.mpMarkRead = async function(sysId) {
        try {
            await apiPost('/api/notifications/endpoints.php', {action:'mark_read', sys_id:sysId});
            await loadNotifications();
        } catch(_){}
    };

    window.mpMarkAllRead = async function() {
        try {
            await apiPost('/api/notifications/endpoints.php', {action:'mark_all'});
            await loadNotifications();
        } catch(_){}
    };

    // Load notification badge count on page load (without opening the panel)
    (async function() {
        try {
            const data = await apiPost('/api/notifications/endpoints.php', {action:'unread_count'});
            if (data.status === 'success') updateNotifBadge(data.count || 0);
        } catch(_){}
    })();

    /* ══════════════════════════════════════════
       12. PAYROLL MODULE
    ══════════════════════════════════════════ */
    let mpPayrollLoaded = false;
    let mpPayrollYears  = [];

    async function loadPayroll(year) {
        const el = document.getElementById('mpPayrollList');
        el.innerHTML = '<div class="text-center py-10 text-gray-300 text-sm"><i class="fas fa-spinner fa-spin text-2xl block mb-2"></i>Loading…</div>';
        try {
            const data = await apiPost('/api/payroll/endpoints.php', {action:'list', year: year || new Date().getFullYear()});
            if (!data.success) { el.innerHTML = `<p class="text-sm text-red-500 text-center py-6">${escHtml(data.message||'Failed to load payroll.')}</p>`; return; }

            // Populate year selector
            if (data.years && data.years.length) {
                mpPayrollYears = data.years;
                const sel = document.getElementById('mpPayrollYear');
                sel.innerHTML = mpPayrollYears.map(y => `<option value="${y}"${y==data.year?' selected':''}>${y}</option>`).join('');
            }

            const rows = data.data || [];
            if (!rows.length) {
                el.innerHTML = '<div class="text-center py-12 text-gray-400 text-sm"><i class="fas fa-file-invoice-dollar text-4xl block mb-3 text-gray-200"></i>No payroll records found for this year.</div>';
                return;
            }

            const STATUS_COLOR = {
                prepared:   'bg-yellow-100 text-yellow-700',
                collected:  'bg-blue-100 text-blue-700',
                authorized: 'bg-green-100 text-green-700',
                cancelled:  'bg-red-100 text-red-700',
            };

            el.innerHTML = rows.map(r => {
                const cls   = STATUS_COLOR[r.status] || 'bg-gray-100 text-gray-600';
                const month = r.month ? new Date(r.month+'-01').toLocaleDateString('en-BD',{month:'long',year:'numeric'}) : r.month;
                const net   = Number(r.net_payable_salary||0).toLocaleString('en-BD');
                return `<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center gap-4 cursor-pointer hover:shadow-md transition" onclick="mpViewSlip('${escHtml(r.sys_id)}')">
                    <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-file-invoice-dollar text-green-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm text-gray-800">${month}</div>
                        <div class="text-xs text-gray-400 mt-0.5">${r.payment_type||'Salary'} · ${r.payment_date||'—'}</div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="font-bold text-green-700 text-sm">৳ ${net}</div>
                        <span class="mt-1 inline-block text-xs font-semibold px-2 py-0.5 rounded-full ${cls}">${r.status||'—'}</span>
                    </div>
                </div>`;
            }).join('');
        } catch(e) {
            el.innerHTML = `<p class="text-sm text-red-500 text-center py-6">Network error: ${escHtml(e.message)}</p>`;
        }
    }

    window.mpViewSlip = async function(sysId) {
        const modal  = document.getElementById('mpPayrollModal');
        const mBody  = document.getElementById('mpPayrollModalBody');
        const mMonth = document.getElementById('mpPayrollModalMonth');
        const mStat  = document.getElementById('mpPayrollModalStatus');
        modal.style.display = 'flex';
        mBody.innerHTML = '<div class="text-center py-8 text-gray-300"><i class="fas fa-spinner fa-spin text-2xl"></i></div>';
        try {
            const data = await apiPost('/api/payroll/endpoints.php', {action:'get', sys_id:sysId});
            if (!data.success) { mBody.innerHTML = `<p class="text-red-500 text-sm text-center py-6">${escHtml(data.message)}</p>`; return; }
            const r = data.data;
            const month = r.month ? new Date(r.month+'-01').toLocaleDateString('en-BD',{month:'long',year:'numeric'}) : r.month;
            mMonth.textContent = month;
            mStat.textContent  = (r.status||'').toUpperCase() + (r.payment_date ? ' · ' + r.payment_date : '');

            const fmt = v => Number(v||0).toLocaleString('en-BD');
            const row = (label, val, cls='') => val
                ? `<div class="flex justify-between items-center py-1.5 border-b border-gray-50 last:border-0">
                       <span class="text-sm text-gray-500">${label}</span>
                       <span class="text-sm font-semibold ${cls}">৳ ${fmt(val)}</span>
                   </div>` : '';

            // Build allowances/deductions display
            let allowHtml = '', dedHtml = '';
            if (r.allowances && typeof r.allowances === 'object') {
                Object.entries(r.allowances).forEach(([k,v]) => { if(v) allowHtml += row(k, v, 'text-green-700'); });
            }
            if (r.deduction && typeof r.deduction === 'object') {
                Object.entries(r.deduction).forEach(([k,v]) => { if(v) dedHtml += row(k, v, 'text-red-600'); });
            }

            mBody.innerHTML = `
                <div class="space-y-1 mb-4">
                    ${row('Basic Salary', r.eps_salary?.basic_salary || r.net_payable_salary)}
                    ${row('Bonus',     r.bonus)}
                    ${row('Overtime',  r.overtime)}
                    ${allowHtml}
                </div>
                ${dedHtml ? `<div class="bg-red-50 rounded-xl p-3 mb-4 space-y-1">
                    <div class="text-xs font-bold text-red-500 mb-1 uppercase tracking-wide">Deductions</div>
                    ${dedHtml}
                </div>` : ''}
                <div class="bg-green-50 rounded-xl p-4 flex items-center justify-between">
                    <span class="font-bold text-gray-700">Net Payable</span>
                    <span class="text-xl font-extrabold text-green-700">৳ ${fmt(r.net_payable_salary)}</span>
                </div>
                ${r.note ? `<p class="mt-3 text-xs text-gray-400 text-center">${escHtml(r.note)}</p>` : ''}
            `;
        } catch(e) {
            mBody.innerHTML = `<p class="text-red-500 text-sm text-center py-6">Network error.</p>`;
        }
    };

    document.getElementById('mpPayrollModalClose')?.addEventListener('click', () => {
        document.getElementById('mpPayrollModal').style.display = 'none';
    });
    document.getElementById('mpPayrollModal')?.addEventListener('click', e => {
        if (e.target === document.getElementById('mpPayrollModal'))
            document.getElementById('mpPayrollModal').style.display = 'none';
    });
    document.getElementById('mpPayrollYear')?.addEventListener('change', function() {
        loadPayroll(this.value);
    });

    /* ══════════════════════════════════════════
       13. QUICK ACTIONS
    ══════════════════════════════════════════ */
    document.getElementById('qaAppoint')?.addEventListener('click', () => {
        alert('Appointment Letter — HR এর সাথে যোগাযোগ করুন। (Document generation coming soon)');
    });
    document.getElementById('qaNoc')?.addEventListener('click', () => {
        alert('NOC Letter — HR এর সাথে যোগাযোগ করুন।');
    });
    document.getElementById('qaSalary')?.addEventListener('click', () => {
        document.querySelector('[data-mpsec="payroll"]')?.click();
    });
    document.getElementById('qaIdCard')?.addEventListener('click', () => {
        window.open('my-id-card.php', '_blank');
    });
    /* ══════════════════════════════════════════
       ANNUAL CALENDAR (Office Journal)
    ══════════════════════════════════════════ */
    let annualYear     = new Date().getFullYear();
    let annualHolidays = []; // { holiday_date, title, type }
    let annualLeaves   = []; // approved: { date_from, date_to, leave_name, emp_name? }
    let annualAtt      = {}; // { "YYYY-MM-DD": att_status }
    let annualNoteCache= {}; // { "YYYY-MM-DD": [notes...] }
    let annualSelectedDate = null;
    // leave range → date set with metadata
    let annualLeaveMap = {}; // { "YYYY-MM-DD": { leave_name, ... } }

    const ANNUAL_MONTH_NAMES = ['January','February','March','April','May','June',
                                'July','August','September','October','November','December'];
    const ANNUAL_DAY_NAMES   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const ANNUAL_DAY_SHORT   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const ANNUAL_DAY_LETTERS = ['S','M','T','W','T','F','S'];

    function annualDaysInMonth(y, m) { return new Date(y, m+1, 0).getDate(); }
    function annualDow(y, m, d)      { return new Date(y, m, d).getDay(); }

    async function loadAnnualData() {
        const grid = document.getElementById('annualGrid');
        const stats = document.getElementById('annualStats');
        grid.innerHTML = '<div class="col-span-full text-center py-12 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl mb-2 block"></i>Loading…</div>';
        stats.innerHTML = '';
        annualNoteCache = {};
        annualLeaveMap = {};

        try {
            // Holidays
            const hRes = await apiPost('/api/holidays/endpoints.php', {action:'list', year: annualYear});
            annualHolidays = hRes.data || [];

            // Own approved leaves
            try {
                const lRes = await apiPost('/api/leaves/endpoints.php', {action:'list', year: annualYear});
                annualLeaves = (lRes.data || []).filter(l => l.status === 'approved');
                // Build per-day leave map with leave type name
                annualLeaves.forEach(l => {
                    const from = new Date((l.date_from||'') + 'T00:00:00');
                    const to   = new Date((l.date_to||'')   + 'T00:00:00');
                    for (let d = new Date(from); d <= to; d.setDate(d.getDate()+1)) {
                        const ds = d.toISOString().slice(0,10);
                        annualLeaveMap[ds] = l.leave_name || 'Leave';
                    }
                });
            } catch(e) { annualLeaves = []; }

            // Attendance (all 12 months in parallel)
            annualAtt = {};
            await Promise.all(
                Array.from({length:12},(_,i)=>i+1).map(m =>
                    apiPost('/api/attendance/endpoints.php', {action:'my_month', year:annualYear, month:m})
                        .then(r => { (r.days||[]).forEach(d => { if(d.date) annualAtt[d.date] = d.att_status; }); })
                        .catch(()=>{})
                )
            );

            // Notes for whole year (month action for each month)
            await Promise.all(
                Array.from({length:12},(_,i)=>i+1).map(m =>
                    apiPost('/api/notes/endpoints.php', {action:'month', year:annualYear, month:m})
                        .then(r => {
                            (r.notes||r.data||[]).forEach(n => {
                                const nd = n.note_date || n.date || '';
                                if (!nd) return;
                                if (!annualNoteCache[nd]) annualNoteCache[nd] = [];
                                annualNoteCache[nd].push(n);
                            });
                        })
                        .catch(()=>{})
                )
            );

        } catch(e) {
            grid.innerHTML = '<div class="col-span-full text-center py-8 text-red-400">Failed to load calendar data.</div>';
            return;
        }

        renderAnnualCalendar();
    }

    function annualDayCellClass(ds) {
        const today  = new Date().toISOString().slice(0,10);
        const isPast = ds < today;
        const isFri  = new Date(ds+'T00:00:00').getDay() === 5;
        const hol    = annualHolidays.find(h => h.holiday_date === ds);
        const hasNote= !!(annualNoteCache[ds] && annualNoteCache[ds].length);
        const att    = annualAtt[ds] || null;

        if (ds === annualSelectedDate) return 'bg-blue-500 text-white border-blue-400 font-bold ring-2 ring-blue-300 z-10 scale-110';
        if (ds === today && ds !== annualSelectedDate) return 'bg-blue-100 text-blue-700 border-blue-300 font-bold ring-1 ring-blue-300';
        if (hol) {
            if (hol.type === 'public_holiday') return 'bg-red-100 text-red-700 border-red-200 font-semibold';
            if (hol.type === 'office_closed')  return 'bg-orange-100 text-orange-700 border-orange-200 font-semibold';
            return 'bg-yellow-50 text-yellow-700 border-yellow-200';
        }
        if (isFri)  return 'bg-slate-100 text-slate-500 border-slate-200';
        if (annualLeaveMap[ds]) return 'bg-pink-100 text-pink-700 border-pink-200';
        if (att === 'present')  return 'bg-green-100 text-green-700 border-green-200';
        if (att === 'absent' && isPast) return 'bg-rose-50 text-rose-500 border-rose-200';
        if (hasNote) return 'bg-violet-50 text-violet-700 border-violet-200';
        return 'bg-white text-gray-700 border-gray-100 hover:bg-gray-50';
    }

    function renderAnnualCalendar() {
        document.getElementById('annualYearLabel').textContent = annualYear;

        // Stats
        const leaveCount   = Object.keys(annualLeaveMap).length;
        const presentCount = Object.values(annualAtt).filter(v=>v==='present').length;
        const absentCount  = Object.values(annualAtt).filter(v=>v==='absent').length;
        const noteCount    = Object.keys(annualNoteCache).length;
        document.getElementById('annualStats').innerHTML = [
            {icon:'fas fa-umbrella-beach',  color:'text-red-500 bg-red-50',    label:'Holidays',     val: annualHolidays.length},
            {icon:'fas fa-plane-departure', color:'text-pink-500 bg-pink-50',  label:'Leave Days',   val: leaveCount},
            {icon:'fas fa-circle-check',    color:'text-green-500 bg-green-50',label:'Present Days', val: presentCount},
            {icon:'fas fa-note-sticky',     color:'text-violet-500 bg-violet-50',label:'Note Days',  val: noteCount},
        ].map(s=>`
            <div class="flex items-center gap-3 rounded-xl border border-gray-100 p-3 cursor-default">
                <div class="w-9 h-9 rounded-xl ${s.color} flex items-center justify-center text-sm flex-shrink-0">
                    <i class="${s.icon}"></i>
                </div>
                <div>
                    <div class="text-xl font-bold text-gray-800 leading-tight">${s.val}</div>
                    <div class="text-xs text-gray-400">${s.label}</div>
                </div>
            </div>
        `).join('');

        // Render 12 months
        const grid = document.getElementById('annualGrid');
        let html = '';
        for (let m = 0; m < 12; m++) {
            const days  = annualDaysInMonth(annualYear, m);
            const first = annualDow(annualYear, m, 1);
            html += `<div class="border border-gray-100 rounded-xl overflow-hidden shadow-sm">
                <div class="bg-gradient-to-r from-slate-700 to-slate-600 text-white text-xs font-bold px-3 py-2 flex justify-between items-center">
                    <span>${ANNUAL_MONTH_NAMES[m]}</span>
                    <span class="opacity-60 font-normal text-[10px]">${annualYear}</span>
                </div>
                <div class="p-1.5">
                    <div class="grid grid-cols-7 mb-0.5">
                        ${ANNUAL_DAY_LETTERS.map((l,i)=>`<div class="text-center text-[9px] font-semibold ${i===5?'text-slate-400':'text-gray-400'} py-0.5">${l}</div>`).join('')}
                    </div>
                    <div class="grid grid-cols-7 gap-px">`;

            for (let e = 0; e < first; e++) html += `<div></div>`;

            for (let day = 1; day <= days; day++) {
                const mm = String(m+1).padStart(2,'0');
                const dd = String(day).padStart(2,'0');
                const ds = `${annualYear}-${mm}-${dd}`;
                const cls = annualDayCellClass(ds);
                const hasNote = !!(annualNoteCache[ds] && annualNoteCache[ds].length);
                html += `<div class="aspect-square rounded text-[10px] flex items-center justify-center border ${cls} cursor-pointer transition-transform relative select-none"
                    data-anndate="${ds}">${day}${hasNote?`<span class="absolute top-0 right-0 w-1 h-1 rounded-full bg-violet-500"></span>`:''}</div>`;
            }
            html += `</div></div></div>`;
        }
        grid.innerHTML = html;

        // Click handlers on day cells
        grid.querySelectorAll('[data-anndate]').forEach(el => {
            el.addEventListener('click', () => annualSelectDay(el.getAttribute('data-anndate')));
        });

        // Holiday list
        if (annualHolidays.length) {
            const listEl  = document.getElementById('annualHolidayList');
            const itemsEl = document.getElementById('annualHolidayItems');
            listEl.classList.remove('hidden');
            const typeLabel = {public_holiday:'Public Holiday', office_closed:'Office Closed', optional:'Optional'};
            const typeCls   = {public_holiday:'bg-red-100 text-red-700', office_closed:'bg-orange-100 text-orange-700', optional:'bg-yellow-100 text-yellow-700'};
            itemsEl.innerHTML = annualHolidays.map(h => {
                const dt      = new Date(h.holiday_date + 'T00:00:00');
                const dayName = ANNUAL_DAY_SHORT[dt.getDay()];
                const display = dt.toLocaleDateString('en-BD',{day:'numeric',month:'long',year:'numeric'});
                return `<div class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50 transition cursor-pointer"
                            onclick="annualSelectDay('${h.holiday_date}')">
                    <div>
                        <div class="font-medium text-gray-800 text-sm">${h.title}</div>
                        <div class="text-xs text-gray-400 mt-0.5">${dayName}, ${display}</div>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full ${typeCls[h.type]||'bg-gray-100 text-gray-600'}">${typeLabel[h.type]||h.type}</span>
                </div>`;
            }).join('');
        }
    }

    // ── Day Planner Panel ─────────────────────────────────────
    window.annualSelectDay = function(dateStr) {
        annualSelectedDate = dateStr;
        // Re-apply cell classes to update selected highlight
        document.querySelectorAll('#annualGrid [data-anndate]').forEach(el => {
            const ds = el.getAttribute('data-anndate');
            el.className = `aspect-square rounded text-[10px] flex items-center justify-center border ${annualDayCellClass(ds)} cursor-pointer transition-transform relative select-none`;
        });

        const panel = document.getElementById('annualDayPanel');
        panel.classList.remove('hidden');

        const dt      = new Date(dateStr + 'T00:00:00');
        const dayName = ANNUAL_DAY_NAMES[dt.getDay()];
        const display = dt.toLocaleDateString('en-BD',{day:'numeric',month:'long',year:'numeric'});
        document.getElementById('annualDayPanelDate').textContent = dayName;
        document.getElementById('annualDayPanelDate').setAttribute('data-date', dateStr);

        // Sub-label: holiday name or leave type or just date
        const hol = annualHolidays.find(h => h.holiday_date === dateStr);
        const leaveName = annualLeaveMap[dateStr] || null;
        document.getElementById('annualDayPanelSub').textContent = display;

        // Status chip
        const chipEl = document.getElementById('annualDayStatusChip');
        let chip = '';
        if (hol) {
            const typeLabel = {public_holiday:'🏖️ Public Holiday', office_closed:'🚪 Office Closed', optional:'🌟 Optional Holiday'};
            chip = `<span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700">${typeLabel[hol.type]||hol.type}: ${hol.title}</span>`;
        } else if (leaveName) {
            chip = `<span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-pink-100 text-pink-700">✈️ On Leave — ${leaveName}</span>`;
        } else if (dt.getDay() === 5) {
            chip = `<span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">📅 Weekend (Friday)</span>`;
        } else {
            const att = annualAtt[dateStr];
            if (att === 'present')      chip = `<span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">✅ Present</span>`;
            else if (att === 'absent')  chip = `<span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">❌ Absent</span>`;
            else                        chip = `<span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">— No record</span>`;
        }
        chipEl.innerHTML = chip;

        // Who's away — fetch daily_all for that date (HR or just self)
        const awayBlock = document.getElementById('annualDayAwayBlock');
        const awayList  = document.getElementById('annualDayAwayList');
        awayBlock.classList.add('hidden');
        apiPost('/api/attendance/endpoints.php', {action:'daily_all', date: dateStr})
            .then(r => {
                const rows = (r.data || r.records || []);
                const away = rows.filter(row => row.att_status === 'on_leave' || row.att_status === 'absent');
                if (!away.length) return;
                awayBlock.classList.remove('hidden');
                awayList.innerHTML = away.map(row => {
                    const icon = row.att_status === 'on_leave' ? '✈️' : '❌';
                    const label = row.att_status === 'on_leave'
                        ? (row.leave_name || 'On Leave')
                        : 'Absent';
                    return `<div class="flex items-center justify-between py-0.5">
                        <span class="font-medium truncate max-w-[140px]">${icon} ${row.emp_name||row.employee_name||'Employee'}</span>
                        <span class="text-gray-400 text-[10px]">${label}</span>
                    </div>`;
                }).join('');
            })
            .catch(()=>{});

        // Notes
        document.getElementById('annualNoteDate').value  = dateStr;
        document.getElementById('annualNoteSysId').value = '';
        document.getElementById('annualNoteTitle').value = '';
        document.getElementById('annualNoteBody').value  = '';
        document.getElementById('annualNoteCancel').classList.add('hidden');
        annualRenderNotesList(dateStr);
    };

    function annualRenderNotesList(dateStr) {
        const listEl = document.getElementById('annualNotesList');
        const notes  = annualNoteCache[dateStr] || [];
        if (!notes.length) {
            listEl.innerHTML = '<p class="text-xs text-gray-400 text-center py-3">No planning notes for this day.</p>';
            return;
        }
        const sorted = [...notes].sort((a,b) => (b.created_at||'') > (a.created_at||'') ? 1 : -1);
        listEl.innerHTML = sorted.map(n => {
            const isRecurring = n.is_recurring || n.repeat_yearly == 1;
            const titleEsc    = (n.title||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'&quot;');
            const bodyEsc     = (n.note_text||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'&quot;');
            const noteDateEsc = (n.note_date||dateStr);
            return `
            <div class="border ${isRecurring?'border-blue-200 bg-blue-50':'border-gray-100 bg-gray-50'} rounded-xl p-2.5 text-xs">
                <div class="flex items-start justify-between gap-1 mb-0.5">
                    <div class="font-semibold text-gray-700 break-words flex-1">${n.title||'<span class="text-gray-400 font-normal italic">No title</span>'}</div>
                    ${isRecurring ? `<span class="flex-shrink-0 text-[9px] font-semibold px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-600 flex items-center gap-1 ml-1">
                        <i class="fas fa-rotate"></i>Yearly
                    </span>` : ''}
                </div>
                <div class="text-gray-600 whitespace-pre-wrap break-words">${n.note_text||''}</div>
                <div class="flex items-center justify-between mt-1.5 pt-1.5 border-t ${isRecurring?'border-blue-200':'border-gray-200'}">
                    <span class="text-gray-400 text-[10px]">${(n.created_at||'').slice(0,16)}${isRecurring&&n.note_date!==dateStr?' <span class="text-blue-400">(from '+n.note_date+')</span>':''}</span>
                    <div class="flex gap-2">
                        ${!isRecurring||n.note_date===dateStr ? `
                        <button onclick="annualEditNote('${n.sys_id}','${noteDateEsc}','${titleEsc}','${bodyEsc}',${n.repeat_yearly||0});return false;"
                            class="text-blue-500 hover:text-blue-700 text-[10px]"><i class="fas fa-pen"></i></button>
                        <button onclick="annualDeleteNote('${n.sys_id}','${dateStr}');return false;"
                            class="text-red-400 hover:text-red-600 text-[10px]"><i class="fas fa-trash"></i></button>` : ''}
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    window.annualEditNote = function(sysId, dateStr, title, body, repeatYearly) {
        document.getElementById('annualNoteSysId').value = sysId;
        document.getElementById('annualNoteDate').value  = dateStr;
        document.getElementById('annualNoteTitle').value = title;
        document.getElementById('annualNoteBody').value  = body;
        document.getElementById('annualNoteRepeat').checked = !!repeatYearly;
        document.getElementById('annualNoteCancel').classList.remove('hidden');
        document.getElementById('annualNoteTitle').focus();
    };

    window.annualDeleteNote = async function(sysId, dateStr) {
        if (!confirm('Delete this note?')) return;
        try {
            await apiPost('/api/notes/endpoints.php', {action:'delete', sys_id: sysId});
            // Refresh notes for this date
            const r = await apiPost('/api/notes/endpoints.php', {action:'list_date', date: dateStr});
            annualNoteCache[dateStr] = r.notes || r.data || [];
            // Update dot indicator on calendar cell
            const cell = document.querySelector(`#annualGrid [data-anndate="${dateStr}"]`);
            if (cell) {
                const hasNote = !!(annualNoteCache[dateStr] && annualNoteCache[dateStr].length);
                cell.className = `aspect-square rounded text-[10px] flex items-center justify-center border ${annualDayCellClass(dateStr)} cursor-pointer transition-transform relative select-none`;
                const dot = cell.querySelector('span');
                if (!hasNote && dot) dot.remove();
            }
            annualRenderNotesList(dateStr);
        } catch(e) { alert('Delete failed.'); }
    };

    document.getElementById('annualNoteSave')?.addEventListener('click', async function() {
        const dateStr     = document.getElementById('annualNoteDate').value;
        const sysId       = document.getElementById('annualNoteSysId').value;
        const title       = document.getElementById('annualNoteTitle').value.trim();
        const body        = document.getElementById('annualNoteBody').value.trim();
        const repeatYearly= document.getElementById('annualNoteRepeat').checked ? 1 : 0;
        if (!title && !body) { alert('Please enter a title or note text.'); return; }
        try {
            if (sysId) {
                await apiPost('/api/notes/endpoints.php', {action:'update', sys_id:sysId, title, note_text:body, repeat_yearly:repeatYearly});
            } else {
                await apiPost('/api/notes/endpoints.php', {action:'add', date:dateStr, title, note_text:body, repeat_yearly:repeatYearly});
            }
            const r = await apiPost('/api/notes/endpoints.php', {action:'list_date', date: dateStr});
            annualNoteCache[dateStr] = r.data || [];
            document.getElementById('annualNoteTitle').value = '';
            document.getElementById('annualNoteBody').value  = '';
            document.getElementById('annualNoteSysId').value = '';
            document.getElementById('annualNoteRepeat').checked = false;
            document.getElementById('annualNoteCancel').classList.add('hidden');
            // Dot indicator
            const cell = document.querySelector(`#annualGrid [data-anndate="${dateStr}"]`);
            if (cell) {
                cell.className = `aspect-square rounded text-[10px] flex items-center justify-center border ${annualDayCellClass(dateStr)} cursor-pointer transition-transform relative select-none`;
                if (!cell.querySelector('span')) {
                    const dot = document.createElement('span');
                    dot.className = 'absolute top-0 right-0 w-1 h-1 rounded-full bg-violet-500';
                    cell.appendChild(dot);
                }
            }
            annualRenderNotesList(dateStr);
        } catch(e) { alert('Save failed: ' + e.message); }
    });

    document.getElementById('annualNoteCancel')?.addEventListener('click', () => {
        document.getElementById('annualNoteSysId').value  = '';
        document.getElementById('annualNoteTitle').value  = '';
        document.getElementById('annualNoteBody').value   = '';
        document.getElementById('annualNoteRepeat').checked = false;
        document.getElementById('annualNoteCancel').classList.add('hidden');
    });

    document.getElementById('annualDayPanelClose')?.addEventListener('click', () => {
        annualSelectedDate = null;
        document.getElementById('annualDayPanel').classList.add('hidden');
        // Remove selected highlight
        document.querySelectorAll('#annualGrid [data-anndate]').forEach(el => {
            const ds = el.getAttribute('data-anndate');
            el.className = `aspect-square rounded text-[10px] flex items-center justify-center border ${annualDayCellClass(ds)} cursor-pointer transition-transform relative select-none`;
        });
    });

    async function initAnnualCalendar() {
        annualYear = new Date().getFullYear();
        document.getElementById('annualYearLabel').textContent = annualYear;
        await loadAnnualData();
    }

    document.getElementById('annualPrevYear')?.addEventListener('click', async () => {
        annualYear--; annualSelectedDate = null;
        document.getElementById('annualDayPanel').classList.add('hidden');
        await loadAnnualData();
    });
    document.getElementById('annualNextYear')?.addEventListener('click', async () => {
        annualYear++; annualSelectedDate = null;
        document.getElementById('annualDayPanel').classList.add('hidden');
        await loadAnnualData();
    });
    document.getElementById('annualTodayBtn')?.addEventListener('click', async () => {
        const todayStr  = new Date().toISOString().slice(0,10);
        const todayYear = new Date().getFullYear();
        if (annualYear !== todayYear) {
            annualYear = todayYear;
            annualSelectedDate = null;
            document.getElementById('annualDayPanel').classList.add('hidden');
            await loadAnnualData();
        }
        // Small scroll to today's cell then select it
        setTimeout(() => {
            const todayCell = document.querySelector(`#annualGrid [data-anndate="${todayStr}"]`);
            if (todayCell) {
                todayCell.scrollIntoView({behavior:'smooth', block:'center'});
                annualSelectDay(todayStr);
            }
        }, annualYear !== new Date().getFullYear() ? 600 : 50);
    });
    document.getElementById('annualPrintBtn')?.addEventListener('click', () => {
        const content = document.getElementById('mpsec-annual').innerHTML;
        const win = window.open('', '_blank');
        win.document.write(`<!DOCTYPE html><html><head>
            <title>Annual Calendar ${annualYear} — TravHub</title>
            <script src="https://cdn.tailwindcss.com"><\/script>
            <style>body{padding:24px;font-family:sans-serif} @media print{#annualDayPanel,button{display:none}}</style>
        </head><body><div class="max-w-5xl mx-auto">${content}</div></body></html>`);
        win.document.close();
        setTimeout(() => { win.focus(); win.print(); }, 800);
    });

    document.getElementById('qaShare')?.addEventListener('click', () => {
        const url = window.location.href;
        if (navigator.share) {
            navigator.share({title:'My TravHub Profile', url});
        } else {
            navigator.clipboard.writeText(url).then(() => alert('Profile URL copied to clipboard!'));
        }
    });
    document.getElementById('qaVisit')?.addEventListener('click', () => {
        alert('Visiting Card — Coming soon!');
    });

}); // end DOMContentLoaded
</script>
</body>
</html>