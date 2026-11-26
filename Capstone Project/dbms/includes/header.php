<?php
$pageTitle = $pageTitle ?? 'Smart Water System';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?> | Smart Water</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php"><span class="brand-icon">W</span><span>Smart Water<span class="brand-subtitle">Distribution &amp; Investigation</span></span></a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav">Menu</button>
    <nav id="main-nav" class="main-nav" aria-label="Main navigation">
        <a class="<?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">Home</a>
        <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a>
        <a class="<?= $currentPage === 'customers.php' ? 'active' : '' ?>" href="customers.php">Customers</a>
        <a class="<?= $currentPage === 'meters.php' ? 'active' : '' ?>" href="meters.php">Meters</a>
        <a class="<?= $currentPage === 'zones.php' ? 'active' : '' ?>" href="zones.php">Zones</a>
        <a class="<?= $currentPage === 'complaints.php' ? 'active' : '' ?>" href="complaints.php">Complaints</a>
        <a class="<?= $currentPage === 'investigations.php' ? 'active' : '' ?>" href="investigations.php">Investigations</a>
        <a class="<?= $currentPage === 'analytics.php' ? 'active' : '' ?>" href="analytics.php">Data Explorer</a>
    </nav>
</header>
<main class="container">
<?php if (!empty($_SESSION['flash_message'])): ?>
    <div class="notice" role="status"><?= h($_SESSION['flash_message']) ?></div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>
