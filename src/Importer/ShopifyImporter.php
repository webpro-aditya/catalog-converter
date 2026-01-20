<?php

require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Logger.php';
require_once __DIR__ . '/../../src/Common/Truncate.php';

class ShopifyImporter
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

        $fp = fopen($filePath, "r");
        $headers = fgetcsv($fp);

        $products = [];
        $lastHandle = null;

        while ($row = fgetcsv($fp)) {

            if (!$row) {
                continue;
            }

            $data = array_combine($headers, $row);
            $rawHandle = trim((string)($data['Handle'] ?? ''));
            if ($rawHandle !== '') {
                $lastHandle = $rawHandle;
            }
            $handle = $rawHandle !== '' ? $rawHandle : (string)$lastHandle;

            if ($handle === '') {
                continue;
            }

            /** -----------------------------
             *  1️⃣ Create product (once)
             * ---------------------------- */
            if (!isset($products[$handle])) {

                $platformProductId = $data['ID'] ?? $handle;

                $this->db->query("
                INSERT INTO source_products
                (job_id, platform_product_id, handle, title, description, mrp, sale_price, vendor, product_type, status)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ", [
                    $this->jobId,
                    $platformProductId,
                    $handle,
                    $data['Title'] ?? '',
                    $data['Body (HTML)'] ?? '',
                    $data['Variant Compare At Price'] ?? '',
                    $data['Variant Price'] ?? '',
                    $data['Vendor'] ?? '',
                    $data['Type'] ?? '',
                    $data['Status'] ?? ''
                ]);

                $products[$handle] = [
                    'id' => $this->db->pdo->lastInsertId(),
                    'attr_names' => []
                ];

                // Cache attribute names (Option1 Name, Option2 Name, ...)
                for ($i = 1; $i <= 10; $i++) {
                    $name = trim($data["Option{$i} Name"] ?? '');
                    if ($name !== '') {
                        $products[$handle]['attr_names'][$i] = $name;
                    }
                }
            }

            $productId = $products[$handle]['id'];
            $attrNames = $products[$handle]['attr_names'];

            /** ---------------------------------------
             *  2️⃣ Collect attribute values for row
             * --------------------------------------- */
            $attributes = [];

            foreach ($attrNames as $i => $attrName) {
                $value = trim((string)($data["Option{$i} Value"] ?? ($data["Option{$i}  Value"] ?? '')));

                if ($value !== '' && strtolower($value) !== 'default title') {
                    $attributes[] = [
                        'name'  => $attrName,
                        'value' => $value
                    ];
                }
            }

            /** ---------------------------------------
             *  3️⃣ Skip ghost / deleted Shopify rows
             * --------------------------------------- */
            $isGhostVariant =
                empty($attributes) &&
                empty(trim($data['Variant SKU'] ?? '')) &&
                empty(trim($data['Variant Price'] ?? ''));

            if ($isGhostVariant) {
                if (!empty($data['Image Src']) || !empty($data['Variant Image'])) {
                    $this->saveImages($productId, null, $data);
                }
                continue;
            }

            /** ---------------------------------------
             *  4️⃣ Create variant
             * --------------------------------------- */
            $variantId = $this->saveVariant($productId, $data);

            /** ---------------------------------------
             *  5️⃣ Save attributes for this variant
             * --------------------------------------- */
            foreach ($attributes as $attr) {
                $this->saveAttributes($productId, $variantId, $attr);
            }

            /** ---------------------------------------
             *  6️⃣ Images & inventory
             * --------------------------------------- */
            $this->saveImages($productId, $variantId, $data);
            $this->saveInventory($variantId, $data);
        }

        fclose($fp);

        Logger::log("Shopify import complete for job {$this->jobId}");
    }

    private function saveVariant($productId, $data)
    {
        $platformVariantId =
            $data['Variant ID']
            ?? $data['Variant SKU']
            ?? uniqid('var_');

        $this->db->query("
            INSERT INTO source_variants
            (job_id, product_id, platform_variant_id, sku, price, compare_at_price, barcode, weight, weight_unit, requires_shipping)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ", [
            $this->jobId,
            $productId,
            $platformVariantId,
            $data['Variant SKU'] ?? '',
            $data['Variant Price'] ?? 0,
            $data['Variant Compare At Price'] ?? null,
            $data['Variant Barcode'] ?? '',
            $data['Variant Grams'] ?? 0,
            $data['Variant Weight Unit'] ?? 'g',
            $data['Variant Requires Shipping'] ?? 'TRUE'
        ]);

        return $this->db->pdo->lastInsertId();
    }

    private function saveAttributes($productId, $variantId, array $attr)
    {
        $this->db->query("
            INSERT INTO source_attributes
            (product_id, variant_id, name, value)
            VALUES (?,?,?,?)
        ", [
            $productId,
            $variantId,
            $attr['name'],
            $attr['value']
        ]);
    }

    private function saveImages($productId, $variantId, $data)
    {
        if (!empty($data['Image Src'])) {
            $this->db->query("
                INSERT INTO source_images
                (product_id, variant_id, type, url, alt_text)
                VALUES (?,?,?,?,?)
            ", [
                $productId,
                $variantId,
                'image_src',
                $data['Image Src'],
                $data['Image Alt Text'] ?? null
            ]);
        }

        if (!empty($data['Variant Image'])) {
            $this->db->query("
                INSERT INTO source_images
                (product_id, variant_id, type, url, alt_text)
                VALUES (?,?,?,?,?)
            ", [
                $productId,
                $variantId,
                'variant_image',
                $data['Variant Image'],
                $data['Image Alt Text'] ?? null
            ]);
        }
    }

    private function saveInventory($variantId, $data)
    {
        $this->db->query("
            INSERT INTO source_inventory
            (variant_id, quantity, manage_stock, backorders_allowed)
            VALUES (?,?,?,?)
        ", [
            $variantId,
            $data['Variant Inventory Qty'] ?? 0,
            !empty($data['Variant Inventory Tracker']) ? 1 : 0,
            ($data['Variant Inventory Policy'] ?? '') === 'continue' ? 1 : 0
        ]);
    }
}
