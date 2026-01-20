<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Logger.php';
require_once __DIR__ . '/../../src/Common/Truncate.php';

class WooImporter
{
    private $common;
    private $db;
    private $jobId;

    public function __construct($jobId)
    {
        $this->db = new Database();
        $this->common = new Truncate();
        $this->jobId = $jobId;
    }

    public function import($filePath)
    {
        $tablesToClear = [
            'source_attributes',
            'source_inventory',
            'source_products',
            'source_variants',
            'source_images',
            'universal_attributes',
            'universal_images',
            'universal_products',
            'universal_variants'
        ];

        if (!$this->common->truncateTables($tablesToClear)) {
            Logger::log("Failed to clear tables before import.");
            die("An error occurred while clearing tables.");
        }

        $fp = fopen($filePath, 'r');
        $headers = fgetcsv($fp);

        $products = []; // parent_sku => product_id

        while ($row = fgetcsv($fp)) {

            if (!$row) continue;

            $data = array_combine($headers, $row);
            $type = strtolower(trim($data['Type'] ?? ''));

            if (!$type) continue;

            // -----------------------------
            // PRODUCT ROW
            // -----------------------------
            if ($type === 'variable' || $type === 'simple') {

                $parentSku = trim($data['SKU']);
                if (!$parentSku) continue;

                if (!isset($products[$parentSku])) {

                    $this->db->query("
                        INSERT INTO source_products
                        (job_id, platform_product_id, handle, title, description, mrp, sale_price, status)
                        VALUES (?,?,?,?,?,?,?,?)
                    ", [
                        $this->jobId,
                        $parentSku,
                        $parentSku,
                        $data['Name'] ?? '',
                        $data['Description'] ?? '',
                        $data['Regular price'] ?? null,
                        $data['Sale price'] ?? null,
                        'publish'
                    ]);

                    $products[$parentSku] = $this->db->pdo->lastInsertId();
                }

                // product-level attributes
                $this->saveAttributes(
                    $products[$parentSku],
                    null,
                    $data
                );

                $this->saveImages(
                    $products[$parentSku],
                    null,
                    $data
                );
            }

            // -----------------------------
            // VARIATION ROW
            // -----------------------------
            if ($type === 'variation') {

                $parentSku = trim($data['Parent']);
                $sku       = trim($data['SKU']);

                if (!$parentSku || !$sku) continue;
                if (!isset($products[$parentSku])) continue;

                // skip empty/deleted variations
                if (
                    empty($data['Regular price']) &&
                    empty($data['Sale price']) &&
                    $this->isEmptyAttributes($data)
                ) {
                    continue;
                }

                $variantId = $this->saveVariant(
                    $products[$parentSku],
                    $data
                );

                $this->saveAttributes(
                    $products[$parentSku],
                    $variantId,
                    $data
                );

                $this->saveImages(
                    $products[$parentSku],
                    $variantId,
                    $data
                );
            }
        }

        fclose($fp);

        Logger::log("WooCommerce import completed for job {$this->jobId}");
    }

    // ------------------------------------
    // Helpers
    // ------------------------------------

    private function saveVariant($productId, $data)
    {
        $this->db->query("
            INSERT INTO source_variants
            (job_id, product_id, platform_variant_id, sku, price, compare_at_price)
            VALUES (?,?,?,?,?,?)
        ", [
            $this->jobId,
            $productId,
            $data['SKU'],
            $data['SKU'],
            $data['Sale price'] ?: $data['Regular price'],
            $data['Regular price']
        ]);

        return $this->db->pdo->lastInsertId();
    }

    private function saveAttributes($productId, $variantId, $data)
    {
        for ($i = 1; $i <= 10; $i++) {

            $name  = trim($data["Attribute {$i} name"] ?? '');
            $value = trim($data["Attribute {$i} value(s)"] ?? '');

            if (!$name || !$value) continue;

            $values = explode('|', $value);

            foreach ($values as $val) {
                $this->db->query("
                    INSERT INTO source_attributes
                    (product_id, variant_id, name, value)
                    VALUES (?,?,?,?)
                ", [
                    $productId,
                    $variantId,
                    $name,
                    trim($val)
                ]);
            }
        }
    }

    private function isEmptyAttributes($data): bool
    {
        for ($i = 1; $i <= 10; $i++) {
            if (!empty($data["Attribute {$i} value(s)"] ?? '')) {
                return false;
            }
        }
        return true;
    }

    private function saveImages($productId, $variantId, $data)
    {
        // WooCommerce uses "Images" column (comma-separated)
        if (empty($data['Images'])) {
            return;
        }

        $urls = array_map('trim', explode(',', $data['Images']));
        $position = 0;

        foreach ($urls as $url) {
            if ($url === '') continue;

            $this->db->query("
            INSERT INTO source_images
            (product_id, variant_id, type, url, alt_text, position)
            VALUES (?,?,?,?,?,?)
        ", [
                $productId,
                $variantId,
                $variantId ? 'variant_image' : 'image_src',
                $url,
                null,
                $position++
            ]);
        }
    }
}
