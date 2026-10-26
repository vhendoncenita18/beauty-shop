# UI Design Guidelines

## Direction and scope

Use a clean, elegant beauty-shop aesthetic. Blush/rose accents, warm white backgrounds and dark readable text are proposed styling choices, not owner-approved brand requirements or rubric requirements. Functional screens follow `docs/03-pages-and-features.md`; role-appropriate navigation follows `docs/04-roles-and-permissions.md`.

## Main interface needs


- Responsive public catalog with consistent product cards: stable image proportions, clear name/price/availability hierarchy, and a distinct add-to-cart action without exposing internal reserved-stock details.
- Customer cart with add-to-cart feedback/animation toward the header cart icon, item-level quantity/remove controls, clear totals and a “Buy all” checkout action. Submitting the cart creates one pickup order and reserves stock.
- The signed-in User header bag opens a modal cart and checkout with product images, offer-aware item totals, quantity controls, item removal, remove-all, payment choices and a Buy all action. Use a keyboard-accessible dialog with Escape and backdrop dismissal; checkout is available from the bag dialog.
- Customer checkout/order screens offer “Online payment (demo)” and “Cash at the shop.” Clearly state that demo online payment does not contact a provider or transfer money; the order waits for Admin review. Show the 48-hour unpaid reservation deadline and cancellation only while eligible.
- Admin order queue shows customer/order details, selected payment method and payment status, with an Admin-only action to confirm payment after review. Include paid-order cancellation/refund outcome, catalog and inventory management.
- User screens for the public catalog and the signed-in User's own account and pickup orders. Users must not see Admin controls.
- The profile screen follows a responsive account dashboard layout with a prominent identity and personal-details card, a sign-in email card, and a separate password-security card. It lets a signed-in User edit first, middle and last names plus optional phone number. Email is visible but read-only. Password changes use a separate form for the current password, new password and confirmation; never redisplay password values. Show field-level errors and success feedback through the shared popup.
- Signed-in User pages share a brand-only top bar with profile and bag shortcuts and a collapsible, sticky icon-and-text sidebar for dashboard, products, orders, bag, profile and logout. Keep the sidebar below the sticky header while scrolling. Apply the same sidebar/content grid to the dashboard, catalog, product detail, cart, profile and order pages. On desktop, the content column expands when the sidebar collapses; on mobile, keep the compact sticky icon navigation below the header and content full-width. Collapsed icons retain accessible names and tooltips. Do not show the signed-in email in the User header or add placeholder metrics.
- Admin pages use the same brand header, profile menu and collapsible sticky sidebar pattern, with links for workspace, products, categories, inventory, orders and offers/updates. The header bag shortcut opens the customer catalog; Admin tools remain available in the sidebar. Keep the sidebar below the sticky header and allow the content column to expand when collapsed.
- The User dashboard highlights active product offers, newest active products, best sellers counted from paid-for item quantities, and active Admin announcements. Empty sections explain when there is no data yet.
- Admin product offers support a percentage discount, a fixed PHP amount off each purchased unit, or buy-X-get-Y-free. Display the rule on product/catalog/cart screens, apply it again on the server at checkout, show the discount in User and Admin order details, and reserve free units from available stock. Announcement text by itself never changes an order price.
- Catalog cards show product name, PHP price, image or neutral placeholder, and availability without exposing reserved-stock counts. Empty and no-search-results states explain the situation.
- User ordering explains pay-at-shop and the 48-hour deadline. Order history/details show text status and UTC reservation deadline; only pending unpaid orders show a cancellation action.
- User order history uses compact filter tabs and order summary cards with product thumbnails, item/price details, pickup and payment information, and reservation deadline where applicable. The View order action opens an accessible dialog with the complete itemization, payment explanation, order history and eligible cancellation action; preserve the direct detail route as a no-JavaScript fallback. Do not show delivery addresses or invoice downloads because the current pickup-order schema does not store them.
- Registration visibly labels required first/last name, email and password fields; middle name is currently proposed as optional, and phone number is optional. Pair first/middle and last/phone fields on wider screens, use full-width email/password fields, and stack everything on narrow screens. Include password confirmation, accessible password visibility controls and field-level validation feedback without redisplaying passwords.
- Admin movement history and sales/inventory reports need readable tables and filters.
- The Admin dashboard presents shop insights for inventory availability, unpaid pickup orders, confirmed pickup sales and active storefront activity. Keep operational page navigation in the Admin sidebar rather than duplicating it as dashboard shortcut cards. The Admin product list uses a compact table with product thumbnails, search and category filters, stock availability, status badges, and a confirmed Delete action that hides products from the active catalog while preserving history. Product lists and movement history use horizontally scrollable, labeled tables; product forms state the accepted image types/size, and stock receipts require a reason. Sales/order/report screens remain future work.
- Clear empty states, confirmation for consequential actions, and actionable success/error feedback. Label the online option as a demo and never imply that a real online payment was processed.
- Show success feedback in the shared popup card, announce it accessibly, and dismiss it automatically after five seconds. Keep validation and error messages visible near the affected form/action so users have time to correct them.

## Accessibility and usability

- Use semantic HTML and correct heading order; label every form control visibly.
- Make all controls keyboard accessible with visible focus; do not use color alone for status.
- Use readable contrast and responsive text; give product images meaningful alternative text.
- Place useful validation feedback near fields and preserve user-entered values where safe.
- Distinguish buttons from links and communicate order state in text.

## Shared form styling

- Give editable form controls (text, email, telephone, password, search, number, date, select and textarea) a consistent rounded box treatment across public, User and Admin pages. Use an 8px corner radius as the current visual reference, with a clear border, contrasting background and visible keyboard focus. Keep labels visible and do not use rounded styling to imply a control is disabled.
- Apply the shared form-control treatment through common CSS rules where possible; page-specific sizing may vary to fit the layout, while preserving readable text and usable controls.

## CSS and progressive enhancement

- Use CSS custom properties for colors, spacing, radii and typography; shared styles for forms, alerts, cards, tables and navigation.
- Test narrow mobile and common desktop widths; admin/report tables need usable small-screen overflow.
- Keep core navigation, authorization, validation and order/inventory rules server-side. JavaScript is enhancement only.

## Acceptance checklist

- [ ] Catalog, User order, and Admin operational screens work at mobile and desktop widths.
- [ ] Reservation deadline and eligibility for cancellation are clear to customers.
- [ ] Admin can distinguish unpaid, confirmed-paid, cancelled and expired states using text and visual cues.
- [ ] Forms have visible labels, focus states and actionable errors.
- [ ] Tables remain usable on narrow screens; image alternatives are meaningful.
- [ ] No sensitive details appear in errors or customer-facing stock displays.

