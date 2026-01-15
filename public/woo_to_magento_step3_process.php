<?php

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Importer/WooImporter.php';
require_once __DIR__ . '/../src/Normalizer/UniversalBuilder.php';
require_once __DIR__ . '/../src/Exporter/MagentoExporter.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

$db = new Database();

$jobId = (int) ($_POST['job_id'] ?? 0);
$mappings = $_POST['map'] ?? [];

if (!$jobId || empty($mappings)) {
    die('Missing job or mappings');
}

/**
 * 1️⃣ Clear previous job mappings
 */
$db->query(
    "DELETE FROM job_mappings WHERE job_id = ?",
    [$jobId]
);

/**
 * 2️⃣ Save new mappings
 */
foreach ($mappings as $sourceColumn => $universalField) {
    if ($universalField === '') continue;

    $db->query(
        "INSERT INTO job_mappings (job_id, source_column, universal_field)
         VALUES (?,?,?)",
        [$jobId, trim($sourceColumn), trim($universalField)]
    );
}

/**
 * 3️⃣ Load job
 */
$job = $db->query(
    "SELECT * FROM import_jobs WHERE id = ?",
    [$jobId]
)->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    die('Invalid job');
}

$csvPath = __DIR__ . '/../uploads/' . $job['file_name'];

/**
 * 4️⃣ Import WooCommerce CSV
 */
$importer = new WooImporter($jobId);
$importer->import($csvPath);

/**
 * 5️⃣ Build universal model
 */
$builder = new UniversalBuilder($jobId);
$builder->build();

/**
 * 6️⃣ Export Magento CSV
 */
$outputFile = __DIR__ . '/../uploads/magento_export_' . $jobId . '.csv';

$exporter = new MagentoExporter($jobId);
$exporter->export($outputFile);

/**
 * 7️⃣ Mark job complete
 */
$db->query(
    "UPDATE import_jobs
     SET status = 'completed', completed_at = NOW()
     WHERE id = ?",
    [$jobId]
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Magento Export Ready</title>
    <style>
        body { font-family: Arial; background:#f4f6fb; margin:0; }
        .wrapper { max-width:700px; margin:60px auto; }
        .card {
            background:#fff; border-radius:16px;
            box-shadow:0 10px 30px rgba(0,0,0,.08);
            padding:35px; text-align:center;
        }
        .icon {
            width:70px; height:70px; border-radius:50%;
            background:#e9f8ef; color:#2fa84f;
            font-size:34px; line-height:70px;
            margin:0 auto 18px;
        }
        a {
            background:#4a6cf7; color:#fff;
            padding:14px 22px; border-radius:10px;
            text-decoration:none; display:inline-block;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="icon">✓</div>
        <h2>Magento CSV Ready</h2>
        <p>You can now import this file into Magento Admin → System → Import</p>
        <a href="<?= '../uploads/' . basename($outputFile) ?>" download>
            Download Magento CSV
        </a>
    </div>
</div>
</body>
</html>
