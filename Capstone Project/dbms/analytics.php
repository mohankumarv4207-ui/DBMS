<?php
require_once __DIR__ . '/includes/app.php';

$datasets = [
    'customer_meter_readings' => [
        'label' => 'Customer meter readings',
        'description' => 'Property-level meter readings, locations, zones, and consumption.',
        'columns' => [
            'id' => 'ID',
            'property_id' => 'Property ID',
            'meter_location' => 'Meter location',
            'dma' => 'DMA',
            'reading_date' => 'Reading date',
            'consumption' => 'Consumption',
        ],
        'order_by' => 'reading_date DESC, id DESC',
    ],
    'water_leak_readings' => [
        'label' => 'Water leak sensor readings',
        'description' => 'Timestamped pressure, flow, temperature, leak, and burst measurements.',
        'columns' => [
            'id' => 'ID',
            'recorded_at' => 'Recorded at',
            'sensor_id' => 'Sensor ID',
            'pressure_bar' => 'Pressure (bar)',
            'flow_rate_lps' => 'Flow rate (L/s)',
            'temperature_c' => 'Temperature (C)',
            'leak_status' => 'Leak status',
            'burst_status' => 'Burst status',
        ],
        'order_by' => 'recorded_at DESC, id DESC',
    ],
];

$datasetKey = $_GET['dataset'] ?? 'customer_meter_readings';
if (!is_string($datasetKey) || !isset($datasets[$datasetKey])) {
    http_response_code(400);
    exit('Invalid dataset selected.');
}

$pageInput = filter_var($_GET['page'] ?? '1', FILTER_VALIDATE_INT);
if ($pageInput === false || $pageInput < 1) {
    http_response_code(400);
    exit('Invalid page number.');
}

$pageSize = 50;
$dataset = $datasets[$datasetKey];

try {
    $analyticsPdo = new PDO(
        "mysql:host={$dbHost};dbname=smart_water_analytics;charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $countStatement = $analyticsPdo->query("SELECT COUNT(*) FROM `{$datasetKey}`");
    $totalRows = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($totalRows / $pageSize));
    $analyticsCurrentPage = min($pageInput, $totalPages);
    $offset = ($analyticsCurrentPage - 1) * $pageSize;
    $columnNames = implode(', ', array_map(
        static fn (string $column): string => "`{$column}`",
        array_keys($dataset['columns'])
    ));

    $rowsStatement = $analyticsPdo->prepare(
        "SELECT {$columnNames} FROM `{$datasetKey}` ORDER BY {$dataset['order_by']} LIMIT :page_size OFFSET :offset"
    );
    $rowsStatement->bindValue(':page_size', $pageSize, PDO::PARAM_INT);
    $rowsStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $rowsStatement->execute();
    $rows = $rowsStatement->fetchAll();
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Could not read the analytics database. Check that smart_water_analytics is available. Details: ' . h($exception->getMessage()));
}

$pageTitle = 'Data Explorer';
include __DIR__ . '/includes/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Data Explorer</h1>
        <p>Browse the imported meter and water-leak sensor datasets.</p>
    </div>
</div>
<section class="panel">
    <form method="get" class="dataset-controls">
        <div class="field">
            <label for="dataset">Dataset</label>
            <select id="dataset" name="dataset">
                <?php foreach ($datasets as $key => $option): ?>
                    <option value="<?= h($key) ?>" <?= $key === $datasetKey ? 'selected' : '' ?>><?= h($option['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="button" type="submit">View data</button>
    </form>
    <h2><?= h($dataset['label']) ?></h2>
    <p><?= h($dataset['description']) ?></p>
    <p class="data-summary">
        <?= h(number_format($totalRows)) ?> total entries
        &middot; Page <?= h($analyticsCurrentPage) ?> of <?= h($totalPages) ?>
        &middot; Showing <?= h(count($rows)) ?> entries
    </p>
    <?php if (!$rows): ?>
        <p class="empty">There are no entries in this dataset.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <?php foreach ($dataset['columns'] as $label): ?>
                            <th><?= h($label) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($dataset['columns'] as $column => $label): ?>
                                <td><?= h($row[$column]) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="Dataset pages">
                <?php if ($analyticsCurrentPage > 1): ?>
                    <a class="button" href="?dataset=<?= h(rawurlencode($datasetKey)) ?>&amp;page=<?= h($analyticsCurrentPage - 1) ?>">Previous</a>
                <?php endif; ?>
                <?php if ($analyticsCurrentPage < $totalPages): ?>
                    <a class="button" href="?dataset=<?= h(rawurlencode($datasetKey)) ?>&amp;page=<?= h($analyticsCurrentPage + 1) ?>">Next</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
