<?php
require_once __DIR__ . '/includes/app.php';
$pageTitle = 'Leakage Investigation';
$complaintOptions = $pdo->query("SELECT complaint_id, location, status FROM complaints WHERE status IN ('Open', 'Investigating') ORDER BY reported_at DESC")->fetchAll();
$inspectionOptions = $pdo->query('SELECT i.inspection_id, i.worker_name, cp.location, cp.complaint_id FROM inspections i JOIN complaints cp ON cp.complaint_id = i.complaint_id ORDER BY i.inspection_id DESC')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add_inspection') {
        $complaintId = (int) ($_POST['complaint_id'] ?? 0);
        $inspectedAt = $_POST['inspected_at'] ?? '';
        $worker = trim($_POST['worker_name'] ?? '');
        $findings = trim($_POST['findings'] ?? '');
        if ($complaintId < 1 || $inspectedAt === '' || $worker === '' || $findings === '') {
            set_flash('Complaint, inspection date, worker, and findings are required.');
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO inspections (complaint_id, inspected_at, findings, worker_name) VALUES (?, ?, ?, ?)');
                $stmt->execute([$complaintId, str_replace('T', ' ', $inspectedAt) . ':00', $findings, $worker]);
                $stmt = $pdo->prepare("UPDATE complaints SET status = 'Investigating' WHERE complaint_id = ? AND status = 'Open'");
                $stmt->execute([$complaintId]);
                $pdo->commit();
                set_flash('Inspection recorded and complaint marked as investigating.');
                redirect('investigations.php');
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }
        }
    } elseif ($action === 'add_repair') {
        $inspectionId = (int) ($_POST['inspection_id'] ?? 0);
        $worker = trim($_POST['repair_worker'] ?? '');
        $description = trim($_POST['job_description'] ?? '');
        if ($inspectionId < 1 || $worker === '' || $description === '') {
            set_flash('Inspection, assigned worker, and repair description are required.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO repair_activities (inspection_id, worker_name, job_description, notes) VALUES (?, ?, ?, ?)');
            $stmt->execute([$inspectionId, $worker, $description, trim($_POST['repair_notes'] ?? '')]);
            set_flash('Repair job assigned.');
            redirect('investigations.php');
        }
    } elseif ($action === 'repair_status') {
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['Pending', 'In Progress', 'Completed'], true)) {
            set_flash('Choose a valid repair status.');
        } else {
            $completedAt = $status === 'Completed' ? date('Y-m-d H:i:s') : null;
            $stmt = $pdo->prepare('UPDATE repair_activities SET status = ?, completed_at = ? WHERE repair_id = ?');
            $stmt->execute([$status, $completedAt, (int) ($_POST['repair_id'] ?? 0)]);
            set_flash('Repair job status updated.');
            redirect('investigations.php');
        }
    } elseif ($action === 'inspection_status') {
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['Scheduled', 'Completed'], true)) {
            set_flash('Choose a valid inspection status.');
        } else {
            $stmt = $pdo->prepare('UPDATE inspections SET status = ? WHERE inspection_id = ?');
            $stmt->execute([$status, (int) ($_POST['inspection_id'] ?? 0)]);
            set_flash('Inspection status updated.');
            redirect('investigations.php');
        }
    }
}
$inspections = $pdo->query('SELECT i.*, cp.location, cp.complaint_id FROM inspections i JOIN complaints cp ON cp.complaint_id = i.complaint_id ORDER BY i.inspected_at DESC')->fetchAll();
$repairs = $pdo->query('SELECT r.*, i.worker_name AS inspector_name, i.inspected_at, cp.location FROM repair_activities r JOIN inspections i ON i.inspection_id = r.inspection_id JOIN complaints cp ON cp.complaint_id = i.complaint_id ORDER BY r.assigned_at DESC')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><h1>Leakage investigation</h1><p>Record pipeline inspections and assign repair activities to maintenance workers.</p></div></div>
<section class="panel"><h2>Record an inspection</h2>
<?php if (!$complaintOptions): ?><p class="empty">There are no open complaints to inspect. Register a complaint or update its status first.</p><?php else: ?><form method="post"><?= form_token() ?><input type="hidden" name="action" value="add_inspection"><div class="form-grid">
<div class="field"><label for="complaint_id">Open complaint *</label><select id="complaint_id" name="complaint_id" required><option value="">Choose complaint</option><?php foreach ($complaintOptions as $complaint): ?><option value="<?= h($complaint['complaint_id']) ?>">#<?= h($complaint['complaint_id']) ?> — <?= h($complaint['location']) ?> (<?= h($complaint['status']) ?>)</option><?php endforeach; ?></select></div>
<div class="field"><label for="inspected_at">Inspection date and time *</label><input id="inspected_at" name="inspected_at" type="datetime-local" value="<?= h(date('Y-m-d\TH:i')) ?>" required></div>
<div class="field"><label for="worker_name">Inspecting worker *</label><input id="worker_name" name="worker_name" maxlength="120" required></div>
<div class="field full"><label for="findings">Inspection findings *</label><textarea id="findings" name="findings" required></textarea></div></div><div class="form-actions"><button class="button" type="submit">Save inspection</button></div></form><?php endif; ?></section>
<section class="panel"><h2>Assign a repair job</h2>
<?php if (!$inspectionOptions): ?><p class="empty">Record an inspection before assigning a repair job.</p><?php else: ?><form method="post"><?= form_token() ?><input type="hidden" name="action" value="add_repair"><div class="form-grid">
<div class="field"><label for="inspection_id">Inspection *</label><select id="inspection_id" name="inspection_id" required><option value="">Choose inspection</option><?php foreach ($inspectionOptions as $inspection): ?><option value="<?= h($inspection['inspection_id']) ?>">#<?= h($inspection['inspection_id']) ?> — <?= h($inspection['location']) ?> (<?= h($inspection['worker_name']) ?>)</option><?php endforeach; ?></select></div>
<div class="field"><label for="repair_worker">Assigned maintenance worker *</label><input id="repair_worker" name="repair_worker" maxlength="120" required></div>
<div class="field full"><label for="job_description">Repair work *</label><textarea id="job_description" name="job_description" required placeholder="Describe the repair to be carried out"></textarea></div>
<div class="field full"><label for="repair_notes">Notes</label><textarea id="repair_notes" name="repair_notes"></textarea></div></div><div class="form-actions"><button class="button" type="submit">Assign repair</button></div></form><?php endif; ?></section>
<section class="panel"><h2>Inspection register</h2><?php if (!$inspections): ?><p class="empty">No inspections recorded yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Inspection</th><th>Complaint location</th><th>Inspected at</th><th>Worker</th><th>Findings</th><th>Status</th></tr></thead><tbody><?php foreach ($inspections as $inspection): ?><tr><td>#<?= h($inspection['inspection_id']) ?> / complaint #<?= h($inspection['complaint_id']) ?></td><td><?= h($inspection['location']) ?></td><td><?= h($inspection['inspected_at']) ?></td><td><?= h($inspection['worker_name']) ?></td><td><?= h($inspection['findings']) ?></td><td><form method="post"><?= form_token() ?><input type="hidden" name="action" value="inspection_status"><input type="hidden" name="inspection_id" value="<?= h($inspection['inspection_id']) ?>"><select name="status" aria-label="Inspection status"><?php foreach (['Scheduled', 'Completed'] as $status): ?><option <?= $inspection['status'] === $status ? 'selected' : '' ?>><?= h($status) ?></option><?php endforeach; ?></select><button class="link-button" type="submit">Save</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="panel"><h2>Repair activities</h2><?php if (!$repairs): ?><p class="empty">No repair jobs assigned yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Job</th><th>Location / work</th><th>Assigned worker</th><th>Assigned</th><th>Completed</th><th>Status</th></tr></thead><tbody><?php foreach ($repairs as $repair): ?><tr><td>#<?= h($repair['repair_id']) ?></td><td><?= h($repair['location']) ?><br><?= h($repair['job_description']) ?><?php if ($repair['notes']): ?><br><small><?= h($repair['notes']) ?></small><?php endif; ?></td><td><?= h($repair['worker_name']) ?></td><td><?= h($repair['assigned_at']) ?></td><td><?= h($repair['completed_at'] ?: '—') ?></td><td><form method="post"><?= form_token() ?><input type="hidden" name="action" value="repair_status"><input type="hidden" name="repair_id" value="<?= h($repair['repair_id']) ?>"><select name="status" aria-label="Repair status"><?php foreach (['Pending', 'In Progress', 'Completed'] as $status): ?><option <?= $repair['status'] === $status ? 'selected' : '' ?>><?= h($status) ?></option><?php endforeach; ?></select><button class="link-button" type="submit">Save</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
