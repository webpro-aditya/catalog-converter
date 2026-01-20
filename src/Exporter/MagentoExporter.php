<?php

require_once __DIR__ . '/../Database.php';

class MagentoExporter
{
    private $db;
    private $jobId;

    public function __construct(int $jobId)
    {
        $this->db = new Database();
        $this->jobId = $jobId;
    }

    public function export(string $filePath)
    {
        $fp = fopen($filePath, 'w');

        // Base Magento REQUIRED headers (fixed)
        $baseHeaders = [
            'sku',
            'product_type',
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
            'url_key',
            'configurable_variation_labels',
            'configurable_variations',
            // image columns
            'base_image',
            'small_image',
            'thumbnail_image',
            'additional_images',
        ];

        // Load products for this job
        $products = $this->db->query(
            "SELECT * FROM universal_products WHERE job_id = ?",
            [$this->jobId]
        )->fetchAll(PDO::FETCH_ASSOC);

        // ------------------------------------------------------------------
        // FIRST PASS: collect dynamic attribute codes across ALL products
        //            and cache variants, attributes, images per product
        // ------------------------------------------------------------------
        $allAttributeCodes      = []; // code => label
        $productVariants        = []; // product_id => [variants...]
        $productVariantAttrs    = []; // product_id => [variant_id => [name => value]]
        $productVariantImages   = []; // product_id => [variant_id => [url, ...]]

        foreach ($products as $product) {
            $productId = (int)$product['id'];

            $variants = $this->getVariants($productId);
            if (!$variants) {
                continue;
            }

            $variantAttributes = $this->getVariantAttributes($productId);
            $variantImages = $this->getVariantImagesByProduct($productId);

            $productVariants[$productId]      = $variants;
            $productVariantAttrs[$productId]  = $variantAttributes;
            $productVariantImages[$productId] = $variantImages;

            if ($variantAttributes) {
                foreach ($variantAttributes as $attrs) {
                    foreach ($attrs as $name => $_) {
                        $code = $this->normalizeAttributeCode($name);
                        $allAttributeCodes[$code] = $name; // store original label
                    }
                }
            }
        }

        // Dynamic attribute columns (sorted for stable order)
        $dynamicAttributeCodes = array_keys($allAttributeCodes);
        sort($dynamicAttributeCodes);

        // Build final header row: base headers + dynamic attribute columns
        $header = array_merge($baseHeaders, $dynamicAttributeCodes);
        fputcsv($fp, $header);

        // ------------------------------------------------------------------
        // SECOND PASS: output rows product by product
        // ------------------------------------------------------------------
        foreach ($products as $product) {

            $productId = (int)$product['id'];

            if (empty($productVariants[$productId])) {
                continue;
            }

            $variants          = $productVariants[$productId];
            $variantAttributes = $productVariantAttrs[$productId] ?? [];
            $variantImages     = $productVariantImages[$productId] ?? [];

            if (empty($variantAttributes)) {
                foreach ($variants as $variant) {
                    $sku = $variant['sku'] ?: $product['parent_sku'];

                    $images = $variantImages[(int)$variant['id']] ?? [];
                    if (!$images) {
                        foreach ($variantImages as $imgList) {
                            if ($imgList) {
                                $images = $imgList;
                                break;
                            }
                        }
                    }

                    $baseImage      = $images[0] ?? '';
                    $smallImage     = $images[0] ?? '';
                    $thumbnailImage = $images[0] ?? '';
                    $additional     = '';

                    if (count($images) > 1) {
                        $additional = implode(',', array_slice($images, 1));
                    }

                    $row = [
                        $sku,
                        'simple',
                        'Default',
                        $product['name'],
                        $variant['regular_price'] ?? '',
                        $variant['sale_price'] ?? '',
                        'Catalog, Search',
                        'Enabled',
                        999,
                        1,
                        1,
                        $product['description'],
                        mb_substr(strip_tags($product['description']), 0, 255),
                        'base',
                        $this->uniqueUrlKey($sku),
                        '',
                        '',
                        $baseImage,
                        $smallImage,
                        $thumbnailImage,
                        $additional,
                    ];

                    foreach ($dynamicAttributeCodes as $code) {
                        $row[] = '';
                    }

                    fputcsv($fp, $row);
                }

                continue;
            }

            // -----------------------------------------
            // Collect attribute codes + labels for THIS product
            // -----------------------------------------
            $attributeLabels = []; // code => label

            foreach ($variantAttributes as $attrs) {
                foreach ($attrs as $name => $_) {
                    $code = $this->normalizeAttributeCode($name);
                    $attributeLabels[$code] = $name; // original label
                }
            }

            // Build configurable_variation_labels string
            // e.g. color=Color,size=Size
            $variationLabels = [];
            foreach ($attributeLabels as $code => $label) {
                $variationLabels[] = "{$code}={$label}";
            }

            // -----------------------------------------
            // Build configurable_variations string
            // AND map per-variant attribute values by code
            // -----------------------------------------
            $configurableVariations = [];
            $variantSuperValues     = []; // [variant_id][code] = value

            foreach ($variants as $variant) {
                $variantId = (int)$variant['id'];

                if (empty($variantAttributes[$variantId])) {
                    continue;
                }

                $parts = [];
                $parts[] = 'sku=' . $variant['sku'];

                foreach ($variantAttributes[$variantId] as $attrName => $attrValue) {
                    $code  = $this->normalizeAttributeCode($attrName);
                    $value = $this->normalizeAttributeValue($attrValue);
                    $parts[] = "{$code}={$value}";

                    $variantSuperValues[$variantId][$code] = $value;
                }

                $configurableVariations[] = implode(',', $parts);
            }

            if (!$configurableVariations) {
                continue;
            }

            // -----------------------------------------
            // CONFIGURABLE PRODUCT ROW
            // -----------------------------------------
            $configRow = [
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
                mb_substr(strip_tags($product['description']), 0, 255),
                'base',
                $this->uniqueUrlKey($product['parent_sku']),
                implode(',', $variationLabels),
                implode('|', $configurableVariations),
                // image columns for configurable (empty or set a main image if you want)
                '',
                '',
                '',
                '',
            ];

            // Add empty placeholders for all dynamic attributes on configurable row
            foreach ($dynamicAttributeCodes as $code) {
                $configRow[] = '';
            }

            fputcsv($fp, $configRow);

            // -----------------------------------------
            // SIMPLE VARIANTS
            // -----------------------------------------
            foreach ($variants as $variant) {
                $variantId = (int)$variant['id'];

                // determine images for this variant
                $images = $variantImages[$variantId] ?? [];
                $baseImage      = $images[0] ?? '';
                $smallImage     = $images[0] ?? '';
                $thumbnailImage = $images[0] ?? '';
                $additional     = '';

                if (count($images) > 1) {
                    // Magento expects comma-separated file names/paths
                    $additional = implode(',', array_slice($images, 1));
                }

                $row = [
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
                    $variant['weight'] ?? 1,
                    '',
                    '',
                    'base',
                    $this->uniqueUrlKey($variant['sku']),
                    '',
                    '',
                    $baseImage,
                    $smallImage,
                    $thumbnailImage,
                    $additional,
                ];

                // Append dynamic attribute values in the same order as header
                foreach ($dynamicAttributeCodes as $code) {
                    $value = $variantSuperValues[$variantId][$code] ?? '';
                    $row[] = $value;
                }

                fputcsv($fp, $row);
            }
        }

        fclose($fp);
    }

