<?php

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Importer/ShopifyImporter.php';
include_once __DIR__ . '/../src/Helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Invalid request");

// save file
$fileName = time() . "_" . $_FILES['csv']['name'];
$filePath = "../uploads/$fileName";
move_uploaded_file($_FILES['csv']['tmp_name'], $filePath);

$db = new Database();

// create job
$db->query("
INSERT INTO import_jobs (platform_id, file_name, status, started_at)
VALUES (
  (SELECT id FROM platforms WHERE code=?),
  ?, 'mapping', NOW()
)
", [$_POST['source_platform'], $fileName]);

$jobId = $db->pdo->lastInsertId();

$importer = new ShopifyImporter($jobId);
$importer->import($filePath);

// read csv header
$fp = fopen($filePath, "r");
$headers = fgetcsv($fp);
fclose($fp);

// defaults from platform_mappings
$sourcePlatformCode = $_POST['source_platform'];
$targetPlatformCode = $_POST['target_platform'];


if ($sourcePlatformCode === $targetPlatformCode) {
    die("Source and target platforms cannot be the same.");
}


if ($sourcePlatformCode === 'shopify' && $targetPlatformCode === 'woocommerce') {
    $mappingTableColumns = $db->query("SHOW COLUMNS FROM platform_mappings")->fetchAll();
    $mappingTableFields = array_column($mappingTableColumns, 'Field');

    $hasTargetPlatformId = in_array('target_platform_id', $mappingTableFields, true);
    $hasTargetPlatformCode = in_array('target_platform_code', $mappingTableFields, true);

    if ($hasTargetPlatformId) {
        $rows = $db->query("
            SELECT source_column, universal_field
            FROM platform_mappings
            WHERE source_platform_id = (SELECT id FROM platforms WHERE code=?)
            AND target_platform_id = (SELECT id FROM platforms WHERE code=?)
        ", [$sourcePlatformCode, $targetPlatformCode])->fetchAll();
    } elseif ($hasTargetPlatformCode) {
        $rows = $db->query("
            SELECT source_column, universal_field
            FROM platform_mappings
            WHERE source_platform_id = (SELECT id FROM platforms WHERE code=?)
            AND target_platform_code = ?
        ", [$sourcePlatformCode, $targetPlatformCode])->fetchAll();
    } else {
        $rows = $db->query("
            SELECT source_column, universal_field
            FROM platform_mappings
            WHERE source_platform_id = (SELECT id FROM platforms WHERE code=?)
        ", [$sourcePlatformCode])->fetchAll();
    }

    $mappings = [];

    foreach ($rows as $r) {
        $mappings[normalize($r['source_column'])] = $r['universal_field'];
    }

    echo renderTemplate('../src/templates/shopify-wocommerce.php', [
        'jobId' => $jobId,
        'headers' => $headers,
        'mappings' => $mappings
    ]);
} else {
    die('No default mappings found for the selected platform combination.');
}
?>

