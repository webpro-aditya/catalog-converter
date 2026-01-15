<!DOCTYPE html>
<html>

<head>
    <title>WooCommerce → Magento Column Mapping</title>

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
        <small>WooCommerce → Magento</small>

        <div class="subtitle">
            Map WooCommerce CSV columns to Magento product fields.
        </div>

        <form method="post" action="woo_to_magento_step3_process.php">

            <input type="hidden" name="job_id" value="<?= $jobId ?>">

            <table>
                <thead>
                <tr>
                    <th>WooCommerce Column</th>
                    <th>Magento Field</th>
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

                                <!-- MAGENTO CORE PRODUCT FIELDS -->
                                <option value="product.sku">SKU</option>
                                <option value="product.name">Product Name</option>
                                <option value="product.description">Description</option>
                                <option value="product.short_description">Short Description</option>
                                <option value="product.price">Price</option>
                                <option value="product.special_price">Special Price</option>
                                <option value="product.status">Status</option>
                                <option value="product.visibility">Visibility</option>
                                <option value="product.attribute_set">Attribute Set</option>
                                <option value="product.product_type">Product Type</option>

                                <!-- INVENTORY -->
                                <option value="inventory.qty">Quantity</option>
                                <option value="inventory.is_in_stock">Is In Stock</option>

                                <!-- IMAGES -->
                                <option value="image.base">Base Image</option>
                                <option value="image.small">Small Image</option>
                                <option value="image.thumbnail">Thumbnail</option>
                                <option value="image.gallery">Additional Images</option>

                                <!-- CONFIGURABLE / VARIANT -->
                                <option value="variant.parent_sku">Parent SKU</option>

                                <!-- ATTRIBUTES (DYNAMIC) -->
                                <option value="attribute.code">Attribute Code</option>
                                <option value="attribute.value">Attribute Value</option>

                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
            </table>

            <button type="submit">
                Continue → Generate Magento CSV
            </button>

            <div class="footer-note">
                Step 2 of 3 — Review and confirm mappings
            </div>

        </form>

    </div>
</div>

</body>
</html>
