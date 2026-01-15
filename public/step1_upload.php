<?php
require_once __DIR__ . '/../src/Database.php';

$db = new Database();
$platforms = $db->query("SELECT id, name, code FROM platforms WHERE is_active=1")->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Catalog Converter</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7fb;
            margin: 0;
            padding: 0;
        }

        .wrapper {
            max-width: 700px;
            margin: 60px auto;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            padding: 30px 35px;
        }

        h2 {
            margin-top: 0;
            text-align: center;
            color: #2d2d2d;
            font-size: 26px;
            letter-spacing: .5px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            font-size: 14px;
        }

        label {
            font-weight: bold;
            margin-bottom: 6px;
            display: block;
            color: #333;
        }

        select,
        input[type=file] {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group {
            margin-bottom: 18px;
        }

        button {
            width: 100%;
            background: #4a6cf7;
            border: none;
            color: #fff;
            font-size: 16px;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s ease;
            letter-spacing: .3px;
        }

        button:hover {
            background: #264df2;
        }

        .footer-note {
            text-align: center;
            margin-top: 18px;
            color: #777;
            font-size: 13px;
        }

        .row {
            display: flex;
            gap: 15px;
        }

        .col {
            flex: 1;
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <div class="card">

            <h2>Catalog Converter</h2>
            <div class="subtitle">
                Convert your product catalog between platforms in a few simple steps
            </div>

            <form method="post" action="step2_mapping.php" enctype="multipart/form-data">

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Select Source Platform</label>
                            <select name="source_platform" required>
                                <?php foreach ($platforms as $p): ?>
                                    <option value="<?= $p['code'] ?>"><?= $p['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col">
                        <div class="form-group">
                            <label>Select Target Platform</label>
                            <select name="target_platform" required>
                                <?php foreach ($platforms as $p): ?>
                                    <option value="<?= $p['code'] ?>"><?= $p['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Select CSV File</label>
                    <input type="file" name="csv" required>
                </div>

                <button type="submit">Continue →</button>

            </form>

            <div class="footer-note">
                Step 1 of 3 — Upload your source catalog file
            </div>

        </div>
    </div>

</body>

</html>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Select the Source Platform (Shopify)
        const sourceSelect = document.querySelector('select[name="source_platform"]');
        if (sourceSelect) {
            sourceSelect.value = 'woocommerce';
        }

        // 2. Select the Target Platform (Woocommerce)
        const targetSelect = document.querySelector('select[name="target_platform"]');
        if (targetSelect) {
            targetSelect.value = 'magento';
        }
    });
</script>