<?php

require_once __DIR__ . '/../Database.php';

class MagentoExporter
{
    private $db;
    private $jobId;

    public function __construct($jobId)
    {
        $this->db = new Database();
        $this->jobId = $jobId;
    }

    public function export(string $filePath)
    {
        $fp = fopen($filePath, 'w');

        // Magento headers (minimal + correct)
        fputcsv($fp, [
            'sku',
            'type',
            'attribute_set_code',
            'name',
            'price',
            'special_price',
            'visibility',
            'status',
            'qty',
            'is_in_stock',
            'weight',
            'description',
            'short_description',
            'product_websites',
            'configurable_variations'
        ]);

        $products = $this->db->query(
            "SELECT * FROM universal_products WHERE job_id = ?",
            [$this->jobId]
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {

            $variants = $this->getVariants($product['id']);
            $attributes = $this->getConfigurableAttributes($product['id']);

            // ---------- CONFIGURABLE PRODUCT ----------
            fputcsv($fp, [
                $product['parent_sku'],
                'configurable',
                'Default',
                $product['name'],
                '',
                '',
                'Catalog, Search',
                'Enabled',
                '',
                '',
                '',
                $product['description'],
                substr(strip_tags($product['description']), 0, 255),
                'base',
                ''
            ]);

            // ---------- SIMPLE VARIANTS ----------
            foreach ($variants as $variant) {

                $variationString = $this->buildVariationString(
                    $attributes,
                    $variant['id']
                );

                fputcsv($fp, [
                    $variant['sku'],
                    'simple',
                    'Default',
                    $product['name'],
                    $variant['regular_price'],
                    $variant['sale_price'],
                    'Not Visible Individually',
                    'Enabled',
                    999,
                    1,
                    $variant['weight'] ?? '',
                    '',
                    '',
                    'base',
                    $variationString
                ]);
            }
        }

        fclose($fp);
    }

    private function getVariants($productId): array
    {
        return $this->db->query(
            "SELECT * FROM universal_variants WHERE product_id = ?",
            [$productId]
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getConfigurableAttributes($productId): array
    {
        $rows = $this->db->query(
            "SELECT DISTINCT name FROM universal_attributes
             WHERE product_id = ? AND variant_id IS NOT NULL",
            [$productId]
        )->fetchAll(PDO::FETCH_COLUMN);

        return $rows;
    }

    private function buildVariationString(array $attributeNames, int $variantId): string
    {
        $rows = $this->db->query(
            "SELECT name, value
             FROM universal_attributes
             WHERE variant_id = ?",
            [$variantId]
        )->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r) {
            $map[$r['name']] = $r['value'];
        }

        $pairs = [];
        foreach ($attributeNames as $attr) {
            if (isset($map[$attr])) {
                $pairs[] = $attr . '=' . $map[$attr];
            }
        }

        return implode(',', $pairs);
    }
}
