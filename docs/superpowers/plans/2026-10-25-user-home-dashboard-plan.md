# User Home Dashboard Implementation Plan

> This plan records the original dashboard/login slice. A later owner request expanded the User home to link to the catalog and orders; those pages and their data migration are documented in `docs/superpowers/specs/2026-10-25-user-pages-design.md`.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox syntax for tracking.

**Goal:** Give signed-in Users a simple account home with a greeting based on their saved first name and a direct link to profile management.

**Architecture:** Add `user/index.php`, authorize using the existing session role, and fetch only the session User's first name with PDO. Reuse the shared layout and allow its header to link back to the User home; use existing CSS tokens without adding dependencies or database changes.

**Tech Stack:** Native PHP 8, PDO, MySQL/MariaDB, HTML and existing CSS.

**Spec:** `docs/superpowers/specs/2026-10-25-user-home-dashboard-design.md`

## Global Constraints

- Use PDO prepared statements for variable input in SQL.
- Enforce authorization server-side; exactly two roles exist.
- Escape rendered output contextually and return generic errors.
- Do not add dependencies or a database migration.
- Do not show fabricated product or order data.

## Review Focus

- Visitor requests redirect to the existing login path.
- Admin sessions receive HTTP 403 and cannot view the User page.
- User ID comes only from the authenticated session; the page accepts no target ID.
- Missing profile data or database errors produce a generic greeting/error, not a stack trace.
- Header, profile, home, logout and CSS links resolve from nested routes on mobile and desktop.

## Task 1: User Home Route

**Files:**
- Create `user/index.php` for User authorization, name lookup and greeting page.
- Create `app/auth/login_redirect.php` with the role-based successful-login destination.
- Modify `public/login.php` to use the authenticated session role for its successful-login redirect.
- Create `tests/login-redirect.php` to check User and Admin destinations.
- Modify `app/includes/site-header.php` to accept a User-home URL and render a Dashboard link only for signed-in Users.
- Modify `assets/css/styles.css` with compact responsive styling that reuses current colors, type and rounded card treatment.
- Create `tests/user-dashboard-responsive.php` as a dependency-free guard for the narrow-header wrapping rule.
- Modify `README.md`, `docs/06-architecture-and-project-structure.md`, `docs/07-ui-guidelines.md`, and `docs/09-testing-and-evidence-plan.md` to describe the implemented route and planned checks.

**Interfaces:**
- Consume `current_user(): ?array`, `database_connection(): PDO`, and existing nested URL variables.
- Produce `user/index.php`; no request parameter may select a user record.

- [x] Before implementation, request `/beauty-shop/user/index.php` through local Apache and record the existing 404 response as the route smoke-test baseline.
- [x] Write and run `php tests/login-redirect.php`; it failed because the role-based redirect helper was missing.
- [x] Implement and test the role-based login destination for the original dashboard phase: User goes to `../user/index.php`; Admin then stayed at `index.php`. The later Admin catalog phase updates Admin destination to `../admin/index.php`; its redirect check is pending.
- [x] Implement Visitor redirect and Admin denial before querying profile data.
- [x] Query `first_name` with a prepared statement constrained to the integer-cast session User ID; on missing name or database failure, display a generic greeting without exposing diagnostics.
- [x] Render a clear welcome heading, brief account-home text, and one profile link; do not render order/catalog metrics or fake content.
- [x] Extend shared header with a configurable nested User-home URL and a Dashboard link for Users; set route URLs for the new page.
- [x] Add focused responsive CSS and preserve visible keyboard focus, readable contrast and no horizontal overflow.
- [x] Add a focused responsive CSS regression check and verify it fails before the narrow-header rule is added.
- [x] Update the architecture map and README; add dashboard security and responsive-route cases to the test matrix, retaining `Not run` for untested authenticated/browser behavior.
- [x] Run PHP syntax checks for the new page and modified header.
- [x] Request the route again through local Apache and verify Visitor receives a redirect; inspect the output headers and server response. Verify Admin/User views in a signed-in browser if available; otherwise leave those cases `Not run`.
- [x] Review final files for session-ID-only SQL, role checks before data access, contextual escaping and correct nested links.

## Scope Boundary

Stop after this account-home and successful-login routing slice. Do not implement a catalog, order summary, order page, Admin dashboard, SQL migration, or new session fields. The previously approved native implementation preference applies; there is no Git repository available for branch/commit workflow.
