<!DOCTYPE html>
<html>
<head>
    <title>WooCommerce Column Mapping</title>

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
            box-shadow: 0 10px 25px rgba(0,0,0,.08);
            padding: 25px 35px;
        }
        h2 {
            margin: 0 0 5px;
            text-align: center;
            font-size: 26px;
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
            padding: 14px;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #274df5;
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

        <h2>WooCommerce Column Mapping</h2>
        <div class="subtitle">
            Map WooCommerce CSV columns to the universal product model
        </div>

        <form method="post" action="woo_step3_process.php">

            <input type="hidden" name="job_id" value="<?= $jobId ?>">

            <table>
                <thead>
                    <tr>
                        <th>WooCommerce Column</th>
                        <th>Map To</th>
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
                                <option value="product.name" <?= ($mappings[$col] ?? '') === 'product.name' ? 'selected' : '' ?>>Product Name</option>
                                <option value="product.slug">Product Slug</option>
                                <option value="product.description">Description</option>
                                <option value="product.brand">Brand</option>
                                <option value="product.type">Product Type</option>
                                <option value="product.tags">Tags</option>
                                <option value="product.image">Main Image</option>

                                <!-- VARIANT -->
                                <option value="variant.sku">Variant SKU</option>
                                <option value="variant.price">Price</option>
                                <option value="variant.compare_at_price">Regular Price</option>
                                <option value="variant.stock">Stock Quantity</option>
                                <option value="variant.stock_status">Stock Status</option>
                                <option value="variant.weight">Weight</option>
                                <option value="variant.weight_unit">Weight Unit</option>
                                <option value="variant.image">Variant Image</option>

                                <!-- ATTRIBUTES (GENERIC) -->
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
                Continue → Build Products
            </button>

        </form>

    </div>
</div>

</body>
</html>