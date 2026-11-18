# Project tools

Run PHP utilities from the project root so the `upload/config.php` database
configuration is found automatically. Database and catalog maintenance scripts
are CLI-only.

## Catalog

- `import-pandashop-products.php`, `import-pandashop-detailed-products.php`,
  `import-pandashop-multibrand-products.php` and
  `import-pandashop-supplemental-products.php` import product data.
- `sync_panda_master.php` previews catalog normalization by default; pass
  `--apply` to write changes.
- `download-pandashop-product-images.php` downloads catalog images.

## Vietnam address data

Scripts and their shared database bootstrap are grouped in `vietnam-address/`.
Source datasets and licensing notes stay in the project-level `data/` folder.
See [the source and import guide](../data/LOCALITY-DATA-SOURCES.md).

Run an importer from the project root, for example:

```powershell
C:\xampp\php\php.exe tools\vietnam-address\import_official_localities.php
```

## Database migrations

SQL changes are grouped in `migrations/`. Review each migration and take a
database backup before applying it.

## Maintenance and documentation

- `fix_master_panda.php` normalizes language/template encodings and creates a
  local backup before modifying files.
- `generate_api.php` builds the API reference in `../docs/api/`.
- `phpstan/` and the bundled PHAR tools support static analysis and formatting.
