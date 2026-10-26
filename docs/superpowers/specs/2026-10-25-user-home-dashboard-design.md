# User Home Dashboard Design

## Intent

Give a signed-in User a simple account landing page with a personal greeting and links to their profile, the public catalog and their own pickup orders.

## Approved scope

- Route: `user/index.php`.
- Require a signed-in account with role `user`; redirect Visitors to the existing login route and deny Admin accounts.
- At the time of this design, successful User login opened the User home while Admin login remained on the public landing page.

Implementation status update (2026-10-25): the User destination remains `../user/index.php`; Admin now routes to `../admin/index.php`, which contains the first catalog and inventory tools.
- Read the greeting name from `users.first_name`, querying only the current session user ID with a PDO prepared statement. If the name cannot be loaded, show a generic greeting without exposing database diagnostics.
- Reuse the shared header/footer and current responsive visual style. Add Dashboard, My orders and Shop links for signed-in Users.
- Do not add order counts, product highlights, images or fabricated data to the dashboard; catalog/order workflows live on their own pages.

## Approaches

1. **Fetch the current first name on the dashboard request (recommended).** This uses the existing session identity and database connection, shows the latest saved name, and avoids changing login/session and profile-update behavior.
2. **Copy the first name into the session.** This avoids a dashboard query but requires changing authentication and keeping the session synchronized when the profile is edited.
3. **Use a generic greeting only.** This is the smallest implementation but does not fulfill the selected personal-greeting experience.

The recommended option is one read-only prepared query scoped by the authenticated session ID, with a generic fallback. The shared navigation receives route overrides so links work from public, profile, dashboard, catalog and order paths. Successful-login routing uses the authenticated session role, never a submitted role.

## UI and security

Use the existing warm-white/blush visual language, concise welcome copy, one profile action, visible keyboard focus, and a responsive single-column layout. Avoid dashboard metrics, empty fake widgets, and missing-image placeholders. Escape the greeting name in HTML. The page has no state-changing actions and needs no migration or CSRF form.

## Verification

Check PHP syntax, the Visitor redirect and Admin denial through XAMPP, and confirm a signed-in User sees their current first name and a working profile link. Check nested CSS, home, Dashboard, profile, and logout links, plus narrow-screen layout. Keep authenticated database and visual-browser checks marked Not run unless actually exercised.
