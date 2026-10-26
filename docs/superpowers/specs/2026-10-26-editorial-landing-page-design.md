# Editorial Landing Page Design

## Intent

Refresh `public/index.php` to use the attached skincare storefront as visual direction: a calm editorial hero, warm neutral colors, spacious product presentation, and a clear path into the catalog.

## Goals

- Keep the existing PHP, semantic HTML and shared CSS; do not add a framework or external service.
- Use the approved live product and category catalog for landing-page content, with useful empty/error states.
- Reserve a clear hero image area as a temporary placeholder. The owner will supply the final picture after the layout is designed.
- Show real shop information only: pickup reservations, the 48-hour window, and demo-online or cash-at-shop payment choices.
- Preserve responsive behavior, keyboard focus, image alt text and reduced-motion support.

## Approaches

1. **Editorial storefront (chosen):** large hero and warm, spacious sections like the reference, with live catalog content and the existing brand story. This best matches the supplied image while remaining accurate to the project.
2. **Catalog-first:** compact hero followed immediately by products and categories. More direct to shopping, but less faithful to the reference.

## Constraints and exclusions

- Use a neutral styled hero placeholder until the owner adds a picture; do not generate or copy the attached full-page screenshot as an asset.
- Do not add discounts, bestseller claims, testimonials, brand claims, or newsletter collection. Those require real approved business data or new scope.
- Keep purchase actions linked to the existing catalog/cart flow. Do not add new order or payment behavior.

## Builder choices

- Use the four newest active products as a “New arrivals” row, based on existing product creation timestamps; if there are no products, show a clear catalog empty state.
- Render active categories as simple editorial tiles without relying on missing category image files.
- Reuse the existing copy from the home page and approved pickup/payment rules; do not invent service promises.

## Files expected to change

- `public/index.php`: load active categories and latest products safely and render the refreshed sections.
- `assets/css/styles.css`: style the landing hero, benefit strip, live product row, category tiles and story section responsively.
- `docs/06-architecture-and-project-structure.md` and `docs/07-ui-guidelines.md`: remove stale placeholder/status details and document the implemented page structure.
