<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Logger.php';
require_once __DIR__ . '/../../src/Common/Truncate.php';

class MagentoImporter
{
    private $db;
    private $common;
    private $jobId;

    // runtime caches
    private array $products = [];           // parent_sku => product_id
    private array $variantAttributeMap = []; // variant_sku => [attr => value]

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
        $headers = array_map('trim', fgetcsv($fp));

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

                $productId = $this->db->pdo->lastInsertId();
                $this->products[$sku] = $productId;

                $this->parseConfigurableVariations($sku, $data);
                $this->saveImages($productId, null, $data);
            }

            // ------------------------------------
            // SIMPLE VARIANT
            // ------------------------------------
            if ($type === 'simple') {

                $sku = trim($data['sku']);
                if (!$sku) continue;

                $parentSku = $this->findParentSkuByVariant($sku);
                if (!$parentSku || !isset($this->products[$parentSku])) {
                    continue;
                }

                $variantId = $this->saveVariant(
                    $this->products[$parentSku],
                    $data
                );

                $this->saveVariantAttributesFromCache(
                    $this->products[$parentSku],
                    $variantId,
                    $sku
                );

                $this->saveImages(
                    $this->products[$parentSku],
                    $variantId,
                    $data
                );
            }
        }

        fclose($fp);

        Logger::log("Magento import completed for job {$this->jobId}");
    }

    // ------------------------------------------------
    // CORE FIXES
    // ------------------------------------------------

    /**
     * Parse configurable_variations from configurable row
     */
    private function parseConfigurableVariations(string $parentSku, array $data): void
    {
        if (empty($data['configurable_variations'])) return;

        $rows = explode('|', $data['configurable_variations']);

        foreach ($rows as $row) {
            $pairs = explode(',', $row);
            $sku = null;
            $attrs = [];

            foreach ($pairs as $pair) {
                [$k, $v] = array_map('trim', explode('=', $pair, 2));

                if ($k === 'sku') {
                    $sku = $v;
                } else {
                    $attrs[ucfirst($k)] = $v;
                }
            }

            if ($sku && $attrs) {
                $this->variantAttributeMap[$sku] = [
                    'parent' => $parentSku,
                    'attrs'  => $attrs
                ];
            }
        }
    }

    private function findParentSkuByVariant(string $variantSku): ?string
    {
        return $this->variantAttributeMap[$variantSku]['parent'] ?? null;
    }

    private function saveVariantAttributesFromCache(
        int $productId,
        int $variantId,
        string $sku
    ): void {
        if (empty($this->variantAttributeMap[$sku]['attrs'])) return;

        foreach ($this->variantAttributeMap[$sku]['attrs'] as $name => $value) {
            $this->db->query("
                INSERT INTO source_attributes
                (product_id, variant_id, name, value)
                VALUES (?,?,?,?)
            ", [
                $productId,
                $variantId,
                $name,
                $value
            ]);
        }
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

    private function saveImages(
        int $productId,
        ?int $variantId,
        array $data
    ): void {
        $columns = [
            'base_image',
            'small_image',
            'thumbnail_image',
            'additional_images'
        ];

        $position = 0;

        foreach ($columns as $col) {
            if (empty($data[$col])) continue;

            foreach (explode(',', $data[$col]) as $url) {
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
