<?php
require_once __DIR__ . '/includes/app.php';
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div>
        <p class="eyebrow">Reliable water, responsibly managed</p>
        <h1>Smart Water Distribution and Leakage Investigation System</h1>
        <p>One simple place to manage customers, water meters, distribution zones, leakage reports, and repair work.</p>
        <a class="button" href="dashboard.php">Open the dashboard</a>
    </div>
    <div class="hero-mark" aria-hidden="true">W</div>
</section>
<section class="content-section">
    <h2>Manage your water network</h2>
    <div class="feature-grid">
        <a class="feature-card" href="customers.php"><span>01</span><h3>Customers</h3><p>Keep customer contact details organized.</p></a>
        <a class="feature-card" href="meters.php"><span>02</span><h3>Meters &amp; readings</h3><p>Track installed meters and consumption readings.</p></a>
        <a class="feature-card" href="zones.php"><span>03</span><h3>Distribution zones</h3><p>Maintain zone and pipeline information.</p></a>
        <a class="feature-card" href="complaints.php"><span>04</span><h3>Leakage reports</h3><p>Register and follow up on reported leaks.</p></a>
        <a class="feature-card" href="investigations.php"><span>05</span><h3>Investigation &amp; repairs</h3><p>Record inspections and assign repair work.</p></a>
        <a class="feature-card" href="dashboard.php"><span>06</span><h3>Dashboard</h3><p>See current service and repair totals.</p></a>
        <a class="feature-card" href="analytics.php"><span>07</span><h3>Data Explorer</h3><p>Browse imported meter readings and leak sensor data.</p></a>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
