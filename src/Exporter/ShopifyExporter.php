<?php

require_once __DIR__ . '/../Database.php';

class ShopifyExporter
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

        // Shopify CSV header (minimum viable)
        fputcsv($fp, [
            'Handle',
            'Title',
            'Body (HTML)',
            'Vendor',
            'Type',
            'Variant SKU',
            'Variant Price',
            'Variant Compare At Price',
            'Option1 Name',
            'Option1 Value',
            'Option2 Name',
            'Option2 Value',
            'Image Src'
        ]);

        $products = $this->db->query("
            SELECT * FROM universal_products
            WHERE job_id = ?
        ", [$this->jobId])->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {

            $variants = $this->getVariants($product['id']);
            $attributes = $this->getAttributes($product['id']);
            $images = $this->getImages($product['id']);

            if (!$variants) continue;

            foreach ($variants as $index => $variant) {

                $row = [
                    $product['parent_sku'],               // Handle
                    $product['name'],                     // Title
                    $product['description'],              // Body
                    $product['brand'],                    // Vendor
                    $product['product_type'] ?? '',       // Type
                    $variant['sku'],                      // SKU
                    $variant['price'],                    // Price
                    $variant['regular_price'],            // Compare at
                ];

                // Attributes → Option columns
                $attrPairs = $attributes[$variant['id']] ?? [];

                for ($i = 0; $i < 2; $i++) {
                    if (isset($attrPairs[$i])) {
                        $row[] = $attrPairs[$i]['name'];
                        $row[] = $attrPairs[$i]['value'];
                    } else {
                        $row[] = '';
                        $row[] = '';
                    }
                }

                // Image (first variant image or product image)
                $row[] = $images[$variant['id']] ?? $images[0] ?? '';

                fputcsv($fp, $row);
            }
        }

        fclose($fp);
    }

    // -----------------------
    // Helpers
    // -----------------------

    private function getVariants($productId)
    {
        return $this->db->query("
            SELECT * FROM universal_variants
            WHERE product_id = ?
        ", [$productId])->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAttributes($productId)
    {
        $rows = $this->db->query("
            SELECT * FROM universal_attributes
            WHERE product_id = ?
            ORDER BY variant_id
        ", [$productId])->fetchAll(PDO::FETCH_ASSOC);

        $out = [];

        foreach ($rows as $row) {
            if ($row['variant_id']) {
                $out[$row['variant_id']][] = [
                    'name'  => $row['name'],
                    'value' => $row['value']
                ];
            }
        }

        return $out;
    }

    private function getImages($productId)
    {
        $rows = $this->db->query("
            SELECT * FROM universal_images
            WHERE product_id = ?
            ORDER BY sort_order ASC
        ", [$productId])->fetchAll(PDO::FETCH_ASSOC);

        $out = [];

        foreach ($rows as $row) {
            if ($row['variant_id']) {
                $out[$row['variant_id']] = $row['url'];
            } else {
                $out[0] = $row['url'];
            }
        }

        return $out;
    }
}
