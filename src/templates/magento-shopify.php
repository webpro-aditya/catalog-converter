<!DOCTYPE html>
<html>

<head>
    <title>Magento → Shopify Column Mapping</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            margin: 0;
        }

        .wrapper {
            max-width: 1100px;
            margin: 40px auto;
            padding: 10px;
        }

        .card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
            padding: 25px 35px;
        }

        h2 {
            margin: 0 0 5px;
            text-align: center;
            font-size: 26px;
        }

        small {
            display: block;
            text-align: center;
            color: #777;
            margin-bottom: 20px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #eef2ff;
        }

        th, td {
            padding: 10px 12px;
            border-top: 1px solid #eee;
            font-size: 14px;
        }

        th {
            text-align: left;
            font-size: 13px;
            color: #333;
        }

        tr:hover {
            background: #fafbff;
        }

        select {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 13px;
        }

        button {
            margin-top: 20px;
            width: 100%;
            background: #4a6cf7;
            border: none;
            color: #fff;
            padding: 13px;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #274df5;
        }

        .footer-note {
            text-align: center;
            margin-top: 12px;
            color: #777;
        }

        .hint {
            font-size: 12px;
            color: #777;
        }
    </style>
</head>

<body>

<div class="wrapper">
    <div class="card">

        <h2>Step 2 — Map Columns</h2>
        <small>Magento → Shopify</small>

        <div class="subtitle">
            Map Magento CSV columns to Shopify product fields.
        </div>

        <form method="post" action="magento_to_shopify_step3_process.php">

            <input type="hidden" name="job_id" value="<?= $jobId ?>">

            <table>
                <thead>
                <tr>
                    <th>Magento Column</th>
                    <th>Shopify Field</th>
                </tr>
                </thead>
                <tbody>

                <?php foreach ($headers as $col):
                    $key = normalize($col);
                ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($col) ?></strong><br>
                            <span class="hint"><?= htmlspecialchars($key) ?></span>
                        </td>
                        <td>
                            <select name="map[<?= htmlspecialchars($col) ?>]">

                                <option value="">— Ignore —</option>

                                <!-- SHOPIFY PRODUCT -->
                                <option value="product.handle">Handle</option>
                                <option value="product.title">Title</option>
                                <option value="product.body_html">Body (HTML)</option>
                                <option value="product.vendor">Vendor</option>
                                <option value="product.product_type">Product Type</option>
                                <option value="product.tags">Tags</option>
                                <option value="product.status">Status</option>

                                <!-- SHOPIFY VARIANT -->
                                <option value="variant.sku">Variant SKU</option>
                                <option value="variant.price">Variant Price</option>
                                <option value="variant.compare_at_price">Compare At Price</option>
                                <option value="variant.weight">Variant Weight</option>
                                <option value="variant.weight_unit">Weight Unit</option>
                                <option value="variant.inventory_qty">Inventory Quantity</option>
                                <option value="variant.inventory_policy">Inventory Policy</option>
                                <option value="variant.requires_shipping">Requires Shipping</option>

                                <!-- IMAGES -->
                                <option value="image.src">Image Src</option>
                                <option value="image.alt">Image Alt Text</option>
                                <option value="variant.image">Variant Image</option>

                                <!-- ATTRIBUTES → SHOPIFY OPTIONS -->
                                <option value="option1.name">Option 1 Name</option>
                                <option value="option1.value">Option 1 Value</option>
                                <option value="option2.name">Option 2 Name</option>
                                <option value="option2.value">Option 2 Value</option>
                                <option value="option3.name">Option 3 Name</option>
                                <option value="option3.value">Option 3 Value</option>

                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
            </table>

            <button type="submit">
                Continue → Generate Shopify CSV
            </button>

            <div class="footer-note">
                Step 2 of 3 — Review and confirm mappings
            </div>

        </form>

    </div>
</div>

</body>
</html>
