<?php
require_once __DIR__ . '/includes/app.php';
$pageTitle = 'Dashboard';
$counts = [
    'customers' => (int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn(),
    'meters' => (int) $pdo->query('SELECT COUNT(*) FROM meters')->fetchColumn(),
    'complaints' => (int) $pdo->query('SELECT COUNT(*) FROM complaints')->fetchColumn(),
    'repairs' => (int) $pdo->query("SELECT COUNT(*) FROM repair_activities WHERE status <> 'Completed'")->fetchColumn(),
];
$recentComplaints = $pdo->query('SELECT cp.complaint_id, cp.location, cp.status, cp.reported_at, c.full_name FROM complaints cp JOIN customers c ON c.customer_id = cp.customer_id ORDER BY cp.reported_at DESC LIMIT 6')->fetchAll();
$recentRepairs = $pdo->query("SELECT r.repair_id, r.worker_name, r.job_description, r.status, cp.location FROM repair_activities r JOIN inspections i ON i.inspection_id = r.inspection_id JOIN complaints cp ON cp.complaint_id = i.complaint_id ORDER BY r.assigned_at DESC LIMIT 6")->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><h1>Dashboard</h1><p>A quick overview of customers, meters, reported leaks, and outstanding work.</p></div><a class="button" href="complaints.php">Register complaint</a></div>
<section class="stats-grid" aria-label="System totals">
    <article class="stat-card"><span>Total customers</span><strong><?= h($counts['customers']) ?></strong></article>
    <article class="stat-card"><span>Water meters</span><strong><?= h($counts['meters']) ?></strong></article>
    <article class="stat-card"><span>Leakage complaints</span><strong><?= h($counts['complaints']) ?></strong></article>
    <article class="stat-card"><span>Pending repairs</span><strong><?= h($counts['repairs']) ?></strong></article>
</section>
<div class="dashboard-grid">
    <section class="panel"><h2>Recent complaints</h2><?php if (!$recentComplaints): ?><p class="empty">No complaints recorded yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Customer / location</th><th>Reported</th><th>Status</th></tr></thead><tbody><?php foreach ($recentComplaints as $complaint): ?><tr><td><?= h($complaint['full_name']) ?><br><?= h($complaint['location']) ?></td><td><?= h($complaint['reported_at']) ?></td><td><span class="badge <?= strtolower(h($complaint['status'])) ?>"><?= h($complaint['status']) ?></span></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?><p><a href="complaints.php">View all complaints &rarr;</a></p></section>
    <section class="panel"><h2>Recent repair jobs</h2><?php if (!$recentRepairs): ?><p class="empty">No repair jobs assigned yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Location / work</th><th>Worker</th><th>Status</th></tr></thead><tbody><?php foreach ($recentRepairs as $repair): ?><tr><td><?= h($repair['location']) ?><br><?= h($repair['job_description']) ?></td><td><?= h($repair['worker_name']) ?></td><td><span class="badge <?= strtolower(str_replace(' ', '-', h($repair['status']))) ?>"><?= h($repair['status']) ?></span></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?><p><a href="investigations.php">Manage inspections &amp; repairs &rarr;</a></p></section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
