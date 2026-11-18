# DaREO Jewels — Lite (2k budget edition)

A lean, **complete** version of the jewelry system: public product gallery **plus** the profit/loss computation the client actually needs. This is *not* the gallery-only 5k variant.

Built with the `loads-builder` skill's rules: modular files, modal-first interactions, integer-centavo money, no fabricated results, and approval gates for anything destructive.

## Stack and why it fits the budget
- Native **PHP 8.0+** with **SQLite** (`pdo_sqlite`). No Composer install, no MySQL database, no framework updates.
- Runs on XAMPP or any cheap shared host with PHP + SQLite. Point the document root at this folder.
- All data lives in `data/app.sqlite` (created automatically on first request). Back up that one file.

## What is included
| Area | Included |
|---|---|
| Public gallery | Available products only, search, category filter, product detail, photo thumbnails, Messenger inquiry link. No prices shown publicly. |
| Admin | One admin account, first-run setup, session login with rate-limit note, CSRF on every POST. |
| Inventory | Add/edit/archive products, acquisition + direct cost, up to 3 photos per product, suggested cash/installment prices. |
| Sales | Cash or installment, agreed price, downpayment, payment collection, payment reversal, Received handover, cancellation once all payments are reversed. |
| Expenses | Category, description, amount and real spent date. |
| Computation | **Net profit/loss = Sales − Cost of sold jewelry − Operating expenses.** Monthly filter, margin %, collections, receivables, unsold inventory value. Browser print / Save PDF (CSV endpoint remains but the UI button was removed). |
| Extra | Pricing calculator (profit margin vs markup), “Powered by: Loadsss” footer modal (opt-out in Settings). |

## Accounting rules (same policy as the full system)
- Revenue and COGS are recognized **when a sale is marked Received**, not when a downpayment is collected.
- Product cost = acquisition cost + directly attributable item cost. Do not charge a whole buying trip to one item, and do not record the same expense twice as product cost and OPEX.
- Unsold products stay in inventory, not COGS. Full payment is settlement of a receivable, not a second sale.
- Cash movement here covers collections minus operating expenses only. Inventory purchase cash flow and payment reversals are **not** tracked in Lite.
- This is a management tool, not an audited financial statement or tax advice.

## Setup
1. Copy this folder into `htdocs` (e.g. `https://dareo-jewels.xo.je/lite/`).
2. Visit `/setup` once to create the admin account (username ≥ 3 chars, password ≥ 8 chars).
3. Settings → set business name and Messenger link; adjust margin/markup defaults.
4. Add products with their real acquisition cost, then record sales and expenses.
5. Open Dashboard or Reports, choose the month, and read Net profit / (loss).

## Deliberately excluded from the 2k Lite scope
Installment schedules and due reminders, notifications, multi-role permissions, audit history, password-reset email, reversals, customer portal, online checkout, multi-branch, tax filing, native XLSX, external services. Adding any of these changes the price.

## Security notes
- All SQL uses prepared statements. Passwords use `password_hash`. CSRF token is checked on every POST.
- Uploads are restricted to JPG/PNG/WebP, 3 MB, with random filenames.
- `src/`, `templates/` and `data/` are blocked from direct web access; `data/.htaccess` denies the database file.
- Change hosting credentials and use HTTPS before going live. Do not commit real customer data.

## Catalog import (from the original system)
- 117 source products → **112 imported**; 114 source photos → **113 unique photos copied directly** into `assets/uploads` (no upload button needed).
- Removed 5 duplicate product rows: `38→24`, `91,92→28`, `70→44`, `4→9`. Their photos were merged into the kept product, so nothing visual was lost. One byte-identical photo was dropped.
- Kept both rows `11` and `23` ("Gold Earrings EF 1.03g"): visually different pieces (heart hoop vs plain hoop) despite the same name. This is the only remaining repeated name.
- **Cost warning:** 109 products in the original had placeholder costs of ₱1,000 acquisition + ₱1,000 direct. Those were imported as **₱0** on purpose so the P&L cannot quietly report fake profit. Enter real acquisition/direct costs per item before trusting any calculation. The one non-placeholder item (Gold Bracelet, ₱8,500 + ₱900) was preserved.
- Status values were preserved: 110 Available, 1 Sold, 1 Reserved. No sales, payments or expenses were imported.

