<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    header("Location: /UMS_Full_PHP_System/login.php");
    exit;
}


$BASE_URL = "/UMS_Full_PHP_System";


require_once __DIR__ . "/db.php";

// Detect current page file name (for active menu)
$current = basename($_SERVER['SCRIPT_NAME'] ?? "");

// Load logged-in user info
$userID = (int)$_SESSION['user_id'];
$userRes = $mysqli->query("
    SELECT full_name, profile_image, email 
    FROM users 
    WHERE id = {$userID}
");
$user = $userRes ? $userRes->fetch_assoc() : null;
if (!$user) {
    header("Location: {$BASE_URL}/logout.php");
    exit;
}

$profile_img = !empty($user['profile_image'])
    ? "{$BASE_URL}/uploads/profiles/{$user['profile_image']}"
    : "{$BASE_URL}/assets/default_avatar.png";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Utility Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- BOOTSTRAP CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- ICONS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<!-- APP CSS -->
<link href="<?= $BASE_URL ?>/assets/css/app.css" rel="stylesheet">

<!-- PROFILE CSS -->
<?php if (strpos(str_replace('\\','/', $_SERVER['SCRIPT_NAME'] ?? ''), '/user/') !== false): ?>
<link href="<?= $BASE_URL ?>/assets/css/profile.css" rel="stylesheet">
<?php endif; ?>

<!-- THEMES CSS -->
<link href="<?= $BASE_URL ?>/assets/css/themes.css" rel="stylesheet">

<!-- THEME UTILITIES CSS -->
<link href="<?= $BASE_URL ?>/assets/css/theme-utilities.css" rel="stylesheet">

<!-- ANIMATIONS CSS -->
<link href="<?= $BASE_URL ?>/assets/css/animations.css" rel="stylesheet">

<!-- Tailwind -->
<script>
tailwind = { config: { corePlugins: { preflight: false } } };
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    (function(){
        var THEMES = ['electric','pastel','glass'];
        var theme = localStorage.getItem('ums-theme');
        if (THEMES.indexOf(theme) === -1) {
            theme = 'electric';
            try { localStorage.setItem('ums-theme', theme); } catch (_) {}
        }
        function apply(){
            var body = document.body;
            if (!body) return;
            THEMES.forEach(function(t){ body.classList.remove('theme-' + t); });
            body.classList.add('theme-' + theme);
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', apply, { once: true });
        } else {
            apply();
        }
    })();
</script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<!-- BODY WITH THEME ATTRIBUTE -->
<body class="text-gray-900 theme-electric">


<nav class="bg-gray-900 text-white shadow-sm sticky top-0 z-50">
    <div class="container-padded flex justify-between items-center h-14">

        <!-- LOGO -->
        <a href="<?= $BASE_URL ?>/dashboard.php"
           class="font-semibold tracking-wide text-lg flex items-center gap-2 text-white">
            <i class="bi bi-lightning-charge-fill text-yellow-400"></i>
            UMS
        </a>

        <!-- DESKTOP MENU -->
        <div class="hidden lg:flex items-center gap-1">

            <?php
            $links = [
                ['dashboard.php','Dashboard','bi-speedometer2'],
                ['customers.php','Customers','bi-people'],
                ['meters.php','Meters','bi-box'],
                ['readings.php','Readings','bi-pencil-square'],
                ['bills.php','Bills','bi-receipt'],
                ['payments.php','Payments','bi-cash-coin'],
                ['tariffs.php','Tariffs','bi-tags'],
                ['reports.php','Reports','bi-bar-chart'],
                ['users.php','Users','bi-person-gear'],
            ];

            foreach ($links as $ln):
                $file  = $ln[0];
                $label = $ln[1];
                $icon  = $ln[2];
                $active = ($current === $file);
            ?>
                <a href="<?= $BASE_URL . '/' . $file ?>"
                   class="px-3 py-1.5 rounded-md text-sm flex items-center gap-2
                   <?= $active ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700' ?>">
                    <i class="<?= $icon ?>"></i> <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>

            <!-- THEME DROPDOWN -->
            <div class="dropdown ms-2">
                <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center"
                        type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-palette me-1"></i> Theme
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a href="#" class="dropdown-item theme-option" data-theme="electric"> Electric</a></li>
                    <li><a href="#" class="dropdown-item theme-option" data-theme="pastel"> Pastel</a></li>
                    <li><a href="#" class="dropdown-item theme-option" data-theme="glass"> Glass</a></li>
                </ul>
            </div>

            <!-- USER DROPDOWN -->
            <div class="dropdown ms-3">
                <button class="btn btn-dark dropdown-toggle px-3 py-1.5 d-flex align-items-center"
                        data-bs-toggle="dropdown" type="button">
                    <img src="<?= $profile_img ?>" 
                         style="width:28px;height:28px;border-radius:50%;object-fit:cover;margin-right:6px;">
                    <span class="d-none d-md-inline"><?= htmlspecialchars($user['full_name']) ?></span>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="<?= $BASE_URL ?>/user/profile.php"><i class="bi bi-person-badge me-2"></i> My Profile</a></li>
                    <li><a class="dropdown-item" href="<?= $BASE_URL ?>/user/profile_edit.php"><i class="bi bi-pencil me-2"></i> Edit Profile</a></li>
                    <li><a class="dropdown-item" href="<?= $BASE_URL ?>/user/profile_password.php"><i class="bi bi-key me-2"></i> Change Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= $BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>

        </div>

        <!-- MOBILE MENU BUTTON -->
        <button id="navToggle" class="lg:hidden p-2 text-gray-300 hover:bg-gray-700 rounded" type="button">
            <i class="bi bi-list text-2xl"></i>
        </button>

    </div>

    <!-- MOBILE MENU -->
    <div id="navMenu" class="hidden lg:hidden pb-3">
        <div class="flex flex-col gap-1 px-3">

            <?php foreach ($links as $ln): ?>
                <a href="<?= $BASE_URL . '/' . $ln[0] ?>"
                   class="px-3 py-2 rounded-md text-sm flex items-center gap-2 text-gray-300 hover:bg-gray-700">
                    <i class="<?= $ln[2] ?>"></i> <?= htmlspecialchars($ln[1]) ?>
                </a>
            <?php endforeach; ?>

            <div class="border-t border-gray-800 mt-2 pt-2 flex items-center justify-between">
                <div class="d-flex align-items-center gap-2">
                    <img src="<?= $profile_img ?>" 
                         style="width:30px;height:30px;border-radius:50%;object-fit:cover;">
                    <span class="text-gray-300 text-sm"><?= htmlspecialchars($user['full_name']) ?></span>
                </div>

                <a href="<?= $BASE_URL ?>/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
            </div>

        </div>
    </div>
</nav>

<!-- BOOTSTRAP JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Mobile Menu Toggle -->
<script>
document.getElementById('navToggle').addEventListener('click', function () {
    document.getElementById('navMenu').classList.toggle('hidden');
});
</script>

<!--  THEME MANAGER JS -->
<script src="<?= $BASE_URL ?>/assets/js/theme.js"></script>

<div class="container container-padded mb-6 animate-fade-in">
