<?php
require_once __DIR__ . '/includes/app.php';
$pageTitle = 'Zone Management';
$editing = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int) ($_POST['zone_id'] ?? 0);
        $name = trim($_POST['zone_name'] ?? '');
        $area = trim($_POST['area'] ?? '');
        $length = filter_var($_POST['pipeline_length_m'] ?? '0', FILTER_VALIDATE_FLOAT);
        $material = trim($_POST['pipeline_material'] ?? '');
        $status = $_POST['status'] ?? 'Active';
        $description = trim($_POST['description'] ?? '');
        if ($name === '' || $area === '' || $length === false || $length < 0 || !in_array($status, ['Active', 'Maintenance', 'Inactive'], true)) {
            set_flash('Enter a zone name, area, valid non-negative pipeline length, and valid status.');
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE zones SET zone_name = ?, area = ?, pipeline_length_m = ?, pipeline_material = ?, status = ?, description = ? WHERE zone_id = ?');
                $stmt->execute([$name, $area, $length, $material, $status, $description, $id]);
                set_flash('Zone updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO zones (zone_name, area, pipeline_length_m, pipeline_material, status, description) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $area, $length, $material, $status, $description]);
                set_flash('Zone added.');
            }
            redirect('zones.php');
        }
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM zones WHERE zone_id = ?');
        $stmt->execute([(int) ($_POST['zone_id'] ?? 0)]);
        set_flash('Zone deleted. Related customer zone assignments have been cleared.');
        redirect('zones.php');
    }
}
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM zones WHERE zone_id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}
$zones = $pdo->query('SELECT * FROM zones ORDER BY zone_name')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><h1>Distribution zones</h1><p>Maintain service areas and pipeline details.</p></div></div>
<section class="panel">
    <h2><?= $editing ? 'Update zone' : 'Add a zone' ?></h2>
    <form method="post"><?= form_token() ?><input type="hidden" name="action" value="save"><input type="hidden" name="zone_id" value="<?= h($editing['zone_id'] ?? '') ?>">
        <div class="form-grid">
            <div class="field"><label for="zone_name">Zone name *</label><input id="zone_name" name="zone_name" maxlength="100" required value="<?= h($editing['zone_name'] ?? '') ?>"></div>
            <div class="field"><label for="area">Area / locality *</label><input id="area" name="area" maxlength="150" required value="<?= h($editing['area'] ?? '') ?>"></div>
            <div class="field"><label for="pipeline_length_m">Pipeline length (m)</label><input id="pipeline_length_m" name="pipeline_length_m" type="number" min="0" step="0.01" value="<?= h($editing['pipeline_length_m'] ?? '0') ?>"></div>
            <div class="field"><label for="pipeline_material">Pipeline material</label><input id="pipeline_material" name="pipeline_material" maxlength="80" value="<?= h($editing['pipeline_material'] ?? '') ?>"></div>
            <div class="field"><label for="status">Status</label><select id="status" name="status"><?php foreach (['Active', 'Maintenance', 'Inactive'] as $status): ?><option <?= ($editing['status'] ?? 'Active') === $status ? 'selected' : '' ?>><?= h($status) ?></option><?php endforeach; ?></select></div>
            <div class="field full"><label for="description">Notes</label><textarea id="description" name="description"><?= h($editing['description'] ?? '') ?></textarea></div>
        </div>
        <div class="form-actions"><button class="button" type="submit"><?= $editing ? 'Save changes' : 'Add zone' ?></button><?php if ($editing): ?><a class="cancel-link" href="zones.php">Cancel</a><?php endif; ?></div>
    </form>
</section>
<section class="panel"><h2>Zone directory <span class="badge"><?= count($zones) ?> total</span></h2>
<?php if (!$zones): ?><p class="empty">No zones added yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Zone</th><th>Area</th><th>Pipeline</th><th>Status</th><th>Notes</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($zones as $zone): ?><tr><td><?= h($zone['zone_name']) ?></td><td><?= h($zone['area']) ?></td><td><?= h($zone['pipeline_length_m']) ?> m<br><?= h($zone['pipeline_material'] ?: 'Material not set') ?></td><td><span class="badge"><?= h($zone['status']) ?></span></td><td><?= h($zone['description'] ?: '—') ?></td><td><div class="actions"><a href="zones.php?edit=<?= h($zone['zone_id']) ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this zone?');"><?= form_token() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="zone_id" value="<?= h($zone['zone_id']) ?>"><button class="link-button danger" type="submit">Delete</button></form></div></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<?php include __DIR__ . '/includes/footer.php'; ?>
