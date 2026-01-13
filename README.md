# Universal Catalog Converter

A platform-agnostic CSV catalog converter that imports product data from one e-commerce platform (currently **Shopify** or **WooCommerce**) and exports it into another platform’s CSV format using a **universal internal data model**.

The system is designed to be extensible, predictable, and safe for large catalogs with multiple variants, attributes, images, and inventory data.

---

## What This Project Solves

Different e-commerce platforms export catalogs in different CSV formats.  
This project solves that by:

- Importing platform-specific CSV files
- Normalizing all data into a universal schema
- Exporting clean, platform-compatible CSV files
- Supporting **unlimited attributes**, variants, and images
- Allowing **user-controlled column mapping**
- Making it easy to add new platforms later

---

## Supported Conversions

### Currently Supported
- Shopify → WooCommerce
- WooCommerce → Shopify

### Designed for Future Support
- Magento
- PrestaShop
- BigCommerce
- Custom CSV formats

---

## Core Architecture
# Universal Catalog Converter

A platform-agnostic CSV catalog converter that imports product data from one e-commerce platform (currently **Shopify** or **WooCommerce**) and exports it into another platform’s CSV format using a **universal internal data model**.

The system is designed to be extensible, predictable, and safe for large catalogs with multiple variants, attributes, images, and inventory data.

---

## What This Project Solves

Different e-commerce platforms export catalogs in different CSV formats.  
This project solves that by:

- Importing platform-specific CSV files
- Normalizing all data into a universal schema
- Exporting clean, platform-compatible CSV files
- Supporting **unlimited attributes**, variants, and images
- Allowing **user-controlled column mapping**
- Making it easy to add new platforms later

---

## Supported Conversions

### Currently Supported
- Shopify → WooCommerce
- WooCommerce → Shopify

### Designed for Future Support
- Magento
- PrestaShop
- BigCommerce
- Custom CSV formats

---

## Core Architecture

CSV (Source Platform)
↓
Platform Importer
↓
source_* tables (raw normalized data)
↓
UniversalBuilder
↓
universal_* tables (platform-agnostic)
↓
Platform Exporter
↓
CSV (Target Platform)



Each stage has a single responsibility and does not depend on platform-specific logic outside its scope.

---

## Database Design

### Source Tables (Platform-Specific Normalized Data)

- `source_products`
- `source_variants`
- `source_attributes`
- `source_images`
- `source_inventory`

These tables store imported data exactly once per job.

---

### Universal Tables (Platform-Agnostic)

- `universal_products`
- `universal_variants`
- `universal_attributes`
- `universal_images`

These tables represent a clean, unified product catalog independent of any platform.

---

### Job & Mapping Tables

- `import_jobs` – tracks each import/export job
- `job_mappings` – stores user-selected column mappings per job
- `platforms` – list of supported platforms
- `platform_mappings` – default auto-mappings per platform pair

---

## Key Concepts

### 1. Universal Attribute Model

Attributes are stored as rows, not columns:


Each stage has a single responsibility and does not depend on platform-specific logic outside its scope.

---

## Database Design

### Source Tables (Platform-Specific Normalized Data)

- `source_products`
- `source_variants`
- `source_attributes`
- `source_images`
- `source_inventory`

These tables store imported data exactly once per job.

---

### Universal Tables (Platform-Agnostic)

- `universal_products`
- `universal_variants`
- `universal_attributes`
- `universal_images`

These tables represent a clean, unified product catalog independent of any platform.

---

### Job & Mapping Tables

- `import_jobs` – tracks each import/export job
- `job_mappings` – stores user-selected column mappings per job
- `platforms` – list of supported platforms
- `platform_mappings` – default auto-mappings per platform pair

---

## Key Concepts

### 1. Universal Attribute Model

Attributes are stored as rows, not columns:

(product_id, variant_id, name, value)


This allows:
- Unlimited attributes
- Variant-level attributes
- Product-level attributes
- No hardcoded limits like `attribute1`, `attribute2`, etc.

---

### 2. Mapping Layer

Before import/export:
- Users map CSV columns to universal fields
- Mappings are saved per job
- Default mappings are prefilled from `platform_mappings`

Separate mapping views exist for:
- Shopify CSV
- WooCommerce CSV

