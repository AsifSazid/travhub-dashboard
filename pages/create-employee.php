<?php
// FILE PATH: /pages/create-employee.php
include_once('./authenticate.php');
require_once __DIR__ . '/../server/db_connection.php';
require_once __DIR__ . '/../server/hrm_permissions.php';
requireHrm($pdo, 'hrm_employee_create', false);

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) $ip_port = "http://103.104.219.3:898";
$storeEmployeeApi = $ip_port . "api/employees/store.php";

$_deptRows = [];
try {
    $s = $pdo->query("SELECT id, sys_id, name FROM departments WHERE is_active=1 ORDER BY sort_order ASC, name ASC");
    $_deptRows = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $_e) { error_log('[create-employee] ' . $_e->getMessage()); }
$_deptJson = json_encode($_deptRows, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Employee — TravHub</title>
<link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
/* ── Wizard layout ── */
.wz-shell{display:flex;gap:0;min-height:calc(100vh - 100px)}

/* ── Sidebar ── */
.wz-sidebar{width:220px;flex-shrink:0;background:#fff;border-right:1.5px solid #f0f0f7;border-radius:16px 0 0 16px;padding:28px 0;display:flex;flex-direction:column}
.wz-sidebar-head{padding:0 20px 20px;border-bottom:1px solid #f0f0f7;margin-bottom:8px}
.wz-sidebar-head h2{font-size:.95rem;font-weight:700;color:#1e1b4b}
.wz-sidebar-head p{font-size:.72rem;color:#9ca3af;margin-top:2px}
.wz-nav{display:flex;flex-direction:column;gap:2px;padding:0 12px;flex:1}
.wz-nav-item{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;cursor:pointer;transition:all .15s;position:relative}
.wz-nav-item:not(.active):not(.done):hover{background:#f9f8ff}
.wz-nav-item.done{cursor:pointer}
.wz-nav-item.done .wz-step-dot{background:#6366f1;border-color:#6366f1;color:#fff}
.wz-nav-item.active{background:#eef2ff}
.wz-nav-item.active .wz-step-dot{background:#6366f1;border-color:#6366f1;color:#fff;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
.wz-nav-item.active .wz-step-label{color:#4f46e5;font-weight:700}
.wz-nav-item.active .wz-step-sub{color:#818cf8}
.wz-step-dot{width:30px;height:30px;border-radius:50%;border:2px solid #e5e7eb;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#9ca3af;flex-shrink:0;transition:all .2s;background:#fff}
.wz-step-label{font-size:.82rem;font-weight:600;color:#6b7280;line-height:1.2}
.wz-step-sub{font-size:.7rem;color:#9ca3af;margin-top:1px}
/* connector line between dots */
.wz-connector{width:2px;height:18px;background:#e5e7eb;margin:0 0 0 26px;transition:background .3s}
.wz-connector.done{background:#6366f1}

/* ── Form panel ── */
.wz-panel{flex:1;background:#fff;border-radius:0 16px 16px 0;padding:28px 32px;overflow:hidden}
.wz-step{display:none}
.wz-step.active{display:block;animation:fs .2s ease}
@keyframes fs{from{opacity:0;transform:translateX(8px)}to{opacity:1;transform:translateX(0)}}

/* ── Form fields ── */
.fi{width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:.85rem;color:#111827;background:#fff;outline:none;transition:border .15s}
.fi:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1)}
.fi.err{border-color:#ef4444}
.fi-ta{resize:none}
.lbl{display:block;font-size:.78rem;font-weight:600;color:#374151;margin-bottom:4px}
.req{color:#ef4444;margin-left:2px}
.frow{display:flex;flex-direction:column;gap:14px}

/* ── 2-col grid ── */
.fcols{display:grid;grid-template-columns:1fr 1fr;gap:20px 24px}
.fcols-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.span2{grid-column:span 2}

/* ── Photo uploader ── */
.photo-wrap{display:flex;flex-direction:column;align-items:center;gap:10px}
.photo-ring{position:relative;width:96px;height:96px;cursor:pointer}
.photo-ring img{width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid #e0e7ff}
.photo-ring .photo-overlay{position:absolute;inset:0;border-radius:50%;background:rgba(99,102,241,.55);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s}
.photo-ring:hover .photo-overlay{opacity:1}
.photo-ring .photo-overlay i{color:#fff;font-size:1.2rem}
.photo-placeholder{width:96px;height:96px;border-radius:50%;background:#eef2ff;border:2.5px dashed #c7d2fe;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s}
.photo-placeholder:hover{background:#e0e7ff;border-color:#818cf8}
.photo-placeholder i{color:#818cf8;font-size:1.4rem}
.photo-hint{font-size:.72rem;color:#9ca3af;text-align:center}

/* ── Type pills ── */
.tp-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.tp input[type="radio"]{display:none}
.tp label{display:flex;align-items:center;gap:7px;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:9px;cursor:pointer;font-size:.8rem;font-weight:600;color:#6b7280;transition:all .15s}
.tp input:checked+label{border-color:#6366f1;background:#eef2ff;color:#4f46e5}

/* ── Sec rows ── */
.sec-row{display:flex;gap:6px;align-items:center;margin-top:6px}

/* ── Summary ── */
.sum-row{display:flex;padding:5px 0;border-bottom:1px solid #f3f4f6;font-size:.81rem}
.sum-lbl{color:#6b7280;width:110px;flex-shrink:0;font-size:.77rem}
.sum-val{color:#111827;font-weight:500}

/* ── Nav buttons ── */
.wz-actions{display:flex;justify-content:space-between;align-items:center;margin-top:24px;padding-top:20px;border-top:1.5px solid #f3f4f6}
.btn-back{padding:8px 20px;border-radius:10px;border:1.5px solid #e5e7eb;font-size:.85rem;font-weight:600;color:#6b7280;cursor:pointer;transition:all .15s;background:#fff}
.btn-back:hover{background:#f9fafb}
.btn-next{padding:8px 24px;border-radius:10px;background:#6366f1;color:#fff;font-size:.85rem;font-weight:600;cursor:pointer;border:none;transition:all .15s}
.btn-next:hover{background:#4f46e5}
.btn-submit{padding:8px 24px;border-radius:10px;background:#059669;color:#fff;font-size:.85rem;font-weight:600;cursor:pointer;border:none;transition:all .15s}
.btn-submit:hover{background:#047857}

/* ── Toast ── */
#wz-toast{position:fixed;bottom:24px;right:24px;z-index:9999;padding:11px 17px;border-radius:11px;color:#fff;font-size:.83rem;font-weight:600;display:flex;align-items:center;gap:8px;transform:translateY(60px);opacity:0;transition:all .3s;pointer-events:none}
#wz-toast.show{transform:translateY(0);opacity:1}
#wz-toast.success{background:#059669}
#wz-toast.error{background:#dc2626}

/* ── Step heading ── */
.step-head{margin-bottom:20px}
.step-head h3{font-size:1rem;font-weight:700;color:#1e1b4b}
.step-head p{font-size:.78rem;color:#9ca3af;margin-top:2px}
</style>
</head>
<body class="bg-gray-100 font-sans">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>

<main id="mainContent" class="pt-20 pl-64 transition-all duration-300">
<div class="p-5">

<div class="mb-4 flex items-center justify-between">
    <div>
        <h1 class="text-lg font-bold text-gray-800"><i class="fas fa-user-plus mr-2 text-indigo-500"></i>Add New Employee</h1>
        <p class="text-xs text-gray-400 mt-0.5">Complete each section — navigate freely with the sidebar.</p>
    </div>
</div>

<form id="employeeForm" novalidate>
<div class="wz-shell rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

    <!-- ── Sidebar ── -->
    <div class="wz-sidebar">
        <div class="wz-sidebar-head">
            <h2>New Employee</h2>
            <p>4 steps to complete</p>
        </div>
        <div class="wz-nav" id="wzNav">
            <?php
            $navSteps = [
                ['fa-user',          'Personal',          'Photo, name, identity'],
                ['fa-phone',         'Contact',           'Phone & email'],
                ['fa-briefcase',     'Company',           'Role, dept, salary'],
                ['fa-map-marker-alt','Address & Review',  'Location & emergency'],
            ];
            foreach ($navSteps as $i => [$ic, $lbl, $sub]):
                $cls = $i === 0 ? 'active' : 'todo';
            ?>
            <?php if ($i > 0): ?><div class="wz-connector" id="conn<?= $i-1 ?>"></div><?php endif; ?>
            <div class="wz-nav-item <?= $cls ?>" id="nav<?= $i ?>" onclick="goToStep(<?= $i ?>)">
                <div class="wz-step-dot" id="dot<?= $i ?>">
                    <?php if ($i === 0): ?><i class="fas <?= $ic ?>"></i><?php else: ?><?= $i+1 ?><?php endif; ?>
                </div>
                <div>
                    <div class="wz-step-label"><?= $lbl ?></div>
                    <div class="wz-step-sub"><?= $sub ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- progress at bottom -->
        <div class="px-5 pt-4 mt-auto border-t border-gray-100">
            <div class="flex justify-between text-xs text-gray-400 mb-1"><span>Progress</span><span id="progressTxt">1 / 4</span></div>
            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden"><div class="h-full bg-indigo-500 rounded-full transition-all duration-300" id="progressBar" style="width:25%"></div></div>
        </div>
    </div>

    <!-- ── Form panel ── -->
    <div class="wz-panel">

        <!-- STEP 0: Personal -->
        <div class="wz-step active" id="step0">
            <div class="step-head">
                <h3>Personal Information</h3>
                <p>Basic identity details and employment type</p>
            </div>
            <div class="fcols">
                <!-- Left col -->
                <div class="frow">
                    <!-- Photo -->
                    <div>
                        <label class="lbl">Profile Photo</label>
                        <div class="photo-wrap mt-1">
                            <div id="photoRing" class="photo-placeholder" onclick="document.getElementById('profilePhotoInput').click()">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div class="photo-hint">JPG · PNG · WEBP<br>Click to upload</div>
                        </div>
                        <input type="file" id="profilePhotoInput" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="previewPhoto(this)">
                    </div>
                    <!-- Name -->
                    <div>
                        <label class="lbl" for="fullName">Full Name <span class="req">*</span></label>
                        <input class="fi" type="text" id="fullName" name="full_name" placeholder="e.g. Rahim Uddin Ahmed">
                    </div>
                    <!-- DOB + Blood -->
                    <div class="fcols-3" style="grid-template-columns:1fr 1fr">
                        <div>
                            <label class="lbl" for="dateOfBirth">Date of Birth</label>
                            <input class="fi" type="date" id="dateOfBirth" name="date_of_birth">
                        </div>
                        <div>
                            <label class="lbl">Blood Group</label>
                            <div class="relative">
                                <input class="fi" type="text" id="bloodGroupInput" placeholder="B+" readonly style="cursor:pointer">
                                <input type="hidden" id="selectedBloodGroupValue" name="blood_group">
                                <div id="bgDropdown" class="absolute hidden z-50 bg-white border border-gray-200 rounded-xl shadow-lg p-2 mt-1" style="display:none">
                                    <div class="grid grid-cols-4 gap-1">
                                        <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg):?>
                                        <button type="button" onclick="setBg('<?=$bg?>')" class="px-2 py-1.5 text-xs font-semibold rounded-lg border border-gray-100 hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-600 transition"><?=$bg?></button>
                                        <?php endforeach;?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- NID -->
                    <div>
                        <label class="lbl" for="nidNo">NID Number</label>
                        <input class="fi" type="text" id="nidNo" name="nid_no" placeholder="National ID">
                    </div>
                </div>
                <!-- Right col -->
                <div class="frow">
                    <div>
                        <label class="lbl">Employment Type <span class="req">*</span></label>
                        <div class="tp-grid mt-1">
                            <?php foreach(['permanent'=>['fa-id-badge','Permanent'],'probationary'=>['fa-clock','Probationary'],'contractual'=>['fa-file-contract','Contractual'],'intern'=>['fa-graduation-cap','Intern']] as $val=>[$ic,$lb]):?>
                            <div class="tp"><input type="radio" id="type_<?=$val?>" name="type" value="<?=$val?>" <?=$val==='permanent'?'checked':''?>><label for="type_<?=$val?>"><i class="fas <?=$ic?> text-xs"></i><?=$lb?></label></div>
                            <?php endforeach;?>
                        </div>
                    </div>
                    <div>
                        <label class="lbl" for="fatherName">Father's Name</label>
                        <input class="fi" type="text" id="fatherName" name="father_name">
                    </div>
                    <div>
                        <label class="lbl" for="motherName">Mother's Name</label>
                        <input class="fi" type="text" id="motherName" name="mother_name">
                    </div>
                    <div>
                        <label class="lbl" for="spouseName">Spouse Name</label>
                        <input class="fi" type="text" id="spouseName" name="spouse_name">
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 1: Contact -->
        <div class="wz-step" id="step1">
            <div class="step-head">
                <h3>Contact Information</h3>
                <p>Primary and additional contact details</p>
            </div>
            <div class="fcols">
                <div class="frow">
                    <div>
                        <label class="lbl" for="primaryPhone">Primary Phone <span class="req">*</span></label>
                        <input class="fi" type="tel" id="primaryPhone" name="primary_phone" placeholder="+880 1X XX XXX XXX">
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="lbl mb-0">Additional Phones</label>
                            <button type="button" onclick="addSecPhone()" class="text-xs text-indigo-600 font-semibold hover:underline"><i class="fas fa-plus mr-1"></i>Add</button>
                        </div>
                        <div id="secPhones"></div>
                    </div>
                </div>
                <div class="frow">
                    <div>
                        <label class="lbl" for="primaryEmail">Primary Email <span class="req">*</span></label>
                        <input class="fi" type="email" id="primaryEmail" name="primary_email" placeholder="name@company.com">
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="lbl mb-0">Additional Emails</label>
                            <button type="button" onclick="addSecEmail()" class="text-xs text-indigo-600 font-semibold hover:underline"><i class="fas fa-plus mr-1"></i>Add</button>
                        </div>
                        <div id="secEmails"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 2: Company -->
        <div class="wz-step" id="step2">
            <div class="step-head">
                <h3>Company Information</h3>
                <p>Role, department and reporting structure</p>
            </div>
            <div class="fcols">
                <div class="frow">
                    <div>
                        <label class="lbl" for="designation">Designation <span class="req">*</span></label>
                        <input class="fi" type="text" id="designation" name="designation" placeholder="e.g. Senior Executive">
                    </div>
                    <div>
                        <label class="lbl" for="dateOfJoin">Date of Join <span class="req">*</span></label>
                        <input class="fi" type="date" id="dateOfJoin" name="date_of_join">
                    </div>
                    <div>
                        <label class="lbl">Department <span class="req">*</span></label>
                        <div id="departmentSearchContainer" class="relative">
                            <input type="text" id="departmentInput" placeholder="Search department…" class="fi" autocomplete="off">
                            <ul id="departmentDropdown" class="absolute w-full bg-white border border-gray-200 rounded-xl mt-1 max-h-48 overflow-auto shadow-lg hidden z-50"></ul>
                        </div>
                        <input type="hidden" id="selectedDepartmentId"    name="department_id">
                        <input type="hidden" id="selectedDepartmentSysId" name="department_sys_id">
                    </div>
                    <div>
                        <label class="lbl" for="grossSalary">Gross Salary (BDT)</label>
                        <input class="fi" type="number" step="0.01" min="0" id="grossSalary" name="gross_salary" placeholder="0.00">
                    </div>
                </div>
                <div class="frow">
                    <div style="display:flex;flex-direction:column;flex:1">
                        <label class="lbl" for="companyRole">Role / Responsibilities <span class="req">*</span></label>
                        <textarea class="fi fi-ta" id="companyRole" name="company_role" placeholder="Key responsibilities…" style="flex:1;min-height:110px"></textarea>
                    </div>
                    <div>
                        <label class="lbl" for="reportingToName">Reporting To</label>
                        <input class="fi" type="text" id="reportingToName" name="reporting_to_name" placeholder="Manager's name">
                    </div>
                    <div>
                        <label class="lbl" for="reportingToDesignation">Their Designation</label>
                        <input class="fi" type="text" id="reportingToDesignation" name="reporting_to_designation" placeholder="e.g. General Manager">
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 3: Address + Emergency + Summary -->
        <div class="wz-step" id="step3">
            <div class="step-head">
                <h3>Address & Emergency Contact</h3>
                <p>Home address, emergency contact and final review</p>
            </div>
            <div class="fcols">
                <!-- Left: Address + Emergency stacked -->
                <div class="frow">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider -mb-2">Home Address</p>
                    <div>
                        <label class="lbl">Address Line 1</label>
                        <input class="fi" type="text" name="address_line_1" placeholder="House / Road / Block">
                    </div>
                    <div>
                        <label class="lbl">Address Line 2</label>
                        <input class="fi" type="text" name="address_line_2" placeholder="Area / Thana">
                    </div>
                    <div class="fcols-3">
                        <div><label class="lbl">City</label><input class="fi" type="text" name="city" placeholder="Dhaka"></div>
                        <div><label class="lbl">Division</label><input class="fi" type="text" name="state"></div>
                        <div><label class="lbl">Post Code</label><input class="fi" type="text" name="zip_code"></div>
                    </div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider -mb-2 mt-2">Emergency Contact</p>
                    <div class="fcols-3" style="grid-template-columns:1fr 1fr">
                        <div><label class="lbl">Person</label><input class="fi" type="text" id="emPerson" name="emergency_contact_person"></div>
                        <div><label class="lbl">Phone</label><input class="fi" type="tel" id="emPhone" name="emergency_phone"></div>
                    </div>
                    <div class="fcols-3" style="grid-template-columns:1fr 1fr">
                        <div>
                            <label class="lbl">Relation</label>
                            <select class="fi" name="relation">
                                <option value="">— Select —</option>
                                <?php foreach(['Father','Mother','Spouse','Brother','Sister','Friend','Other'] as $r):?><option><?=$r?></option><?php endforeach;?>
                            </select>
                        </div>
                        <div><label class="lbl">City</label><input class="fi" type="text" name="emergency_city"></div>
                    </div>
                </div>

                <!-- Right: Summary -->
                <div class="frow">
                    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 border border-indigo-100 rounded-2xl p-5 flex-1">
                        <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <i class="fas fa-clipboard-check"></i>Summary
                        </p>
                        <div id="reviewBody"></div>
                    </div>
                    <div class="rounded-xl border border-amber-100 bg-amber-50 p-3 flex gap-2 items-start text-xs text-amber-700">
                        <i class="fas fa-shield-alt mt-0.5 flex-shrink-0"></i>
                        <span>Default login password = <strong>System ID</strong>. Employee must change it on first login.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="wz-actions">
            <button type="button" id="btnBack" onclick="wizPrev()" class="btn-back hidden"><i class="fas fa-arrow-left mr-2"></i>Back</button>
            <div></div>
            <button type="button" id="btnNext" onclick="wizNext()" class="btn-next">Next <i class="fas fa-arrow-right ml-1"></i></button>
            <button type="submit" id="btnSubmit" class="btn-submit hidden"><i class="fas fa-check mr-1"></i>Add Employee</button>
        </div>

    </div><!-- /panel -->
</div><!-- /shell -->
</form>
</div>
</main>

<div id="wz-toast"><i id="wz-ti" class="fas fa-check-circle"></i><span id="wz-tm"></span></div>

<script>
const TOTAL=4;
const API_STORE="<?= $storeEmployeeApi ?>";
const API_PHOTO="<?= $ip_port ?>api/employees/upload-photo.php";
let cur=0, photoFile=null;

// ── Navigation ──────────────────────────────────────────────
function goToStep(n, validate=false) {
    if (validate && n > cur) {
        for (let i = cur; i < n; i++) { if (!validateStep(i)) return; }
    }
    // Update sidebar
    document.getElementById('step' + cur).classList.remove('active');
    for (let i = 0; i < TOTAL; i++) {
        const nav = document.getElementById('nav' + i);
        const dot = document.getElementById('dot' + i);
        const icons = ['fa-user','fa-phone','fa-briefcase','fa-map-marker-alt'];
        nav.className = 'wz-nav-item ' + (i < n ? 'done' : i === n ? 'active' : 'todo');
        dot.innerHTML = i < n
            ? '<i class="fas fa-check" style="font-size:.65rem"></i>'
            : i === n
                ? `<i class="fas ${icons[i]}" style="font-size:.65rem"></i>`
                : (i + 1);
        if (i < TOTAL - 1) {
            const conn = document.getElementById('conn' + i);
            if (conn) conn.className = 'wz-connector ' + (i < n ? 'done' : '');
        }
    }
    cur = n;
    document.getElementById('step' + cur).classList.add('active');
    document.getElementById('btnBack').classList.toggle('hidden', cur === 0);
    document.getElementById('btnNext').classList.toggle('hidden', cur === TOTAL - 1);
    document.getElementById('btnSubmit').classList.toggle('hidden', cur !== TOTAL - 1);
    // Progress
    document.getElementById('progressBar').style.width = ((cur + 1) / TOTAL * 100) + '%';
    document.getElementById('progressTxt').textContent = (cur + 1) + ' / ' + TOTAL;
    if (n === TOTAL - 1) buildReview();
}
function wizNext() { goToStep(Math.min(cur + 1, TOTAL - 1), true); }
function wizPrev() { goToStep(Math.max(cur - 1, 0)); }

// ── Validation ───────────────────────────────────────────────
function validateStep(n) {
    if (n === 0 && !vf('fullName', 'Full name is required')) return false;
    if (n === 1) {
        if (!vf('primaryPhone', 'Primary phone is required')) return false;
        if (!vf('primaryEmail', 'Primary email is required')) return false;
    }
    if (n === 2) {
        if (!vf('designation', 'Designation is required')) return false;
        if (!vf('companyRole', 'Role / responsibilities is required')) return false;
        if (!vf('dateOfJoin', 'Date of join is required')) return false;
        if (!document.getElementById('departmentInput').value.trim()) {
            toast('error', 'Please select a department');
            document.getElementById('departmentInput').classList.add('err');
            return false;
        }
    }
    return true;
}
function vf(id, msg) {
    const el = document.getElementById(id);
    if (!el.value.trim()) { toast('error', msg); el.classList.add('err'); el.focus(); return false; }
    el.classList.remove('err'); return true;
}

// ── Photo ────────────────────────────────────────────────────
function previewPhoto(inp) {
    const f = inp.files[0]; if (!f) return;
    photoFile = f;
    const r = new FileReader();
    r.onload = e => {
        document.getElementById('photoRing').outerHTML =
            `<div id="photoRing" class="photo-ring" onclick="document.getElementById('profilePhotoInput').click()">
                <img src="${e.target.result}" alt="">
                <div class="photo-overlay"><i class="fas fa-camera"></i></div>
            </div>`;
    };
    r.readAsDataURL(f);
}

// ── Blood group ──────────────────────────────────────────────
document.getElementById('bloodGroupInput').addEventListener('click', () => {
    const dd = document.getElementById('bgDropdown');
    dd.style.display = dd.style.display === 'none' || !dd.style.display ? 'block' : 'none';
});
function setBg(v) {
    document.getElementById('bloodGroupInput').value = v;
    document.getElementById('selectedBloodGroupValue').value = v;
    document.getElementById('bgDropdown').style.display = 'none';
}
document.addEventListener('click', e => {
    if (!e.target.closest('#bloodGroupInput') && !e.target.closest('#bgDropdown'))
        document.getElementById('bgDropdown').style.display = 'none';
});

// ── Department ───────────────────────────────────────────────
(function () {
    const data = <?= $_deptJson ?>;
    const inp = document.getElementById('departmentInput');
    const list = document.getElementById('departmentDropdown');
    const cont = document.getElementById('departmentSearchContainer');
    function render(arr) {
        list.innerHTML = arr.length
            ? arr.map(d => `<li class="px-4 py-2.5 cursor-pointer hover:bg-indigo-50 border-b last:border-b-0 text-sm" onclick="pickDept(${d.id},'${(d.sys_id??'').replace(/'/g,"\\'")}','${d.name.replace(/'/g,"\\'")}')">
                <span class="font-medium">${d.name}</span>
                <span class="text-xs text-gray-400 ml-2">${d.sys_id??''}</span></li>`).join('')
            : '<li class="px-4 py-3 text-sm text-gray-400">No department found</li>';
    }
    inp.addEventListener('focus', () => { render(data); list.classList.remove('hidden'); });
    inp.addEventListener('input', () => {
        const q = inp.value.toLowerCase();
        render(q ? data.filter(d => d.name.toLowerCase().includes(q) || (d.sys_id||'').toLowerCase().includes(q)) : data);
        list.classList.remove('hidden');
    });
    document.addEventListener('click', e => { if (!cont.contains(e.target)) list.classList.add('hidden'); });
})();
function pickDept(id, sysId, name) {
    document.getElementById('departmentInput').value = name;
    document.getElementById('selectedDepartmentId').value = id;
    document.getElementById('selectedDepartmentSysId').value = sysId;
    document.getElementById('departmentInput').classList.remove('err');
    document.getElementById('departmentDropdown').classList.add('hidden');
}

// ── Sec contacts ─────────────────────────────────────────────
function addSecPhone() {
    const r = document.createElement('div'); r.className = 'sec-row';
    r.innerHTML = `<select name="secondary_phone_type[]" class="fi" style="width:95px;flex-shrink:0"><option value="mobile">Mobile</option><option value="home">Home</option><option value="work">Work</option></select><input type="tel" name="secondary_phone_number[]" class="fi flex-1" placeholder="Number"><button type="button" onclick="this.parentElement.remove()" class="text-red-400 px-1.5 flex-shrink-0"><i class="fas fa-times text-xs"></i></button>`;
    document.getElementById('secPhones').appendChild(r);
}
function addSecEmail() {
    const r = document.createElement('div'); r.className = 'sec-row';
    r.innerHTML = `<select name="secondary_email_type[]" class="fi" style="width:95px;flex-shrink:0"><option value="work">Work</option><option value="personal">Personal</option><option value="other">Other</option></select><input type="email" name="secondary_email_address[]" class="fi flex-1" placeholder="Email"><button type="button" onclick="this.parentElement.remove()" class="text-red-400 px-1.5 flex-shrink-0"><i class="fas fa-times text-xs"></i></button>`;
    document.getElementById('secEmails').appendChild(r);
}

// ── Review ───────────────────────────────────────────────────
function g(id) { return document.getElementById(id)?.value?.trim() ?? ''; }
function buildReview() {
    const rows = [
        ['Full Name',   g('fullName')],
        ['Type',        document.querySelector('input[name="type"]:checked')?.value ?? '—'],
        ['Phone',       g('primaryPhone')],
        ['Email',       g('primaryEmail')],
        ['Designation', g('designation')],
        ['Department',  g('departmentInput') || '—'],
        ['Join Date',   g('dateOfJoin')],
        ['Salary',      g('grossSalary') ? 'BDT ' + g('grossSalary') : '—'],
        ['Reporting',   g('reportingToName') || '—'],
        ['City',        g('city') || '—'],
    ];
    document.getElementById('reviewBody').innerHTML = rows.map(([l, v]) =>
        `<div class="sum-row"><span class="sum-lbl">${l}</span><span class="sum-val">${v||'—'}</span></div>`
    ).join('');
}

// ── Toast ────────────────────────────────────────────────────
let _tt;
function toast(type, msg) {
    const el = document.getElementById('wz-toast');
    document.getElementById('wz-ti').className = 'fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle');
    document.getElementById('wz-tm').textContent = msg;
    el.className = 'show ' + type;
    clearTimeout(_tt); _tt = setTimeout(() => { el.className = type; }, 3200);
}

// ── Submit ───────────────────────────────────────────────────
document.getElementById('employeeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    if (!validateStep(0) || !validateStep(1) || !validateStep(2)) return;
    const btn = document.getElementById('btnSubmit');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Adding…'; btn.disabled = true;
    const fd = new FormData(this);
    const data = {
        type: fd.get('type') || 'permanent', full_name: fd.get('full_name'),
        status: 'active', date_of_birth: fd.get('date_of_birth'), blood_group: fd.get('blood_group')
    };
    const dId = document.getElementById('selectedDepartmentId').value;
    const dSid = document.getElementById('selectedDepartmentSysId').value;
    const dName = document.getElementById('departmentInput').value;
    if (dName) { data.department = dName; if (dId) data.department_id = dId; if (dSid) data.department_sys_id = dSid; }
    data.company_related_info = {
        designation: fd.get('designation'), company_role: fd.get('company_role'),
        date_of_join: fd.get('date_of_join'), father_name: fd.get('father_name')||null,
        mother_name: fd.get('mother_name')||null, spouse_name: fd.get('spouse_name')||null,
        nid_no: fd.get('nid_no')||null, gross_salary: fd.get('gross_salary')||null,
        reporting_to_name: fd.get('reporting_to_name')||null,
        reporting_to_designation: fd.get('reporting_to_designation')||null
    };
    data.phone = { primary_no: fd.get('primary_phone') };
    const spt = fd.getAll('secondary_phone_type[]'), spn = fd.getAll('secondary_phone_number[]');
    if (spt.length) data.phone.secondary_no = spt.map((t,i) => ({ type:t, number:spn[i]||'' }));
    data.email = { primary: fd.get('primary_email') };
    const set = fd.getAll('secondary_email_type[]'), sea = fd.getAll('secondary_email_address[]');
    if (set.length) data.email.secondary = set.map((t,i) => ({ type:t, address:sea[i]||'' }));
    data.address = {
        address_line_1: fd.get('address_line_1')||'', address_line_2: fd.get('address_line_2')||'',
        city: fd.get('city')||'', state: fd.get('state')||'', zip_code: fd.get('zip_code')||'',
        country: fd.get('country')||'Bangladesh'
    };
    data.emergency_contact = {
        person: fd.get('emergency_contact_person')||'', relation: fd.get('relation')||'',
        phone: fd.get('emergency_phone')||'',
        address: { address_line_1: fd.get('emergency_address_line_1')||'', city: fd.get('emergency_city')||'' }
    };
    const upload = new FormData();
    if (photoFile) upload.append('files[]', photoFile);
    upload.append('employee_data', JSON.stringify(data));
    try {
        const res = await fetch(API_STORE, { method:'POST', body: upload });
        const result = await res.json();
        if (result.success) {
            toast('success', 'Employee added! ID: ' + result.sys_id);
            if (photoFile && result.sys_id) {
                const pf = new FormData(); pf.append('sys_id', result.sys_id); pf.append('photo', photoFile);
                try { await fetch(API_PHOTO, { method:'POST', body:pf }); } catch {}
            }
            setTimeout(() => {
                this.reset(); photoFile = null;
                document.getElementById('selectedDepartmentId').value = '';
                document.getElementById('selectedDepartmentSysId').value = '';
                document.getElementById('selectedBloodGroupValue').value = '';
                document.getElementById('bloodGroupInput').value = '';
                document.getElementById('secPhones').innerHTML = '';
                document.getElementById('secEmails').innerHTML = '';
                document.getElementById('photoRing').outerHTML = `<div id="photoRing" class="photo-placeholder" onclick="document.getElementById('profilePhotoInput').click()"><i class="fas fa-camera"></i></div>`;
                goToStep(0);
            }, 2000);
        } else { toast('error', result.message || 'Failed'); }
    } catch (err) { toast('error', 'Network error: ' + err.message); }
    finally { btn.innerHTML = '<i class="fas fa-check mr-1"></i>Add Employee'; btn.disabled = false; }
});
document.getElementById('dateOfJoin').max = new Date().toISOString().split('T')[0];
</script>
</body>
</html>