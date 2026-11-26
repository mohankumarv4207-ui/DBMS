<?php
require_once __DIR__ . '/includes/app.php';
$pageTitle = 'Customer Management';
$editing = null;
$zones = $pdo->query('SELECT zone_id, zone_name FROM zones ORDER BY zone_name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int) ($_POST['customer_id'] ?? 0);
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $zoneId = ($_POST['zone_id'] ?? '') !== '' ? (int) $_POST['zone_id'] : null;
        if ($name === '' || $phone === '' || $address === '') {
            set_flash('Name, phone, and address are required.');
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('Enter a valid email address, or leave it blank.');
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE customers SET full_name = ?, email = ?, phone = ?, address = ?, zone_id = ? WHERE customer_id = ?');
                $stmt->execute([$name, $email, $phone, $address, $zoneId, $id]);
                set_flash('Customer details updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO customers (full_name, email, phone, address, zone_id) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$name, $email, $phone, $address, $zoneId]);
                set_flash('Customer added.');
            }
            redirect('customers.php');
        }
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM customers WHERE customer_id = ?');
        $stmt->execute([(int) ($_POST['customer_id'] ?? 0)]);
        set_flash('Customer deleted. Their meters and complaints are also removed.');
        redirect('customers.php');
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE customer_id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}
$customers = $pdo->query(
    'SELECT c.*, z.zone_name FROM customers c LEFT JOIN zones z ON z.zone_id = c.zone_id ORDER BY c.customer_id DESC'
)->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><h1>Customers</h1><p>Add and maintain water service customer records.</p></div></div>
<section class="panel">
    <h2><?= $editing ? 'Update customer' : 'Add a customer' ?></h2>
    <form method="post">
        <?= form_token() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="customer_id" value="<?= h($editing['customer_id'] ?? '') ?>">
        <div class="form-grid">
            <div class="field"><label for="full_name">Full name *</label><input id="full_name" name="full_name" maxlength="120" required value="<?= h($editing['full_name'] ?? '') ?>"></div>
            <div class="field"><label for="phone">Phone *</label><input id="phone" name="phone" maxlength="30" required value="<?= h($editing['phone'] ?? '') ?>"></div>
            <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="150" value="<?= h($editing['email'] ?? '') ?>"></div>
            <div class="field"><label for="zone_id">Service zone</label><select id="zone_id" name="zone_id"><option value="">Not assigned</option><?php foreach ($zones as $zone): ?><option value="<?= h($zone['zone_id']) ?>" <?= (string) ($editing['zone_id'] ?? '') === (string) $zone['zone_id'] ? 'selected' : '' ?>><?= h($zone['zone_name']) ?></option><?php endforeach; ?></select></div>
            <div class="field full"><label for="address">Service address *</label><input id="address" name="address" maxlength="255" required value="<?= h($editing['address'] ?? '') ?>"></div>
        </div>
        <div class="form-actions"><button class="button" type="submit"><?= $editing ? 'Save changes' : 'Add customer' ?></button><?php if ($editing): ?><a class="cancel-link" href="customers.php">Cancel</a><?php endif; ?></div>
    </form>
</section>
<section class="panel">
    <h2>Customer directory <span class="badge"><?= count($customers) ?> total</span></h2>
    <?php if (!$customers): ?><p class="empty">No customers yet. Add the first customer using the form above.</p><?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Name</th><th>Phone / email</th><th>Address</th><th>Zone</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($customers as $customer): ?><tr>
        <td><?= h($customer['full_name']) ?></td><td><?= h($customer['phone']) ?><br><?= h($customer['email'] ?: '—') ?></td>
        <td><?= h($customer['address']) ?></td><td><?= h($customer['zone_name'] ?: '—') ?></td>
        <td><div class="actions"><a href="customers.php?edit=<?= h($customer['customer_id']) ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this customer and their related meters and complaints?');"><?= form_token() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="customer_id" value="<?= h($customer['customer_id']) ?>"><button class="link-button danger" type="submit">Delete</button></form></div></td>
    </tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
