<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// অলরেডি ভেরিফাই করা থাকলে এই নোটিশ ফাইল কিছুই দেখাবে না
if (!empty($_SESSION['new_system_verified'])) {
    return;
}

const OLD_ADMIN_URL = 'https://oldadmin.travhub.com.bd';
const EMPLOYEE_VERIFY_API = '/api/employees/verify.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (empty($_SESSION['transition_csrf'])) {
    $_SESSION['transition_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['transition_csrf'];
?>
<!-- এরপরের HTML এবং JS কোড অপরিবর্তিত থাকবে -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>System Transition Notice | TravHub</title>

    <link
        rel="icon"
        type="image/png"
        href="../assets/images/logo/round-logo.png"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --navy: #1a2039;
            --text: #111827;
            --muted: #64748b;
            --border: #e2e8f0;
            --background: #f1f5f9;
            --success: #15803d;
            --danger: #dc2626;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;

            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;

            color: var(--text);
            background:
                radial-gradient(
                    circle at top left,
                    rgba(59, 130, 246, .12),
                    transparent 35%
                ),
                var(--background);
        }

        .notice-overlay {
            position: fixed;
            inset: 0;
            z-index: 99999;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 18px;
            overflow-y: auto;

            background: rgba(15, 23, 42, .72);

            backdrop-filter: blur(7px);
            -webkit-backdrop-filter: blur(7px);
        }

        .notice-modal {
            width: 100%;
            max-width: 510px;
            max-height: calc(100vh - 36px);
            overflow-y: auto;

            padding: 32px;

            background: #fff;
            border: 1px solid rgba(255, 255, 255, .7);
            border-radius: 18px;

            box-shadow: 0 25px 80px rgba(0, 0, 0, .25);

            text-align: center;

            animation: modalAppear .3s ease-out;
        }

        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: translateY(12px) scale(.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .notice-icon {
            width: 68px;
            height: 68px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto 17px;

            color: var(--primary);
            background: #eff6ff;
            border-radius: 18px;

            font-size: 28px;
        }

        .notice-badge {
            display: inline-flex;
            align-items: center;
            gap: 9px;

            margin-bottom: 17px;
            padding: 8px 13px;

            color: #1d4ed8;
            background: #eff6ff;
            border-radius: 999px;

            font-size: 12px;
            font-weight: 700;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            flex-shrink: 0;

            background: #3b82f6;
            border-radius: 50%;

            box-shadow: 0 0 0 4px rgba(59, 130, 246, .13);
        }

        .notice-modal h1 {
            margin-bottom: 13px;

            color: var(--navy);

            font-size: 27px;
            line-height: 1.3;
            font-weight: 750;
            letter-spacing: -.6px;
        }

        .description {
            margin-bottom: 13px;

            color: var(--muted);

            font-size: 14px;
            line-height: 1.8;
        }

        .description.bangla {
            margin-bottom: 23px;
            color: #475569;

            font-family:
                "Noto Sans Bengali",
                "SolaimanLipi",
                "Siyam Rupali",
                sans-serif;
        }

        .notice-btn {
            width: 100%;
            min-height: 48px;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;

            padding: 13px 16px;

            border: 0;
            border-radius: 9px;

            font-size: 13px;
            font-weight: 700;
            text-decoration: none;

            cursor: pointer;
            transition: background .2s ease, transform .2s ease;
        }

        .old-admin-btn {
            color: #fff;
            background: var(--navy);
        }

        .old-admin-btn:hover {
            color: #fff;
            background: #111827;
            transform: translateY(-1px);
        }

        .external-icon {
            margin-left: auto;
            font-size: 12px;
        }

        .notice-divider {
            display: flex;
            align-items: center;
            gap: 14px;

            margin: 21px 0;

            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
        }

        .notice-divider::before,
        .notice-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .employee-form {
            text-align: left;
        }

        .employee-form label {
            display: block;
            margin-bottom: 8px;

            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        .employee-input-wrap {
            display: flex;
            align-items: center;
            gap: 11px;

            padding: 0 14px;

            color: #64748b;
            border: 1px solid #cbd5e1;
            border-radius: 9px;

            transition: border-color .2s, box-shadow .2s;
        }

        .employee-input-wrap:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
        }

        .employee-input-wrap input {
            width: 100%;
            height: 46px;

            border: 0;
            outline: 0;

            color: #0f172a;
            background: transparent;

            font-size: 14px;
        }

        .employee-input-wrap input::placeholder {
            color: #94a3b8;
        }

        .employee-input-wrap input:disabled {
            opacity: .65;
        }

        .employee-message {
            display: none;
            margin-top: 9px;

            font-size: 12px;
            line-height: 1.6;

            overflow-wrap: anywhere;
        }

        .employee-message.error {
            display: block;
            color: var(--danger);
        }

        .employee-message.success {
            display: block;
            color: var(--success);
        }

        .employee-message.info {
            display: block;
            color: var(--primary);
        }

        .new-system-btn {
            margin-top: 15px;

            color: #fff;
            background: var(--primary);
        }

        .new-system-btn:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .new-system-btn:disabled {
            color: #94a3b8;
            background: #e2e8f0;
            cursor: not-allowed;
        }

        .notice-footer {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 8px;

            margin-top: 23px;
            padding-top: 17px;

            border-top: 1px solid var(--border);

            color: #94a3b8;
            font-size: 11px;
            line-height: 1.7;
        }

        .notice-footer i {
            margin-top: 3px;
        }

        @media (max-width: 480px) {
            body {
                padding: 0;
            }

            .notice-overlay {
                padding: 12px;
            }

            .notice-modal {
                padding: 25px 19px;
                max-height: calc(100vh - 24px);
                border-radius: 15px;
            }

            .notice-modal h1 {
                font-size: 24px;
            }

            .description {
                font-size: 13px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .notice-modal {
                animation: none;
            }

            .notice-btn {
                transition: none;
            }
        }
    </style>
</head>

<body>

<div class="notice-overlay">
    <main class="notice-modal" aria-labelledby="noticeTitle">

        <div class="notice-icon" aria-hidden="true">
            <i class="fa-solid fa-screwdriver-wrench"></i>
        </div>

        <div class="notice-badge">
            <span class="pulse-dot"></span>
            System Transition Notice
        </div>

        <h1 id="noticeTitle">Welcome to TravHub</h1>

        <p class="description">
            We have introduced our new system to improve your
            experience. Please choose how you would like to continue.
        </p>

        <p class="description bangla" lang="bn">
            TravHub-এর নতুন সিস্টেম চালু করা হয়েছে। আপনি চাইলে
            আগের Admin Panel-এ আপনার কাজ চালিয়ে যেতে পারেন।
            অথবা Employee ID দিয়ে নতুন সিস্টেমে প্রবেশ করতে পারেন।
        </p>

        <!-- OLD ADMIN OPTION -->
        <a
            href="<?= htmlspecialchars(OLD_ADMIN_URL, ENT_QUOTES, 'UTF-8') ?>"
            class="notice-btn old-admin-btn"
            target="_blank"
            rel="noopener noreferrer"
        >
            <i class="fa-solid fa-arrow-left"></i>

            <span>Continue with Old Admin</span>

            <i class="fa-solid fa-arrow-up-right-from-square external-icon"></i>
        </a>

        <div class="notice-divider">
            <span>OR</span>
        </div>

        <!-- NEW SYSTEM OPTION -->
        <form
            id="employeeForm"
            class="employee-form"
            method="post"
            action="<?= htmlspecialchars(EMPLOYEE_VERIFY_API, ENT_QUOTES, 'UTF-8') ?>"
            novalidate
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
            >

            <label for="employeeId">Employee ID</label>

            <div class="employee-input-wrap">
                <i class="fa-solid fa-id-card" aria-hidden="true"></i>

                <input
                    type="text"
                    id="employeeId"
                    name="employee_id"
                    placeholder="Enter your Employee ID"
                    autocomplete="username"
                    maxlength="100"
                    required
                    aria-describedby="employeeMessage"
                >
            </div>

            <p
                id="employeeMessage"
                class="employee-message"
                role="status"
                aria-live="polite"
            ></p>

            <button
                type="submit"
                id="newSystemBtn"
                class="notice-btn new-system-btn"
                disabled
            >
                <i class="fa-solid fa-shield-halved"></i>

                <span>Continue with New System</span>

                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <div class="notice-footer">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>

            <span>
                Need urgent assistance? Please contact your system
                administrator.
            </span>
        </div>

    </main>
</div>

<script>
(() => {
    'use strict';

    const form = document.getElementById('employeeForm');
    const input = document.getElementById('employeeId');
    const button = document.getElementById('newSystemBtn');
    const message = document.getElementById('employeeMessage');

    let requestController = null;
    let requestSequence = 0;

    function showMessage(text, type) {
        message.textContent = text;
        message.className = 'employee-message ' + type;
    }

    function setLoading(loading) {
        button.disabled = loading || !input.value.trim();
        input.disabled = loading;

        button.innerHTML = loading
            ? '<i class="fa-solid fa-spinner fa-spin"></i>' +
              '<span>Verifying Employee ID...</span>'
            : '<i class="fa-solid fa-shield-halved"></i>' +
              '<span>Continue with New System</span>' +
              '<i class="fa-solid fa-arrow-right"></i>';
    }

    input.addEventListener('input', () => {
        requestSequence++;

        if (requestController) {
            requestController.abort();
            requestController = null;
        }

        button.disabled = !input.value.trim();

        message.textContent = '';
        message.className = 'employee-message';
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const employeeId = input.value.trim();

        if (!employeeId) {
            showMessage('Please enter your Employee ID.', 'error');
            input.focus();
            return;
        }

        if (employeeId.length > 100) {
            showMessage('Employee ID is too long.', 'error');
            return;
        }

        if (requestController) {
            requestController.abort();
        }

        const controller = new AbortController();
        requestController = controller;

        const sequence = ++requestSequence;

        setLoading(true);

        showMessage(
            'Verifying your Employee ID. Please wait...',
            'info'
        );

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    employee_id: employeeId,
                    csrf_token: form.elements.csrf_token.value
                }),
                signal: controller.signal
            });

            if (sequence !== requestSequence) {
                return;
            }

            const contentType = response.headers.get('content-type') || '';

            if (!contentType.includes('application/json')) {
                throw new Error(
                    'The verification endpoint must return JSON.'
                );
            }

            const result = await response.json();

            if (!response.ok ||
                result.success !== true ||
                result.valid !== true) {

                showMessage(
                    result.message ||
                    'Invalid Employee ID. Please check and try again.',
                    'error'
                );

                return;
            }

            showMessage(
                'Employee ID verified. Redirecting...',
                'success'
            );

            // যে URL থেকে ইউজার হিট করেছিল, সরাসরি সেখানেই রিডাইরেক্ট / রিলোড হবে
            window.location.assign(window.location.href);

        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            if (sequence === requestSequence) {
                showMessage(
                    'Unable to verify your Employee ID. Please try again.',
                    'error'
                );
            }

        } finally {
            if (sequence === requestSequence) {
                requestController = null;
                input.disabled = false;
                setLoading(false);
            }
        }
    });
})();
</script>

</body>
</html>