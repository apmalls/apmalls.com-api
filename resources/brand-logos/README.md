# Official Brand Logo Bundle

Collected on 7 October 2026 from official brand websites and asset hosts referenced by those websites. See `manifest.json` for source pages, original/final URLs, SHA-256 checksums, dimensions and normalization details. `review-sheet.png` is for review only and is never imported.

24 of 25 unique seeded brand identities have verified assets. Nestle remains unresolved: its official corporate website denied asset access, so no substitute was bundled. Tata uses its official corporate mark; Parle is Parle Products; Anchor is Anchor by Panasonic. All artwork preserves official colors and proportions. PNGs are capped at 640 pixels without upscaling. Only blank white canvas around Anchor/Fevicol was trimmed; white backgrounds and all logo artwork are retained. Some official source images are low resolution and remain at their original size.

These are third-party trademarks, not evidence of sponsorship, authorization or partnership. Public availability does not grant trademark or copyright rights. The store owner is responsible for appropriate use and any required permissions. No logo is generated, sourced from a logo aggregator or hotlinked at runtime.

## Rollout

No migration, reseeding or frontend API changes are needed. Do not run BrandSeeder: it can change featured selections. Deploy the backend bundle and importer before the logo-only frontend.

From the deployed backend directory:

```sh
php artisan brands:import-logos
```

This prints the configured database target and a read-only eligibility table. `eligible` means an existing, non-trashed brand with a null/blank logo. Other results are `existing`, `trashed`, `missing`, `unresolved` or `failed`. No records or media are modified by preview.

Before applying, independently confirm the production database target and take/verify a database backup using your normal PostgreSQL backup process. Also preserve existing public media. Do not share database passwords in terminal screenshots. Then:

```sh
php artisan brands:import-logos --apply
```

The command requires interactive confirmation of the target and backup. It imports only missing logos into existing non-trashed records, under `storage/app/public/brands/official/`, preserving all other fields and timestamps. Eligibility is checked under a row lock and again during the conditional update; rerunning preserves already populated logos. Files copied during a failed update are removed. Any cleanup failure reports the exact orphaned path for operator review. Imports commit per brand, so a failure does not reverse other successes.

```sh
php artisan storage:link
```

Run only if the normal `public/storage` link is absent. Ensure the deployment serves and persists the public disk. Deploy the frontend only after imported media URLs are reachable. Featured choices remain manual; refresh the homepage after its approximately 60-second cache window. Already-open pages do not update automatically.

If a row fails, fix storage/database access and rerun preview/apply. Unresolved and unmatched slugs remain unchanged. If official artwork changes, replace it only through a newly reviewed bundle; this command intentionally never overwrites an existing staff upload or prior import. Backups, not reseeding, are the recovery mechanism.

## Collection and Verification

The frontend workspace contains a build-time collection script (`scripts/collect-brand-logos.cjs`) using Sharp. It is not invoked by the website or import command. Recollection requires network access and a fresh visual/provenance review before deployment; the importer does not download anything.

Importer tests use guarded in-memory SQLite and fake public storage, never the client database. Test command:

```sh
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --do-not-cache-result tests/Feature/BrandLogoImportTest.php
```

No database import, development server or queue worker has been started as part of preparing this bundle.