---

### 3. Importers

Each platform has its own importer:

- `ShopifyImporter`
- `WooImporter`

Responsibilities:
- Read CSV
- Skip deleted or empty variants
- Normalize data into `source_*` tables
- Handle attributes, images, inventory correctly

---

### 4. UniversalBuilder

The **UniversalBuilder**:
- Reads from `source_*`
- Writes to `universal_*`
- Applies consistent pricing logic
- Maps source variants to universal variants
- Converts attributes and images correctly

This layer never knows which platform the data came from.

---

### 5. Exporters

Each platform has its own exporter:

- `WooExporter`
- `ShopifyExporter`

Responsibilities:
- Read from `universal_*`
- Generate platform-compliant CSV
- Output valid import-ready files
- Handle attributes, images, variants correctly

---

## Project Structure

This allows:
- Unlimited attributes
- Variant-level attributes
- Product-level attributes
- No hardcoded limits like `attribute1`, `attribute2`, etc.

---

### 2. Mapping Layer

Before import/export:
- Users map CSV columns to universal fields
- Mappings are saved per job
- Default mappings are prefilled from `platform_mappings`

Separate mapping views exist for:
- Shopify CSV
- WooCommerce CSV

---

### 3. Importers

Each platform has its own importer:

- `ShopifyImporter`
- `WooImporter`

Responsibilities:
- Read CSV
- Skip deleted or empty variants
- Normalize data into `source_*` tables
- Handle attributes, images, inventory correctly

---

### 4. UniversalBuilder

The **UniversalBuilder**:
- Reads from `source_*`
- Writes to `universal_*`
- Applies consistent pricing logic
- Maps source variants to universal variants
- Converts attributes and images correctly

This layer never knows which platform the data came from.

---

### 5. Exporters

Each platform has its own exporter:

- `WooExporter`
- `ShopifyExporter`

Responsibilities:
- Read from `universal_*`
- Generate platform-compliant CSV
- Output valid import-ready files
- Handle attributes, images, variants correctly

---

## Project Structure
/public
├── index.php
├── step2_mapping.php (Shopify mapping view)
├── woo_step2_mapping.php (WooCommerce mapping view)
├── step3_export.php
├── woo_step3_process.php
└── uploads/

/src
├── Database.php
├── Logger.php
├── Helpers.php

├── Importer/
│ ├── ShopifyImporter.php
│ └── WooImporter.php

├── Normalizer/
│ └── UniversalBuilder.php

└── Exporter/
├── WooExporter.php
└── ShopifyExporter.php


---

## Example Flow (WooCommerce → Shopify)

1. User uploads WooCommerce CSV
2. Job created in `import_jobs`
3. User maps columns in Woo mapping view
4. Mappings saved in `job_mappings`
5. `WooImporter` imports data into `source_*`
6. `UniversalBuilder` creates `universal_*`
7. `ShopifyExporter` generates Shopify CSV
8. User downloads the final file

---

## Handling Special Cases

### Deleted / Empty Variations
- Variations without SKU, price, or attribute values are skipped

### Shopify Default Variants
- Rows with `Title / Default Title` are ignored

### Duplicate Attributes
- Normalized and deduplicated at build time

---

## Adding a New Platform

To add a new platform:

1. Create a new importer in `src/Importer`
2. Parse CSV into `source_*` tables
3. (Optional) Create an exporter in `src/Exporter`
4. Add platform entry in `platforms`
5. Insert default mappings in `platform_mappings`
6. Create a mapping view if required

No changes are needed in:
- UniversalBuilder
- Universal tables
- Existing importers/exporters

---

## Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB
- PDO enabled
- Apache / Nginx / Local server stack

---

## Development Notes

- Written in core PHP (no framework)
- Explicit logic, easy to debug
- Scales well for large catalogs
- Safe to refactor into Laravel or Symfony later
- Designed for long-term extensibility

---

## License

MIT License  
Free for commercial and personal use.

---

## Next Possible Enhancements

- Mapping templates per user
- CSV preview before import
- Background job processing
- Error reports per row
- Image downloading & media sync
- UI to browse universal catalog

---

This README reflects the **actual working system**, not a theoretical design.
