<?php

require_once __DIR__ . '/../Database.php';

class WooExporter
{
    private $db;
    private $jobId;

    public function __construct($jobId)
    {
        $this->db = new Database();
        $this->jobId = $jobId;
    }

    public function export($filePath)
    {
        $fp = fopen($filePath, 'w');

        /**
         * 1️⃣ Fetch all attribute names used in this job
         */
        $attributeNames = $this->db->query("
            SELECT DISTINCT ua.name
            FROM universal_attributes ua
            JOIN universal_products up ON up.id = ua.product_id
            WHERE up.job_id = ?
            ORDER BY ua.name
        ", [$this->jobId])->fetchAll(PDO::FETCH_COLUMN);

        /**
         * 2️⃣ Build CSV header dynamically
         */
        $header = [
            'Type',
            'SKU',
            'Parent',
            'Name',
            'Regular price',
            'Sale price',
            'Images'
        ];

        foreach ($attributeNames as $i => $attrName) {
            $n = $i + 1;
            $header[] = "Attribute {$n} name";
            $header[] = "Attribute {$n} value(s)";
            $header[] = "Attribute {$n} visible";
            $header[] = "Attribute {$n} global";
        }

        fputcsv($fp, $header);

        /**
         * 3️⃣ Fetch products
         */
        $products = $this->db->query("
            SELECT * FROM universal_products
            WHERE job_id = ?
        ", [$this->jobId])->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {
            $this->writeProductRow($fp, $product, $attributeNames);
            $this->writeVariants($fp, $product, $attributeNames);
        }

        fclose($fp);
    }

    /**
     * -------------------------------------------------
     * PRODUCT ROW
     * -------------------------------------------------
     */
    private function writeProductRow($fp, $product, $attributeNames)
    {
        $productAttrs = $this->getProductAttributes($product['id']);
        $image = $this->getProductImage($product['id']);

        $row = [
            empty($productAttrs) ? 'simple' : 'variable',
            $product['parent_sku'],
            '',
            $product['name'],
            $product['mrp'],
            $product['sale_price'],
            $image
        ];

        foreach ($attributeNames as $attrName) {
            $values = $productAttrs[$attrName] ?? [];
            $row[] = $attrName;
            $row[] = implode(',', $values);
            $row[] = 1;
            $row[] = 1;
        }

        fputcsv($fp, $row);
    }

    /**
     * -------------------------------------------------
     * VARIANT ROWS
     * -------------------------------------------------
     */
    private function writeVariants($fp, $product, $attributeNames)
{
    $variants = $this->db->query("
        SELECT * FROM universal_variants
        WHERE product_id = ?
    ", [$product['id']])->fetchAll(PDO::FETCH_ASSOC);

    if (!$variants) return;

    $variantAttrs  = $this->getVariantAttributes($product['id']);
    $variantImages = $this->getVariantImages($product['id']);

    foreach ($variants as $v) {

        $row = [
            'variation',
            $v['sku'],
            $product['parent_sku'],
            '',
            $v['regular_price'],
            $v['sale_price'],
            $variantImages[$v['id']] ?? ''
        ];

        foreach ($attributeNames as $attrName) {

            // Attribute X name
            $row[] = $attrName;

            // Attribute X value
            $row[] = $variantAttrs[$v['id']][$attrName] ?? '';

            // Attribute X visible
            $row[] = 1;

            // Attribute X global
            $row[] = 1;
        }

        fputcsv($fp, $row);
    }
}



    /**
     * -------------------------------------------------
     * HELPERS
     * -------------------------------------------------
     */
    private function getProductAttributes($productId)
    {
        $rows = $this->db->query("
            SELECT name, value
            FROM universal_attributes
            WHERE product_id = ? AND variant_id IS NULL
        ", [$productId])->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[$r['name']][] = $r['value'];
        }

        return $out;
    }

    private function getVariantAttributes($productId)
    {
        $rows = $this->db->query("
            SELECT variant_id, name, value
            FROM universal_attributes
            WHERE product_id = ? AND variant_id IS NOT NULL
        ", [$productId])->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[$r['variant_id']][$r['name']] = $r['value'];
        }

        return $out;
    }

    private function getProductImage($productId)
    {
        return $this->db->query("
            SELECT url
            FROM universal_images
            WHERE product_id = ? AND variant_id IS NULL
            ORDER BY sort_order ASC
            LIMIT 1
        ", [$productId])->fetchColumn() ?: '';
    }

    private function getVariantImages($productId)
    {
        $rows = $this->db->query("
            SELECT variant_id, url
            FROM universal_images
            WHERE product_id = ? AND variant_id IS NOT NULL
        ", [$productId])->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[$r['variant_id']] = $r['url'];
        }

        return $out;
    }
}
