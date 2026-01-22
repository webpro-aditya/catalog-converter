
---

## Example Flow (Magento → Shopify)

1. Upload Magento CSV
2. Job created in `import_jobs`
3. User maps columns (Magento mapping view)
4. Mappings saved in `job_mappings`
5. `MagentoImporter` → `source_*`
6. `UniversalBuilder` → `universal_*`
7. `ShopifyExporter` generates Shopify CSV
8. User downloads ready-to-import file

---

## Edge Cases Handled

### Deleted / Empty Variants
- Variants with no price & no attributes are skipped

### Shopify Default Variants
- Rows with `Title / Default Title` ignored

### Magento URL Conflicts
- Unique `url_key` generated per SKU

### Duplicate Attributes
- Deduplicated during build phase

---

## Adding a New Platform

To add a new platform:

1. Create importer in `src/Importer`
2. Map CSV → `source_*`
3. (Optional) Create exporter
4. Insert platform in `platforms`
5. Insert defaults in `platform_mappings`
6. Create mapping UI if needed

**No changes required in UniversalBuilder.**

---

## Requirements

- PHP 7.4+
- MySQL 5.7+ / MariaDB
- PDO enabled
- Apache / Nginx / Local stack

---

## Development Notes

- Written in **plain PHP**
- No framework lock-in
- Debuggable and explicit
- Safe for large catalogs
- Ready to be migrated to Laravel/Symfony later

---

## Known Constraints

- Magento attributes must exist before import
- Magento option labels must match exactly
- Image URLs are not downloaded (CSV only)

---

## License

MIT License  
Free for commercial and personal use.

---

## Future Enhancements

- Attribute auto-creation for Magento
- CSV row-level error reports
- Background job processing
- Image downloading & media sync
- Admin UI to browse universal catalog
- Saved mapping templates per user

---

This README reflects the **actual implemented system**, including Magento behavior, not a conceptual design.

CSV (Source Platform)
↓
Platform Importer
↓
source_* tables (normalized raw data)
↓
UniversalBuilder
↓
universal_* tables (platform-agnostic)
↓
Platform Exporter
↓
CSV (Target Platform)


Each layer has **one responsibility** and no platform leakage.

---

## Database Design

### Source Tables (Imported Raw Data)

Platform-specific CSVs are normalized into:

- `source_products`
- `source_variants`
- `source_attributes`
- `source_images`
- `source_inventory`

These tables are **cleared per job** to keep imports isolated.

---

### Universal Tables (Platform-Agnostic)

After normalization:

- `universal_products`
- `universal_variants`
- `universal_attributes`
- `universal_images`

These tables represent a **clean canonical catalog** independent of platform rules.

---

### Job & Mapping Tables

- `import_jobs` – tracks each conversion job
- `job_mappings` – user-selected column mappings per job
- `platforms` – supported platforms (Shopify, WooCommerce, Magento)
- `platform_mappings` – default auto-mappings between platforms

---

## Core Concepts

### 1. Universal Attribute Model (Key Design)

Attributes are stored as **rows**, not columns:


This allows:
- Unlimited attributes
- Variant-level attributes
- Product-level attributes
- No hard limits like `attribute1`, `attribute2`

---

### 2. Mapping Layer

Before import/export:
- Users map CSV columns to universal fields
- Mappings are stored in `job_mappings`
- Defaults are auto-loaded from `platform_mappings`

Separate mapping views exist for:
- Shopify
- WooCommerce
- Magento

---

### 3. Importers

Each platform has a dedicated importer:

- `ShopifyImporter`
- `WooImporter`
- `MagentoImporter`

Responsibilities:
- Read CSV safely
- Skip deleted / empty variants
- Normalize attributes correctly
- Store images (product & variant)
- Insert clean rows into `source_*`

---

### 4. UniversalBuilder

The **UniversalBuilder**:
- Reads from `source_*`
- Writes to `universal_*`
- Handles pricing logic
- Maps variants correctly
- Deduplicates attributes
- Normalizes images

**This layer is platform-blind.**

---

### 5. Exporters

Each platform has its own exporter:

- `ShopifyExporter`
- `WooExporter`
- `MagentoExporter`

Responsibilities:
- Read from `universal_*`
- Produce platform-valid CSV
- Generate correct variable/configurable product structure
- Ensure import compatibility

---

## Magento-Specific Handling (Important)

### Configurable Products

- Parent product:
  - `product_type = configurable`
  - `status = Enabled`
  - `visibility = Catalog, Search`
- Variants:
  - `product_type = simple`
  - `visibility = Not Visible Individually`

### Linking Variants

Magento requires:
- `configurable_variations` column on the **configurable row**
- Format:
sku=SKU1,color=Red,size=M|sku=SKU2,color=Blue,size=L


### Attribute Rules

- Attribute **codes** must already exist in Magento
- Attribute **values must match option labels exactly**
- No slugification or encoding of values

---

## Project Structure

/public
├── index.php
├── step2_mapping.php
├── woo_step2_mapping.php
├── magento_step2_mapping.php
├── step3_export.php
├── woo_step3_process.php
├── magento_step3_process.php
└── uploads/

/src
├── Database.php
├── Logger.php
├── Helpers.php

├── Common/
│ └── Truncate.php

├── Importer/
│ ├── ShopifyImporter.php
│ ├── WooImporter.php
│ └── MagentoImporter.php

├── Normalizer/
│ └── UniversalBuilder.php

└── Exporter/
├── ShopifyExporter.php
├── WooExporter.php
└── MagentoExporter.php


---

## Example Flow (Magento → Shopify)

1. Upload Magento CSV
2. Job created in `import_jobs`
3. User maps columns (Magento mapping view)
4. Mappings saved in `job_mappings`
5. `MagentoImporter` → `source_*`
6. `UniversalBuilder` → `universal_*`
7. `ShopifyExporter` generates Shopify CSV
8. User downloads ready-to-import file

---

## Edge Cases Handled

### Deleted / Empty Variants
- Variants with no price & no attributes are skipped

### Shopify Default Variants
- Rows with `Title / Default Title` ignored

### Magento URL Conflicts
- Unique `url_key` generated per SKU

### Duplicate Attributes
- Deduplicated during build phase

---

## Adding a New Platform

To add a new platform:

1. Create importer in `src/Importer`
2. Map CSV → `source_*`
3. (Optional) Create exporter
4. Insert platform in `platforms`
5. Insert defaults in `platform_mappings`
6. Create mapping UI if needed

**No changes required in UniversalBuilder.**

---

## Requirements

- PHP 7.4+
- MySQL 5.7+ / MariaDB
- PDO enabled
- Apache / Nginx / Local stack

---

## Development Notes

- Written in **plain PHP**
- No framework lock-in
- Debuggable and explicit
- Safe for large catalogs
- Ready to be migrated to Laravel/Symfony later

---

## Known Constraints

- Magento attributes must exist before import
- Magento option labels must match exactly
- Image URLs are not downloaded (CSV only)

---

## License

MIT License  
Free for commercial and personal use.

---

## Future Enhancements

- Attribute auto-creation for Magento
- CSV row-level error reports
- Background job processing
- Image downloading & media sync
- Admin UI to browse universal catalog
- Saved mapping templates per user

---

This README reflects the **actual implemented system**, including Magento behavior, not a conceptual design.
