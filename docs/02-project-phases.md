# Project Phases and Acceptance Criteria

## Status and sources of truth

The owner approved the functional scope in `docs/03-pages-and-features.md`. That file owns feature requirements; `docs/04-roles-and-permissions.md` owns access boundaries, `docs/05-database-design.md` owns data/migration planning, and `docs/01-rubric-traceability.md` alone owns deployment rubric mapping. This phase plan orders delivery; it does not add requirements. The User catalog/order slice and initial Admin catalog/inventory slice are implemented; other Admin workflows and verified-host deployment remain future work.

## Phases

1. **Resolve remaining scope details.** Confirm open questions in `docs/03-pages-and-features.md`, `docs/04-roles-and-permissions.md`, and `docs/05-database-design.md`. Exit: order/status/payment/refund, the Admin/User permission boundary, stock policies, and image handling are specified enough for data design.
2. **Local environment and skeleton.** Verify installed XAMPP PHP and MySQL/MariaDB versions, start Apache/database, and load a minimal PHP page from `htdocs`. Record actual setup. Keep credentials local and untracked. Exit: runtime and diagnostic path are understood.
3. **Data model and migration procedure.** The initial User catalog/order model and migration 003 are implemented and applied locally. Fresh-database rehearsal, concurrency/idempotency checks, migration repeat/partial-failure verification, and the remaining Admin sales/payment/refund schema are still required before phase exit.
4. **Shared UI and navigation.** Build responsive layouts and role-appropriate navigation. Exit: keyboard, mobile, labels, focus and feedback acceptance checks in `docs/07-ui-guidelines.md` pass.
5. **Accounts and authorization.** User registration, login and logout with a local account migration are implemented as the first slice. Admin route checks now protect the initial Admin catalog/inventory pages; safe Admin account provisioning and full authorization verification remain. Exit: provisioning is approved and authorization cases in `docs/04-roles-and-permissions.md` and `docs/09-testing-and-evidence-plan.md` pass.
6. **Catalog and inventory.** Public/User catalog browsing and product details are implemented. The initial Admin workspace, product/category management, validated image uploads, positive stock receipts and movement-history screen are implemented using migration 003. Negative stock corrections, category deletion/deactivation policy and movement edge-case verification remain. Product removal deactivates records to preserve history. Exit: Admin permissions and inventory checks pass; stock changes are auditable.
7. **In-store sales.** Implement Admin-recorded sales and atomic stock deduction. Exit: price and quantity are server-validated and sale/stock records remain consistent.
8. **Pickup reservations and order handling.** User reservation, own order history/details, pending-unpaid cancellation and request-triggered 48-hour expiry processing are implemented. Admin payment confirmation, paid-order cancellation/refund outcomes, exact-time scheduled expiry, and concurrency/idempotency verification remain. Exit: all transitions are authorized, auditable and concurrency-safe.
9. **Reports.** Implement approved sales and inventory reports from persisted records. Exit: report totals reconcile against source records.
10. **Verification and deployment rehearsal.** Run approved functional/security checks locally, then verify current host compatibility and rehearse deployment. Exit: evidence is recorded against the eight rubric criteria; local and production checks are distinct.
11. **Hosting deployment and demonstration.** Proceed only after actual InfinityFree account/plan capabilities, safe configuration, HTTPS, and file layout are verified. Exit: actual hosted workflows and rubric evidence are demonstrated and documented.

## Change control

Do not silently broaden scope. If a new decision changes a feature, update its source-of-truth document and dependent phase, permissions, data, security, tests, UI or deployment references. Do not claim a phase or rubric criterion complete without recorded evidence. Do not create schema or application code before the relevant decisions and design review.

