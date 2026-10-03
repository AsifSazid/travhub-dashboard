<?php
// PATH: pages/my-profile.php
// Self-service profile page — modular, split into pages/profile/ includes
// ==========================================================================

include_once('./authenticate.php');
require_once __DIR__ . '/../server/db_connection.php';
require_once __DIR__ . '/../server/permissions.php';

// All PHP vars
include __DIR__ . '/profile/_init.php';
?>
<?php include __DIR__ . '/profile/_head.php'; ?>
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

    <?php include __DIR__ . '/profile/_hero.php'; ?>

    <!-- ── Main layout: sidebar + content ─────────────────────── -->
    <div class="flex flex-col md:flex-row gap-5">

        <?php include __DIR__ . '/profile/_nav.php'; ?>

        <!-- Content area -->
        <div class="flex-1 min-w-0">
            <?php include __DIR__ . '/profile/_sec-myinfo.php'; ?>
            <?php include __DIR__ . '/profile/_sec-attendance.php'; ?>
            <?php include __DIR__ . '/profile/_sec-documents.php'; ?>
            <?php include __DIR__ . '/profile/_sec-credentials.php'; ?>
            <?php include __DIR__ . '/profile/_sec-notifications.php'; ?>
            <?php include __DIR__ . '/profile/_sec-payroll.php'; ?>
            <?php include __DIR__ . '/profile/_sec-annual.php'; ?>
            <?php include __DIR__ . '/profile/_sec-explore.php'; ?>
            <?php include __DIR__ . '/profile/_sec-permissions.php'; ?>
        </div><!-- /content area -->

    </div><!-- /main layout -->

</div><!-- /container -->
</main>

<?php include __DIR__ . '/profile/_modals.php'; ?>

<script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
<script src="../assets/js/functional/dashboard.js?time=<?php echo time(); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const API_BASE   = "<?= rtrim($API_BASE, '/') ?>";
    const MY_SYS_ID  = "<?= htmlspecialchars($myEmpId, ENT_QUOTES) ?>";
    const IS_HR      = <?= $isHR ? 'true' : 'false' ?>;

    const MONTH_NAMES = ['January','February','March','April','May','June',
                         'July','August','September','October','November','December'];

<?php include __DIR__ . '/profile/_js-core.php'; ?>
<?php include __DIR__ . '/profile/_js-attendance.php'; ?>
<?php include __DIR__ . '/profile/_js-misc.php'; ?>
<?php include __DIR__ . '/profile/_js-annual.php'; ?>
<?php include __DIR__ . '/profile/_js-permissions.php'; ?>

}); // end DOMContentLoaded
</script>
</body>
</html>
