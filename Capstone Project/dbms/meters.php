<?php
require_once __DIR__ . '/includes/app.php';
$pageTitle = 'Water Meter Management';
$editing = null;
$customersList = $pdo->query('SELECT customer_id, full_name FROM customers ORDER BY full_name')->fetchAll();
$zonesList = $pdo->query('SELECT zone_id, zone_name FROM zones ORDER BY zone_name')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save_meter') {
        $id = (int) ($_POST['meter_id'] ?? 0);
        $number = trim($_POST['meter_number'] ?? '');
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        $zoneId = ($_POST['zone_id'] ?? '') !== '' ? (int) $_POST['zone_id'] : null;
        $date = $_POST['installation_date'] ?? '';
        $status = $_POST['status'] ?? 'Active';
        if ($number === '' || $customerId < 1 || $date === '' || !in_array($status, ['Active', 'Inactive', 'Faulty'], true)) {
            set_flash('Meter number, customer, installation date, and valid status are required.');
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE meters SET meter_number = ?, customer_id = ?, zone_id = ?, installation_date = ?, status = ? WHERE meter_id = ?');
                $stmt->execute([$number, $customerId, $zoneId, $date, $status, $id]);
                set_flash('Meter updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO meters (meter_number, customer_id, zone_id, installation_date, status) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$number, $customerId, $zoneId, $date, $status]);
                set_flash('Meter added.');
            }
            redirect('meters.php');
        }
    } elseif ($action === 'add_reading') {
        $meterId = (int) ($_POST['meter_id'] ?? 0);
        $readingDate = $_POST['reading_date'] ?? '';
        $consumption = filter_var($_POST['consumption_m3'] ?? '', FILTER_VALIDATE_FLOAT);
        if ($meterId < 1 || $readingDate === '' || $consumption === false || $consumption < 0) {
            set_flash('Choose a meter, date, and non-negative consumption value.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO sensor_readings (meter_id, reading_date, consumption_m3, notes) VALUES (?, ?, ?, ?)');
            $stmt->execute([$meterId, str_replace('T', ' ', $readingDate) . ':00', $consumption, trim($_POST['reading_notes'] ?? '')]);
            set_flash('Consumption reading recorded.');
            redirect('meters.php');
        }
    } elseif ($action === 'delete_meter') {
        $stmt = $pdo->prepare('DELETE FROM meters WHERE meter_id = ?');
        $stmt->execute([(int) ($_POST['meter_id'] ?? 0)]);
        set_flash('Meter deleted. Its recorded readings were also removed.');
        redirect('meters.php');
    }
}
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM meters WHERE meter_id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}
$meters = $pdo->query('SELECT m.*, c.full_name, z.zone_name FROM meters m JOIN customers c ON c.customer_id = m.customer_id LEFT JOIN zones z ON z.zone_id = m.zone_id ORDER BY m.meter_id DESC')->fetchAll();
$readings = $pdo->query('SELECT r.*, m.meter_number, c.full_name FROM sensor_readings r JOIN meters m ON m.meter_id = r.meter_id JOIN customers c ON c.customer_id = m.customer_id ORDER BY r.reading_date DESC, r.reading_id DESC LIMIT 20')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><h1>Water meters &amp; readings</h1><p>Manage meter installations and record consumption readings in cubic metres.</p></div></div>
<section class="panel"><h2><?= $editing ? 'Update meter' : 'Register a meter' ?></h2>
<?php if (!$customersList): ?><p class="empty">Add a customer before registering a meter.</p><?php else: ?>
<form method="post"><?= form_token() ?><input type="hidden" name="action" value="save_meter"><input type="hidden" name="meter_id" value="<?= h($editing['meter_id'] ?? '') ?>">
<div class="form-grid"><div class="field"><label for="meter_number">Meter number *</label><input id="meter_number" name="meter_number" maxlength="60" required value="<?= h($editing['meter_number'] ?? '') ?>"></div>
<div class="field"><label for="customer_id">Customer *</label><select id="customer_id" name="customer_id" required><option value="">Choose customer</option><?php foreach ($customersList as $customer): ?><option value="<?= h($customer['customer_id']) ?>" <?= (string) ($editing['customer_id'] ?? '') === (string) $customer['customer_id'] ? 'selected' : '' ?>><?= h($customer['full_name']) ?></option><?php endforeach; ?></select></div>
<div class="field"><label for="meter_zone_id">Zone</label><select id="meter_zone_id" name="zone_id"><option value="">Not assigned</option><?php foreach ($zonesList as $zone): ?><option value="<?= h($zone['zone_id']) ?>" <?= (string) ($editing['zone_id'] ?? '') === (string) $zone['zone_id'] ? 'selected' : '' ?>><?= h($zone['zone_name']) ?></option><?php endforeach; ?></select></div>
<div class="field"><label for="installation_date">Installation date *</label><input id="installation_date" name="installation_date" type="date" required value="<?= h($editing['installation_date'] ?? date('Y-m-d')) ?>"></div>
<div class="field"><label for="meter_status">Status</label><select id="meter_status" name="status"><?php foreach (['Active', 'Inactive', 'Faulty'] as $status): ?><option <?= ($editing['status'] ?? 'Active') === $status ? 'selected' : '' ?>><?= h($status) ?></option><?php endforeach; ?></select></div></div>
<div class="form-actions"><button class="button" type="submit"><?= $editing ? 'Save changes' : 'Register meter' ?></button><?php if ($editing): ?><a class="cancel-link" href="meters.php">Cancel</a><?php endif; ?></div>
</form><?php endif; ?></section>
<section class="panel"><h2>Record consumption</h2>
<?php if (!$meters): ?><p class="empty">Register a meter before adding a reading.</p><?php else: ?><form method="post"><?= form_token() ?><input type="hidden" name="action" value="add_reading"><div class="form-grid">
<div class="field"><label for="reading_meter">Meter *</label><select id="reading_meter" name="meter_id" required><option value="">Choose meter</option><?php foreach ($meters as $meter): ?><option value="<?= h($meter['meter_id']) ?>"><?= h($meter['meter_number'] . ' — ' . $meter['full_name']) ?></option><?php endforeach; ?></select></div>
<div class="field"><label for="reading_date">Reading date and time *</label><input id="reading_date" name="reading_date" type="datetime-local" required value="<?= h(date('Y-m-d\TH:i')) ?>"></div>
<div class="field"><label for="consumption_m3">Consumption (m³) *</label><input id="consumption_m3" name="consumption_m3" type="number" min="0" step="0.001" required></div>
<div class="field"><label for="reading_notes">Notes</label><input id="reading_notes" name="reading_notes" maxlength="255"></div></div><div class="form-actions"><button class="button" type="submit">Save reading</button></div></form><?php endif; ?></section>
<section class="panel"><h2>Registered meters</h2><?php if (!$meters): ?><p class="empty">No meters registered yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Meter</th><th>Customer</th><th>Zone</th><th>Installed</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($meters as $meter): ?><tr><td><?= h($meter['meter_number']) ?></td><td><?= h($meter['full_name']) ?></td><td><?= h($meter['zone_name'] ?: '—') ?></td><td><?= h($meter['installation_date']) ?></td><td><span class="badge"><?= h($meter['status']) ?></span></td><td><div class="actions"><a href="meters.php?edit=<?= h($meter['meter_id']) ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this meter and its readings?');"><?= form_token() ?><input type="hidden" name="action" value="delete_meter"><input type="hidden" name="meter_id" value="<?= h($meter['meter_id']) ?>"><button class="link-button danger" type="submit">Delete</button></form></div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="panel"><h2>Latest readings</h2><?php if (!$readings): ?><p class="empty">No readings recorded yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Date / time</th><th>Meter</th><th>Customer</th><th>Consumption</th><th>Notes</th></tr></thead><tbody><?php foreach ($readings as $reading): ?><tr><td><?= h($reading['reading_date']) ?></td><td><?= h($reading['meter_number']) ?></td><td><?= h($reading['full_name']) ?></td><td><?= h($reading['consumption_m3']) ?> m³</td><td><?= h($reading['notes'] ?: '—') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
