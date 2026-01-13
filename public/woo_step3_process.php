<?php

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Importer/WooImporter.php';
require_once __DIR__ . '/../src/Normalizer/UniversalBuilder.php';
require_once __DIR__ . '/../src/Exporter/ShopifyExporter.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

$db = new Database();

$jobId = (int) $_POST['job_id'];
$mappings = $_POST['map'] ?? [];

if (!$jobId || empty($mappings)) {
    die('Missing job or mappings');
}

/**
 * 1️⃣ Clear existing mappings for this job
 */
$db->query("
    DELETE FROM job_mappings
    WHERE job_id = ?
", [$jobId]);

/**
 * 2️⃣ Save mappings
 */
foreach ($mappings as $sourceColumn => $universalField) {

    if ($universalField === '') continue;

    $db->query("
        INSERT INTO job_mappings
        (job_id, source_column, universal_field)
        VALUES (?,?,?)
    ", [
        $jobId,
        trim($sourceColumn),
        trim($universalField)
    ]);
}

/**
 * 3️⃣ Import Woo CSV → source_*
 */
$job = $db->query("
    SELECT * FROM import_jobs WHERE id = ?
", [$jobId])->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    die('Invalid job');
}

$csvPath = __DIR__ . '/../uploads/' . $job['file_name'];

$importer = new WooImporter($jobId);
$importer->import($csvPath);

/**
 * 4️⃣ Build universal_* tables
 */
$builder = new UniversalBuilder($jobId);
$builder->build();

/**
 * 5️⃣ Export to Shopify CSV
 */
$outputFile = __DIR__ . '/../uploads/shopify_export_' . $jobId . '.csv';

$exporter = new ShopifyExporter($jobId);
$exporter->export($outputFile);

/**
 * 6️⃣ Update job status
 */
$db->query("
    UPDATE import_jobs
    SET status = 'completed', completed_at = NOW()
    WHERE id = ?
", [$jobId]);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Export Ready</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            padding: 40px;
            text-align: center;
        }
        .box {
            background: #fff;
            max-width: 500px;
            margin: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,.08);
        }
        a {
            display: inline-block;
            margin-top: 15px;
            background: #4a6cf7;
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Shopify Export Ready</h2>
    <p>Your WooCommerce data has been converted successfully.</p>
    <a href="<?= '../uploads/' . basename($outputFile) ?>" download>
        Download Shopify CSV
    </a>
</div>

</body>
</html>