    // --------------------------------------------------

    private function getVariants(int $productId): array
    {
        return $this->db->query(
            "SELECT * FROM universal_variants WHERE product_id = ?",
            [$productId]
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getVariantAttributes(int $productId): array
    {
        $rows = $this->db->query(
            "SELECT variant_id, name, value
             FROM universal_attributes
             WHERE product_id = ? AND variant_id IS NOT NULL",
            [$productId]
        )->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[$r['variant_id']][$r['name']] = $r['value'];
        }

        return $out;
    }

    private function getVariantImagesByProduct(int $productId): array
    {
        $rows = $this->db->query(
            "SELECT variant_id, url
             FROM universal_images
             WHERE product_id = ? AND variant_id IS NOT NULL",
            [$productId]
        )->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[$r['variant_id']][] = $r['url'];
        }

        return $out;
    }

    private function normalizeAttributeCode(string $name): string
    {
        $code = strtolower($name);
        $code = preg_replace('/[^a-z0-9_]/', '_', $code);
        $code = preg_replace('/_+/', '_', $code);
        return trim($code, '_');
    }

    /**
     * IMPORTANT:
     * Magento expects attribute OPTION LABELS exactly as created in admin
     */
    private function normalizeAttributeValue(string $value): string
    {
        return trim($value);
    }

    private function uniqueUrlKey(string $sku): string
    {
        return strtolower(preg_replace('/[^a-z0-9\-]/', '-', $sku));
    }
}
