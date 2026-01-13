<!DOCTYPE html>
<html>

<head>
    <title>Map Columns</title>

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6fb;
            margin: 0;
        }

        .wrapper {
            max-width: 1000px;
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
            margin-top: 0;
            text-align: center;
            color: #2d2d2d;
            font-size: 26px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .table-wrapper {
            border: 1px solid #e2e2e2;
            border-radius: 10px;
            overflow: hidden;
            background: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f2f5ff;
        }

        thead th {
            padding: 12px;
            text-align: left;
            font-size: 13px;
            color: #333;
            position: sticky;
            top: 0;
            background: #eef2ff;
        }

        tbody td {
            padding: 9px 12px;
            border-top: 1px solid #eee;
            font-size: 14px;
        }

        tbody tr:hover {
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
            transition: .2s ease;
        }

        button:hover {
            background: #274df5;
        }

        .footer-note {
            text-align: center;
            margin-top: 12px;
            color: #777;
        }

        .badge {
            background: #eef2ff;
            padding: 4px 10px;
            border-radius: 20px;
            color: #4a6cf7;
            font-size: 12px;
        }
    </style>

</head>

<body>

    <div class="wrapper">

        <div class="card">

            <h2>Step 2 — Map Columns</h2>
            <div class="subtitle">
                Match your source columns to the correct fields. You can adjust if needed.
            </div>

            <form method="post" action="step3_export.php">

                <input type="hidden" name="job_id" value="<?= $jobId ?>">
                <input type="hidden" name="target_platform" value="<?= $_POST['target_platform'] ?>">

                <div class="table-wrapper">

                    <table>

                        <thead>
                            <tr>
                                <th>Source Column</th>
                                <th>Mapped To</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($headers as $h): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($h) ?>
                                    </td>

                                    <td>
                                        <select name="map[<?= htmlspecialchars($h) ?>]">
                                            <?php $key = normalize($h); ?>

                                            <option value="">-- Ignore --</option>

                                            <!-- PRODUCT -->
                                            <option value="product.name" <?= ($mappings[$key] ?? '') == 'product.name' ? 'selected' : '' ?>>Product Name</option>
                                            <option value="product.slug" <?= ($mappings[$key] ?? '') == 'product.slug' ? 'selected' : '' ?>>Slug</option>
                                            <option value="product.description" <?= ($mappings[$key] ?? '') == 'product.description' ? 'selected' : '' ?>>Description</option>
                                            <option value="product.brand" <?= ($mappings[$key] ?? '') == 'product.brand' ? 'selected' : '' ?>>Brand</option>
                                            <option value="product.type" <?= ($mappings[$key] ?? '') == 'product.type' ? 'selected' : '' ?>>Product Type</option>
                                            <option value="product.tags" <?= ($mappings[$key] ?? '') == 'product.tags' ? 'selected' : '' ?>>Tags</option>
                                            <option value="product.image" <?= ($mappings[$key] ?? '') == 'product.image' ? 'selected' : '' ?>>Main Image</option>

                                            <!-- VARIANT -->
                                            <option value="variant.sku" <?= ($mappings[$key] ?? '') == 'variant.sku' ? 'selected' : '' ?>>Variant SKU</option>
                                            <option value="variant.price" <?= ($mappings[$key] ?? '') == 'variant.price' ? 'selected' : '' ?>>Variant Price</option>
                                            <option value="variant.compare_at_price" <?= ($mappings[$key] ?? '') == 'variant.compare_at_price' ? 'selected' : '' ?>>Variant Compare Price</option>
                                            <option value="variant.barcode" <?= ($mappings[$key] ?? '') == 'variant.barcode' ? 'selected' : '' ?>>Variant Barcode</option>
                                            <option value="variant.stock" <?= ($mappings[$key] ?? '') == 'variant.stock' ? 'selected' : '' ?>>Inventory Qty</option>
                                            <option value="variant.stock_status" <?= ($mappings[$key] ?? '') == 'variant.stock_status' ? 'selected' : '' ?>>Stock Status</option>
                                            <option value="variant.weight" <?= ($mappings[$key] ?? '') == 'variant.weight' ? 'selected' : '' ?>>Weight</option>
                                            <option value="variant.weight_unit" <?= ($mappings[$key] ?? '') == 'variant.weight_unit' ? 'selected' : '' ?>>Weight Unit</option>
                                            <option value="variant.requires_shipping" <?= ($mappings[$key] ?? '') == 'variant.requires_shipping' ? 'selected' : '' ?>>Requires Shipping</option>
                                            <option value="variant.image" <?= ($mappings[$key] ?? '') == 'variant.image' ? 'selected' : '' ?>>Variant Image</option>

                                            <!-- ATTRIBUTES -->
                                            <option value="attribute1.name" <?= ($mappings[$key] ?? '') == 'attribute1.name' ? 'selected' : '' ?>>Attribute 1 Name</option>
                                            <option value="attribute1.value" <?= ($mappings[$key] ?? '') == 'attribute1.value' ? 'selected' : '' ?>>Attribute 1 Value</option>
                                            <option value="attribute2.name" <?= ($mappings[$key] ?? '') == 'attribute2.name' ? 'selected' : '' ?>>Attribute 2 Name</option>
                                            <option value="attribute2.value" <?= ($mappings[$key] ?? '') == 'attribute2.value' ? 'selected' : '' ?>>Attribute 2 Value</option>
                                            <option value="attribute3.name" <?= ($mappings[$key] ?? '') == 'attribute3.name' ? 'selected' : '' ?>>Attribute 3 Name</option>
                                            <option value="attribute3.value" <?= ($mappings[$key] ?? '') == 'attribute3.value' ? 'selected' : '' ?>>Attribute 3 Value</option>

                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <button type="submit">Generate Export File →</button>

                <div class="footer-note">
                    Step 2 of 3 — Review and confirm your mappings
                </div>

            </form>

        </div>
    </div>

</body>

</html>