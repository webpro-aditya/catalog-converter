<?php

require_once __DIR__ . '/../Database.php';

class UniversalBuilder
{

    private $db;
    private $jobId;

    public function __construct($jobId)
    {
        $this->db = new Database();
        $this->jobId = $jobId;
    }

    public function build()
    {
        $products = $this->db->query("
            SELECT * FROM source_products WHERE job_id = ?
        ", [$this->jobId])->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {

            // 1️⃣ create universal product
            $this->db->query("
                INSERT INTO universal_products
                (job_id, parent_sku, slug, name, description, mrp, sale_price, brand)
                VALUES (?,?,?,?,?,?,?,?)
            ", [
                $this->jobId,
                $product['handle'],
                $product['handle'],
                $product['title'],
                $product['description'],
                $product['mrp'],
                $product['sale_price'],
                $product['vendor']
            ]);

            $universalProductId = $this->db->pdo->lastInsertId();

            // 2️⃣ convert variants
            $this->convertVariants($universalProductId, $product['id']);

            // 3️⃣ convert attributes (🔥 THIS WAS MISSING)
            $this->convertAttributes($universalProductId, $product['id']);

            // 4️⃣ convert images
            $this->convertImages($universalProductId, $product['id']);
        }
    }

    private function convertVariants($universalProductId, $sourceProductId)
    {
        $variants = $this->db->query("
            SELECT * FROM source_variants WHERE product_id = ?
        ", [$sourceProductId])->fetchAll(PDO::FETCH_ASSOC);

        foreach ($variants as $v) {

            $regular = ($v['compare_at_price'] > $v['price'])
                ? $v['compare_at_price']
                : $v['price'];

            $sale = ($v['compare_at_price'] > $v['price'])
                ? $v['price']
                : null;

            $this->db->query("
                INSERT INTO universal_variants
                (product_id, sku, price, regular_price, sale_price)
                VALUES (?,?,?,?,?)
            ", [
                $universalProductId,
                $v['sku'],
                $v['price'],
                $regular,
                $sale
            ]);
        }
    }

    /**
     * 🔥 Converts source_attributes → universal_attributes
     */
    private function convertAttributes($universalProductId, $sourceProductId)
    {
        // fetch source attributes with source variant ids
        $rows = $this->db->query("
            SELECT sa.variant_id, sa.name, sa.value
            FROM source_attributes sa
            WHERE sa.product_id = ?
        ", [$sourceProductId])->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return;
        }

        // map source_variant_id → universal_variant_id
        $variantMap = $this->db->query("
            SELECT sv.id AS source_variant_id, uv.id AS universal_variant_id
            FROM source_variants sv
            JOIN universal_variants uv 
              ON uv.sku = sv.sku
            WHERE sv.product_id = ?
        ", [$sourceProductId])->fetchAll(PDO::FETCH_KEY_PAIR);

        $productLevel = [];

        foreach ($rows as $row) {

            $universalVariantId = null;

            if (!empty($row['variant_id']) && isset($variantMap[$row['variant_id']])) {
                $universalVariantId = $variantMap[$row['variant_id']];
            }

            // insert variant-level attribute
            $this->db->query("
                INSERT INTO universal_attributes
                (product_id, variant_id, name, value)
                VALUES (?,?,?,?)
            ", [
                $universalProductId,
                $universalVariantId,
                $row['name'],
                $row['value']
            ]);

            // collect for product-level attributes
            $productLevel[$row['name']][] = $row['value'];
        }

        // insert product-level attributes (variant_id = NULL)
        foreach ($productLevel as $name => $values) {
            foreach (array_unique($values) as $val) {
                $this->db->query("
                    INSERT INTO universal_attributes
                    (product_id, variant_id, name, value)
                    VALUES (?,?,?,?)
                ", [
                    $universalProductId,
                    null,
                    $name,
                    $val
                ]);
            }
        }
    }

    private function convertImages($universalProductId, $sourceProductId)
    {
        // 1️⃣ Build source_variant_id → universal_variant_id map
        $variantMap = $this->db->query("
        SELECT sv.id AS source_variant_id, uv.id AS universal_variant_id
        FROM source_variants sv
        JOIN universal_variants uv ON uv.sku = sv.sku
        WHERE sv.product_id = ?
    ", [$sourceProductId])->fetchAll(PDO::FETCH_KEY_PAIR);

        // 2️⃣ Fetch all source images for this product
        $images = $this->db->query("
        SELECT * FROM source_images
        WHERE product_id = ?
        ORDER BY position ASC, id ASC
    ", [$sourceProductId])->fetchAll(PDO::FETCH_ASSOC);

        if (!$images) {
            return;
        }

        foreach ($images as $img) {

            $universalVariantId = null;

            // remap variant_id if present
            if (!empty($img['variant_id']) && isset($variantMap[$img['variant_id']])) {
                $universalVariantId = $variantMap[$img['variant_id']];
            }

            $this->db->query("
            INSERT INTO universal_images
            (product_id, variant_id, type, url, alt_text, sort_order)
            VALUES (?,?,?,?,?,?)
        ", [
                $universalProductId,
                $universalVariantId,
                $img['type'] ?? 'image',
                $img['url'],
                $img['alt_text'],
                $img['position'] ?? 0
            ]);
        }
    }
}
