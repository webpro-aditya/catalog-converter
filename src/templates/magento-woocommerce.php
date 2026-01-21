<!DOCTYPE html>
<html>

<head>
    <title>Magento → WooCommerce Column Mapping</title>

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
        <small>Magento → WooCommerce</small>

        <div class="subtitle">
            Map Magento CSV columns to WooCommerce product fields.
        </div>

        <form method="post" action="magento_to_woo_step3_process.php">

            <input type="hidden" name="job_id" value="<?= $jobId ?>">

            <table>
                <thead>
                <tr>
                    <th>Magento Column</th>
                    <th>WooCommerce Field</th>
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

                                <!-- PRODUCT -->
                                <option value="product.type">Product Type</option>
                                <option value="product.sku">SKU</option>
                                <option value="product.parent_sku">Parent SKU</option>
                                <option value="product.name">Product Name</option>
                                <option value="product.description">Description</option>
                                <option value="product.short_description">Short Description</option>
                                <option value="product.status">Status</option>
                                <option value="product.visibility">Visibility</option>

                                <!-- PRICING -->
                                <option value="product.regular_price">Regular Price</option>
                                <option value="product.sale_price">Sale Price</option>

                                <!-- INVENTORY -->
                                <option value="inventory.qty">Stock Quantity</option>
                                <option value="inventory.manage_stock">Manage Stock</option>
                                <option value="inventory.stock_status">Stock Status</option>

                                <!-- IMAGES -->
                                <option value="image.main">Main Image</option>
                                <option value="image.gallery">Gallery Images</option>

                                <!-- ATTRIBUTES -->
                                <option value="attribute.name">Attribute Name</option>
                                <option value="attribute.value">Attribute Value</option>
                                <option value="attribute.visible">Attribute Visible</option>
                                <option value="attribute.global">Attribute Global</option>

                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
            </table>

            <button type="submit">
                Continue → Generate WooCommerce CSV
            </button>

            <div class="footer-note">
                Step 2 of 3 — Review and confirm mappings
            </div>

        </form>

    </div>
</div>

</body>
</html>