## UI/UX refresh
- Public routes: `/` is the **Home page** (hero + featured pieces); `/collection` is the filterable gallery; `/p/{id}` is the product detail; `/about` and `/contact` are static pages. Nav active state and all back links follow these routes.
- Collection follows the supplied mobile reference: burgundy `#701219` header (sampled with the gold `#d5a450` and deep red `#490805` from `public/images/logo`), gold logo image, cream `#fbf7f0` background, two-column white cards with an inset “Available” pill.
- Public header and admin header/sidebar use the **no-tagline** wordmark (`dareo-logo-wo-tagline.png`); the Home hero keeps the logo **with tagline** (`dareo-logo-w-tagline.png`) on the right. Header has centered nav and language button on one row; on phones the nav drops below and uses the hamburger.
- The Home hero keeps the headline on the left and places the large logo with tagline on the right (~660px wide at 1440px), where the thin diamond accent used to be; the right logo hides on phones.
- Favicon uses the circular DaREO emblem (`assets/logo/favicon.png`, 64×64, ~10 KB), with a 180×180 `apple-touch-icon.png` for mobile home screens; both public and admin pages link them.
- New sale shows a third computed field, **Per installment (₱)**, beside Agreed price and Downpayment: (price − downpayment) ÷ number of installments, recalculated as any value changes.
- New sale asks for **Number of installments** when payment type is Installment (hidden for cash). The Payment dialog shows the remaining balance split across the 15th and 30th of each month, starting after the sale date.
- The Payment dialog numbers each 15th/30th row (1…count); paid rows are crossed out with a full-width dark line (number → date → price). Recording a payment uses a three-column form (Amount / Date paid / Installment no.), auto-selects the next unpaid number and auto-fills its amount (still editable). Overpayment is carried to the next installment; paid numbers are disabled and duplicates are rejected server-side. Only the latest payment can be reversed (older ones have no Reverse button, and direct POSTs are rejected); after reversing, the next one becomes reversible. Responsive: the same three-column row is kept on phones with tighter padding; all values stay readable.
- New sale installment downpayment auto-fills to 50% of the agreed price when the price changes (editable), and the server rejects installment sales below 50%.
- New sale uses a searchable product picker with product thumbnails, names, and SKU/category; it still submits through the native select and keeps the suggested-price logic.
- Product detail hides the “Product notes” panel entirely when the description is empty; the Messenger inquiry panel always remains.
- Admin header: the no-tagline logo is centered in the sidebar on desktop but left-aligned in the mobile drawer; the topbar shows only a live date/time on the right (Asia/Manila, refreshes every 30s). “Signed in as …” and the “Admin” label were removed. Sign out lives at the bottom footer of the sidebar.
- Mobile sidebar nav is a single column, matching the desktop format.
- Admin account page (`/admin/account`) lets the owner edit display name and email, and change the password after verifying the current one (min 8 characters). Username stays fixed as the login identity.
- Admin **Products** stays a table on desktop but uses a dedicated compact card on phones at ≤760px: product name · category, then image + plain status · Price: value · Cost: value, then the SKU and Edit/Archive buttons. Status is plain text, not a badge.
- Sales list shows a product thumbnail in both views: an **Image** column in the desktop table and a thumbnail inside each mobile card.
- Admin forms fill the desktop width: the product editor uses three columns (photos / main details / SKU-category-notes-actions), while Settings, Calculator and My account use two-column layouts. They stack back into one column on phones.
- The product photo preview fills the whole panel when there is one photo (4:3, contained so the whole piece is visible) and switches to two columns for multiple photos; the “No product images yet” placeholder hides whenever a photo exists.
- Mobile menus (admin sidebar and public hamburger) close on outside click, on Escape, and after a nav link is tapped; the open sidebar also dims the page behind it.
- The public footer is pinned to the bottom of short pages. The admin footer was removed; signed-in admin pages end after the content, while Sign out remains in the sidebar footer.
- Admin uses the same burgundy/gold/cream elements and logo as the storefront, with a visible active item in the sidebar.
- Products admin has a name/SKU search box and status dropdown (All / Available / Reserved / Sold / Archived); filtering is server-side with 10 rows per page and preserves the query in pagination links.
- Dashboard and Reports month pickers apply automatically on selection (no View button), use the admin sans-serif style, and display friendly labels like “November 2026” instead of ISO `2026-11`. Reports header has only the month picker and “Print / Save PDF” side by side; the Export CSV button was removed.
- “Tagalog” toggle switches nav labels, search placeholder, category label and piece count without reloading (saved in localStorage).
- Search and category filtering is live JavaScript — no submit button, no page reload. Client-side pagination shows **10 cards per page**; URL state updates with `history.replaceState`.
- Admin Products, Sales and Expenses lists use server-side pagination at **10 rows per page**.
- Add/Edit Product now follows the original DaREO setup: auto-generated read-only SKU (`PRDCT-000000` from the inserted ID), no Karat/Grams, acquisition date and optional payment dates, photo preview (up to 6), and required markers on necessary fields.

## Configuration & deployment
- `.env` + `.env.example` provide app URL/debug, session/cookie flags, SQLite or MySQL connection, login throttling, and Cloudflare Turnstile keys. `APP_ENV_FILE` can point to a file outside the document root.
- Production checklist, security audit findings, and hosting comparison: see `DEPLOYMENT.md`. SQLite schema auto-init; MySQL uses `database/mysql.sql`.

## Verification performed
- PHP 8.2.12 CLI syntax check: **31/31 `.php` files passed**.
- Automated browser-level HTTP scenario against a disposable SQLite database: **32/32 checks passed** — setup, login, product creation, gallery visibility (sold products hidden, prices never public), installment sale with cost snapshot, downpayment collection, extra payment, Received recognition, expense, P&L figures (revenue ₱200, COGS ₱120, expenses ₱30, net ₱50, 25% margin, ₱100 receivables), CSV export, CSRF rejection, empty-month zeros.
- Visual QA in a real browser: stylesheet loads, hero/filter/card/footer render correctly, and the “Powered by: Loadsss” dialog opens with focus and closes.
- Catalog import QA: gallery renders **110 cards**, **0 broken images**, and only the intentional `Gold Earrings EF 1.03g` name appears twice (two different pieces).
- UI QA: 390px mobile viewport shows the hamburger (nav hidden until toggled), two-column cards, 10 cards per page with page buttons; live search/category filtering changed results without a reload; Tagalog toggle updated labels; admin lists paginated at 10 rows; new product saved with generated SKU `PRDCT-000118` and the new date fields (test record removed).
- Test records were removed before the catalog import; the admin account and any entries created afterwards are untouched.

## Local dev server
`php -S 127.0.0.1:8899 -t . serve.php` then open `http://127.0.0.1:8899/`. `serve.php` is only a local router helper — Apache uses `.htaccess`.
