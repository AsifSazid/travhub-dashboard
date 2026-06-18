<?php
include_once('./authenticate.php');

$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898/";
}

$pkg_sys_id = trim($_GET['sys_id'] ?? '');  // if editing existing

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Builder — TravHub</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
    <script src="../assets/tailwind/script.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-50 font-sans">
    <?php include '../elements/header.php'; ?>
    <?php include '../elements/aside.php'; ?>
    <?php include '../elements/preview-model.php'; ?>
    <main id="mainContent" class="pt-16 pb-16 pl-64 md:pb-0 md:pl-16 lg:pl-64 transition-all duration-300"></main>
    <?php include '../elements/floating-menus.php'; ?>
    <script>
        const API_BASE    = "<?php echo $ip_port; ?>";
        const PKG_SYS_ID  = "<?php echo htmlspecialchars($pkg_sys_id); ?>";
    </script>
    <script src="../assets/js/script.js?t=<?php echo time(); ?>"></script>
    <script src="../assets/js/th-utils.js?t=<?php echo time(); ?>"></script>
    <script src="../assets/js/pkg-builder.js?t=<?php echo time(); ?>"></script>
    <script>document.addEventListener('DOMContentLoaded', () => PkgBuilder.init(PKG_SYS_ID));</script>
</body>
</html>