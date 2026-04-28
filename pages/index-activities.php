<?php
include_once('./authenticate.php');

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898/";
}

$apiBase         = $ip_port . "api/masterdata/activities/";
$apiSync         = $ip_port . "api/masterdata/activities/sync.php";
$apiCountriesBase = $ip_port . "api/masterdata/countries/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activities — Master Data</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="../assets/tailwind/script.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-50 font-sans">
    <?php include '../elements/header.php'; ?>
    <?php include '../elements/aside.php'; ?>
    <?php include '../elements/preview-model.php'; ?>

    <main id="mainContent" class="pt-16 pb-16 pl-64 md:pb-0 md:pl-16 lg:pl-64 transition-all duration-300">
        <!-- ActivityManager.init() injects all content here -->
    </main>

    <?php include '../elements/floating-menus.php'; ?>

    <script>
        const time = Date.now();
        const API_ACTIVITIES_BASE = "<?php echo $apiBase; ?>";
        const API_ACTIVITIES_SYNC = "<?php echo $apiSync; ?>";
        const API_COUNTRIES_BASE  = "<?php echo $apiCountriesBase; ?>";
    </script>
    <script src="../assets/js/script.js?time=<?php echo time(); ?>"></script>
    <script src="../assets/js/index-activity.js?time=<?php echo time(); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => ActivityManager.init());
    </script>
</body>
</html>