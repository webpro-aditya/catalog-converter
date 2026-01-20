<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Importer/ShopifyImporter.php';
require_once __DIR__ . '/../src/Normalizer/UniversalBuilder.php';
require_once __DIR__ . '/../src/Exporter/MagentoExporter.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

$db = new Database();

$jobId    = (int) ($_POST['job_id'] ?? 0);
$mappings = $_POST['map'] ?? [];

if (!$jobId || empty($mappings)) {
    die('Missing job or mappings');
}

/**
 * 1️⃣ Clear previous mappings
 */
$db->query(
    "DELETE FROM job_mappings WHERE job_id = ?",
    [$jobId]
);

/**
 * 2️⃣ Save mappings
 */
foreach ($mappings as $sourceColumn => $universalField) {

    if ($universalField === '') {
        continue;
    }

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
 * 4️⃣ Import Shopify CSV
 */
$importer = new ShopifyImporter($jobId);
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
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6fb;
            margin: 0;
        }

        .wrapper {
            max-width: 700px;
            margin: 60px auto;
            padding: 10px;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
            padding: 35px;
            text-align: center;
        }

        .icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #e9f8ef;
            color: #2fa84f;
            font-size: 34px;
            line-height: 70px;
            margin: 0 auto 18px auto;
        }

        h2 {
            margin: 0;
            font-size: 26px;
            color: #222;
        }

        .subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 8px;
            margin-bottom: 25px;
        }

        a.download-btn {
            background: #4a6cf7;
            color: #fff;
            padding: 14px 22px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 15px;
            display: inline-block;
            transition: .2s ease;
        }

        a.download-btn:hover {
            background: #274df5;
        }

        .footer-note {
            color: #777;
            margin-top: 18px;
            font-size: 13px;
        }

        .steps {
            text-align: center;
            font-size: 13px;
            color: #888;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>

<div class="wrapper">

        <div class="card">

            <div class="icon">✓</div>

            <div class="steps">Step 3 of 3 — Completed</div>

            <h2>Your export file is ready</h2>

            <div class="subtitle">
                You can now download the converted CSV file and import it into the target platform.
            </div>

            <a href="<?= '../uploads/' . basename($outputFile) ?>" download class="download-btn">
                Download Export File
            </a>

            <div class="footer-note">
                Need to convert another file? <a href="step1_upload.php">Start a new import</a>
            </div>

        </div>

    </div>

</body>
</html>
