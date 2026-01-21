<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Logger.php';
require_once __DIR__ . '/../../src/Common/Truncate.php';

class MagentoImporter
{
    private $db;
    private $common;
    private $jobId;

    public function __construct(int $jobId)
    {
        $this->db = new Database();
        $this->common = new Truncate();
        $this->jobId = $jobId;
    }

    public function import(string $filePath)
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
            Logger::log("Failed to truncate tables for Magento import");
            die("Table truncate failed");
        }

        $fp = fopen($filePath, 'r');
        $headers = fgetcsv($fp);
        $headers = array_map('trim', $headers);

        $products = []; // sku => product_id

        while ($row = fgetcsv($fp)) {

            if (!$row) continue;

            $data = array_combine($headers, $row);
            $type = strtolower(trim($data['product_type'] ?? ''));

            if (!$type) continue;

            // ------------------------------------
            // CONFIGURABLE PRODUCT
            // ------------------------------------
            if ($type === 'configurable') {

                $sku = trim($data['sku']);
                if (!$sku) continue;

                if (!isset($products[$sku])) {

                    $this->db->query("
                        INSERT INTO source_products
                        (job_id, platform_product_id, handle, title, description, status)
                        VALUES (?,?,?,?,?,?)
                    ", [
                        $this->jobId,
                        $sku,
                        $sku,
                        $data['name'] ?? '',
                        $data['description'] ?? '',
                        'publish'
                    ]);

                    $products[$sku] = $this->db->pdo->lastInsertId();
                }

                // product images
                $this->saveImages(
                    $products[$sku],
                    null,
                    $data
                );
            }

            // ------------------------------------
            // SIMPLE VARIANT
            // ------------------------------------
            if ($type === 'simple') {

                $sku = trim($data['sku']);
                if (!$sku) continue;

                // parent sku comes from configurable_variations
                $parentSku = $this->extractParentSku($data);
                if (!$parentSku || !isset($products[$parentSku])) {
                    continue;
                }

                $variantId = $this->saveVariant(
                    $products[$parentSku],
                    $data
                );

                $this->saveVariantAttributes(
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

        Logger::log("Magento import completed for job {$this->jobId}");
    }

    // ------------------------------------------------
    // Helpers
    // ------------------------------------------------

    private function saveVariant(int $productId, array $data): int
    {
        $this->db->query("
            INSERT INTO source_variants
            (job_id, product_id, platform_variant_id, sku, price)
            VALUES (?,?,?,?,?)
        ", [
            $this->jobId,
            $productId,
            $data['sku'],
            $data['sku'],
            $data['price'] ?? 0
        ]);

        return $this->db->pdo->lastInsertId();
    }

    /**
     * Extract attributes from configurable_variations
     * Example:
     * sku=MH01-XS-Orange,size=XS,color=Orange
     */
    private function saveVariantAttributes(
        int $productId,
        int $variantId,
        array $data
    ) {
        if (empty($data['configurable_variations'])) return;

        $chunks = explode('|', $data['configurable_variations']);

        foreach ($chunks as $chunk) {

            if (!str_contains($chunk, 'sku=' . $data['sku'])) {
                continue;
            }

            $pairs = explode(',', $chunk);

            foreach ($pairs as $pair) {

                if (str_starts_with($pair, 'sku=')) continue;

                [$name, $value] = array_map('trim', explode('=', $pair, 2));

                if (!$name || !$value) continue;

                $this->db->query("
                    INSERT INTO source_attributes
                    (product_id, variant_id, name, value)
                    VALUES (?,?,?,?)
                ", [
                    $productId,
                    $variantId,
                    ucfirst($name),
                    $value
                ]);
            }
        }
    }

    private function extractParentSku(array $data): ?string
    {
        if (empty($data['configurable_variations'])) {
            return null;
        }

        // Magento links simples implicitly by configurable row order,
        // we already created the configurable earlier
        // So parent sku is the LAST configurable product inserted
        return array_key_last($GLOBALS['products'] ?? []) ?: null;
    }

    private function saveImages(
        int $productId,
        ?int $variantId,
        array $data
    ) {
        $columns = [
            'base_image',
            'small_image',
            'thumbnail_image',
            'additional_images'
        ];

        $position = 0;

        foreach ($columns as $col) {

            if (empty($data[$col])) continue;

            $urls = explode(',', $data[$col]);

            foreach ($urls as $url) {
                $url = trim($url);
                if ($url === '') continue;

                $this->db->query("
                    INSERT INTO source_images
                    (product_id, variant_id, type, url, position)
                    VALUES (?,?,?,?,?)
                ", [
                    $productId,
                    $variantId,
                    $variantId ? 'variant_image' : 'image_src',
                    $url,
                    $position++
                ]);
            }
        }
    }
}
