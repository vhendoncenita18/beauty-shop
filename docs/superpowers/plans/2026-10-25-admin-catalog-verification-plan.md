# Admin Catalog and Inventory Verification Plan

> **For agentic workers:** Use the existing local PHP/XAMPP setup and perform this checklist only after owner approval. Do not edit application code as part of verification unless separately authorized.

**Goal:** Verify the implemented Admin product, category, image-upload, and stock-receipt workflows locally before moving to the next feature phase.

**Architecture:** Exercise the existing server-rendered Admin routes against an isolated local test database built from migrations 001–003. Use browser requests for role checks and forms, then inspect the resulting database rows and stock movements. Keep test records out of the working `beauty_shop` database.

**Tech Stack:** PHP, PDO, Apache, MySQL/MariaDB, XAMPP, browser.

**Scope references:** `docs/03-pages-and-features.md`, `docs/04-roles-and-permissions.md`, `docs/05-database-design.md`, and `docs/09-testing-and-evidence-plan.md`.

## Global Constraints

- Do not expose or commit local credentials, password values, or database dumps.
- Do not use production hosting or alter hosted data.
- Keep the current local `.env` backed up privately before temporarily pointing the app at a test database; restore it after verification.
- Do not drop a test database or erase audit records without owner approval.
- Keep every planned test marked `Not run` until it is actually performed.
- Do not add dependencies, application features, or migrations during this phase.

## Review Focus

- A Visitor is redirected to login and a User receives an authorization denial on every Admin route and mutation.
- Invalid or missing CSRF tokens do not write categories, products, or stock.
- Product values are validated and rendered safely; duplicate SKUs and category slugs do not disclose database errors.
- Spoofed, unsupported, oversized, or undecodable image uploads are rejected; valid local JPEG, PNG, and WebP files use generated filenames.
- A stock receipt changes on-hand stock and writes its matching actor/reason movement together; available catalog stock remains on-hand minus reserved.

## Phase 1: Prepare Isolated Local Test Data

**Files:** No application files. Temporarily use the ignored local `.env` only after saving a private copy.

- [ ] Confirm Apache/PHP and MySQL/MariaDB are running and record their actual local versions.
- [ ] Resolve the documentation mismatch before applying migrations: `database/migrations/README.md` says only migration 001 exists, while migration files 002 and 003 are present and `docs/05-database-design.md` records them as applied locally. Confirm the actual fresh-database instructions, then create a separate empty local test database and apply migrations 001, 002, and 003 in order.
- [ ] Verify the migration ledger and required tables without copying real customer data.
- [ ] Create local-only test accounts in the test database, including one Admin and one User; store only password hashes.
- [ ] Temporarily point the local application to the isolated test database without displaying or recording secrets.

**Check:** The app connects to the test database, the ledger shows migrations 001–003, and the working `beauty_shop` database is untouched.

## Phase 2: Exercise Admin and User Boundaries

**Files:** No planned application changes. Record results in `docs/09-testing-and-evidence-plan.md` only after each check.

- [ ] As a Visitor, request `admin/index.php`, product list/form, category page, and inventory page directly; confirm redirect to login.
- [ ] As a User, request the same Admin pages and submit a state-changing request; confirm denial and no database change.
- [ ] As an Admin, log in through `public/login.php`; confirm the redirect to `admin/index.php`.
- [ ] Submit representative mutations with missing and invalid CSRF tokens; confirm no rows or stock balances change.

**Check:** Record actual HTTP/result evidence for `ADMIN-01` and `AUTH-07`/`AUTH-08`; do not infer a pass from PHP syntax checks.

## Phase 3: Exercise Catalog and Inventory Workflows

**Files:** No planned application changes. Use the existing Admin forms and public catalog.

- [ ] Add and rename a test category; try a duplicate category slug and confirm safe feedback.
- [ ] Add a product with valid name, SKU, category, PHP price, and description; then edit its details.
- [ ] Try an invalid price, invalid category ID, overlong values, and duplicate SKU; confirm no unintended writes.
- [ ] Upload a valid test image, then test unsupported, spoofed, undecodable, and oversized files; confirm only valid allowed images are stored.
- [ ] Receive a positive quantity with a reason; verify `stock_on_hand` and exactly one matching `received` movement with the Admin actor and reason.
- [ ] Open the public catalog as Visitor/User and verify the active product, price, image or placeholder, and available quantity display.
- [ ] Review product, category, and inventory pages at desktop and narrow mobile widths.

**Check:** Record actual outcomes for `ADMIN-02` through `ADMIN-05`, `UPLOAD-01`, and `INV-01`. A failure is a finding; stop before broadening scope or changing business rules.

## Phase 4: Restore and Report

- [ ] Restore the original local `.env` from its private copy and verify its contents are not printed or included in output.
- [ ] Confirm the working `beauty_shop` database remains unchanged by the verification.
- [ ] Update only test statuses supported by evidence in `docs/09-testing-and-evidence-plan.md` and summarize failures/limitations.
- [ ] Leave the isolated test database intact until the owner approves its removal.

**Exit:** The Admin catalog/inventory checks have recorded outcomes, local configuration is restored, and no unsupported compatibility or deployment claims have been made.

## After This Phase

If Phase 6 checks pass, the next feature phase in `docs/02-project-phases.md` is **in-store sales**. Before designing its migration, resolve the sale record/line snapshots, stock deduction and audit transaction together. Do not add payment-method, customer, tax, fee, discount, receipt, or refund fields unless the owner approves those requirements. Continue to use current database prices and prevent sales from consuming reserved quantities.
