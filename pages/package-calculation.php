<?php
include_once('./authenticate.php');

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

// $package_uuid = trim($_GET['packageId'] ?? '');
$package_sys_id = trim($_GET['packageId'] ?? '');

$countriesApi = $ip_port."api/utilities/countries.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Calculator</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="../assets/tailwind/script.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>

    <!-- Preview Modal -->
    <?php include '../elements/preview-model.php'; ?>

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pb-16 pl-64 md:pb-0 md:pl-16 lg:pl-64 transition-all duration-300">
        <!-- PackageCalculation.init() injects all content here -->
    </main>

    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>

    <script>
        const time = Date.now();
        const API_COUNTRIES = "<?php echo $countriesApi; ?>";
    </script>

    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    <script src="../assets/js/package-calculation.js?time=<?php echo time(); ?>"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            PackageCalculation.init();
        });
    </script>
</body>

</html>