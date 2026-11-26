<?php
require_once __DIR__ . '/includes/app.php';
$pageTitle = 'Leakage Complaints';
$customersList = $pdo->query('SELECT customer_id, full_name FROM customers ORDER BY full_name')->fetchAll();
$zonesList = $pdo->query('SELECT zone_id, zone_name FROM zones ORDER BY zone_name')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        $zoneId = ($_POST['zone_id'] ?? '') !== '' ? (int) $_POST['zone_id'] : null;
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($customerId < 1 || $location === '' || $description === '') {
            set_flash('Customer, leak location, and description are required.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO complaints (customer_id, zone_id, location, description) VALUES (?, ?, ?, ?)');
            $stmt->execute([$customerId, $zoneId, $location, $description]);
            set_flash('Leakage complaint registered.');
            redirect('complaints.php');
        }
    } elseif ($action === 'status') {
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['Open', 'Investigating', 'Resolved', 'Closed'], true)) {
            set_flash('Choose a valid complaint status.');
        } else {
            $stmt = $pdo->prepare('UPDATE complaints SET status = ? WHERE complaint_id = ?');
            $stmt->execute([$status, (int) ($_POST['complaint_id'] ?? 0)]);
            set_flash('Complaint status updated.');
            redirect('complaints.php');
        }
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM complaints WHERE complaint_id = ?');
        $stmt->execute([(int) ($_POST['complaint_id'] ?? 0)]);
        set_flash('Complaint and its investigation records deleted.');
        redirect('complaints.php');
    }
}
$complaints = $pdo->query('SELECT cp.*, c.full_name, z.zone_name FROM complaints cp JOIN customers c ON c.customer_id = cp.customer_id LEFT JOIN zones z ON z.zone_id = cp.zone_id ORDER BY cp.reported_at DESC')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><h1>Leakage complaints</h1><p>Register reports from customers and track their resolution.</p></div></div>
<section class="panel"><h2>Register a leakage complaint</h2>
<?php if (!$customersList): ?><p class="empty">Add a customer before registering a complaint.</p><?php else: ?><form method="post"><?= form_token() ?><input type="hidden" name="action" value="add"><div class="form-grid">
<div class="field"><label for="customer_id">Reporting customer *</label><select id="customer_id" name="customer_id" required><option value="">Choose customer</option><?php foreach ($customersList as $customer): ?><option value="<?= h($customer['customer_id']) ?>"><?= h($customer['full_name']) ?></option><?php endforeach; ?></select></div>
<div class="field"><label for="zone_id">Zone</label><select id="zone_id" name="zone_id"><option value="">Not known / not assigned</option><?php foreach ($zonesList as $zone): ?><option value="<?= h($zone['zone_id']) ?>"><?= h($zone['zone_name']) ?></option><?php endforeach; ?></select></div>
<div class="field full"><label for="location">Leak location *</label><input id="location" name="location" maxlength="255" required placeholder="Street, landmark, or nearby address"></div>
<div class="field full"><label for="description">Description *</label><textarea id="description" name="description" required placeholder="Describe the leak and when it was noticed"></textarea></div></div><div class="form-actions"><button class="button" type="submit">Submit complaint</button></div></form><?php endif; ?></section>
<section class="panel"><h2>Complaint register <span class="badge"><?= count($complaints) ?> total</span></h2>
<?php if (!$complaints): ?><p class="empty">No complaints have been registered.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Reported</th><th>Customer</th><th>Zone / location</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($complaints as $complaint): ?><tr><td><?= h($complaint['reported_at']) ?></td><td><?= h($complaint['full_name']) ?></td><td><?= h($complaint['zone_name'] ?: '—') ?><br><?= h($complaint['location']) ?></td><td><?= h($complaint['description']) ?></td><td><span class="badge <?= strtolower(h($complaint['status'])) ?>"><?= h($complaint['status']) ?></span></td><td><div class="actions"><form method="post"><?= form_token() ?><input type="hidden" name="action" value="status"><input type="hidden" name="complaint_id" value="<?= h($complaint['complaint_id']) ?>"><select aria-label="Complaint status" name="status"><?php foreach (['Open', 'Investigating', 'Resolved', 'Closed'] as $status): ?><option <?= $complaint['status'] === $status ? 'selected' : '' ?>><?= h($status) ?></option><?php endforeach; ?></select><button class="link-button" type="submit">Save</button></form><form method="post" onsubmit="return confirm('Delete this complaint and linked investigation records?');"><?= form_token() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="complaint_id" value="<?= h($complaint['complaint_id']) ?>"><button class="link-button danger" type="submit">Delete</button></form></div></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
