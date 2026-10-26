# InfinityFree Deployment Planning Runbook

## Status and source of truth

This is a checklist for investigation and future deployment, not a verified InfinityFree procedure. Check current documentation and the actual account/plan before relying on PHP versions/extensions, MySQL access, scheduled jobs, HTTPS, upload limits, file layout, environment variables, permissions, or diagnostics. Deployment assessment criteria remain defined only in `docs/01-rubric-traceability.md`; shop smoke cases are in `docs/09-testing-and-evidence-plan.md`.

## A. Prepare locally (future deployment phase)

1. Verify the approved app and tests locally; record actual XAMPP PHP/database versions.
2. Inspect source for local absolute paths, local-only DB host values in production configuration, debug output, secrets and temporary files.
3. If plain PHP/no package manager remains the stack, document that no compiled build exists and run appropriate syntax/configuration checks; do not invent a build command.
4. Back up data for migration rehearsal only. Do not upload local customer data to production by default.

## B. Verify host capabilities before committing to a deployment design

Record the date, source and observed result for supported PHP version/extensions, MySQL/MariaDB version/connectivity, database host details, import limits/tools, assigned URL/domain, HTTPS activation, public web root, private configuration options, upload/storage behavior, scheduled-task support/frequency, writable paths, logs and file transfer method. The 48-hour reservation expiry mechanism must be designed against verified scheduler capabilities; no cron/scheduled job is assumed. If a capability is unavailable, stop and document the compatibility gap for owner decision.

## C. Configuration and file safety

- Separate local and production settings; use actual hosted DB values, never assume `localhost` for production.
- Keep credentials outside source control and public paths where possible. If the host cannot provide private configuration, document the safest verified alternative and its trade-offs before proceeding.
- Identify the public web root and protect `app/config`, logs, migration files and uploaded images. Do not assume the proposed directory layout is private on shared hosting.
- Configure production error handling and HTTPS-aware cookies to match verified settings.

## D. Database and upload deployment

1. Back up existing production data before schema changes and verify the backup.
2. Apply only reviewed migration files in the approved order using the verified host-supported process; record migration identifiers, not secrets.
3. Verify tables/constraints/indexes/ledger and a read-only application query.
4. Do not overwrite production with a development dump.
5. Upload only required application/static files. Exclude `.git`, local secrets, logs, temporary files and local database data.
6. Verify image upload paths, execution prevention, permissions and public exposure against actual host behavior.

## E. Hosted smoke checks

Use `docs/09-testing-and-evidence-plan.md` and the rubric evidence mapping in `docs/01-rubric-traceability.md`. Check home/catalog, login/logout, User order ownership, Admin authorization, sales/inventory operations, reservation/cancellation/payment transitions, reports, assets, HTTPS if supported, safe errors, and available diagnostics. Use clearly identified test accounts/data; do not destructively test real orders. Record only checks actually performed.

## F. Troubleshooting and rollback

Check URL/DNS, public path, PHP/extensions, path case, active configuration, DB host/credentials/permissions, applied schema, upload permissions, browser errors and host diagnostics in that order as relevant. After correcting an issue, repeat the original failing test and record evidence. Before risky deployment changes, back up production database and files; restore a known-good version or use a reviewed forward-fix. Never guess a destructive rollback or expose secrets in logs/runbook.

## G. Student demonstration

Prepare to explain Apache/PHP/MySQL request flow, whether a compiled build exists, local versus hosted configuration, how the database schema is applied, actual HTTPS/routing checks, and one diagnosed deployment failure. Record actual hosting URLs, versions, procedures and outcomes only after verification; omit secret values. This supports the rubric and does not add functional requirements.

