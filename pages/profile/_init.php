<?php
// Profile init vars — included from my-profile.php (after authenticate/db/permissions already loaded)
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) { $ip_port = "https://dev.travhub.com.bd/"; }
$ip_port = rtrim($ip_port, '/') . '/';

$myEmpId = $_SESSION['user_id']     ?? '';
$myName  = $_SESSION['user_name']   ?? '';
$myEmail = $_SESSION['user_email']  ?? '';
$myDesg  = $_SESSION['designation'] ?? '';
// Both 'role' and 'user_role' keys exist in session (login app sets both).
// Use whichever is '0' — defensive: checks both so it works regardless of which key was set.
$myRole  = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
$isSuperAdmin        = ($myRole === '0') || ((string)($_SESSION['role'] ?? '') === '0');
$isHR                = $isSuperAdmin || canAccess($pdo, 'hrm_employee_view');
$canManagePerms      = $isSuperAdmin || hasPermission($pdo, $myEmpId, 'full_accounting_access');

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
