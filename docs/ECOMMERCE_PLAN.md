# Ronak Fire B2B Store — implementation plan

Status: phase 1 foundation (working storefront, configurator, cart, orders, admin).

## Decisions (confirmed)

| Area | Decision |
| --- | --- |
| Platform | Custom PHP 8.3 + MySQL on the existing cPanel/Apache host |
| Audience | B2B only — dealers, contractors, institutions |
| Pricing | Prices visible only to authenticated customers (enforced server-side) |
| Checkout | Order now, pay offline / against invoice. No payment gateway in phase 1 |
| Customization | Variant options with price impact, bundles/kits, made-to-measure dimensions |
| Login | `rfipl.ronakfire.com` login, shared cookie on `.ronakfire.com`, signed JWT |
| Accounts | Portal accounts only; the store never stores customer passwords |
| Configurator | Pilot: Extinguishers + Hose Reel Drums. Other 7 categories are catalog + quote |
| Data | Extracted from `images/pdf/*.pdf`; prices are admin-entered placeholders |
| Design | Modern industrial, keep `#f5831f` accent, mobile-first |

## URL map

| URL | Purpose |
| --- | --- |
| `/shop` | Storefront home |
| `/catalog` | All products, search, filters |
| `/catalog/{category}` | Category listing |
| `/product/{slug}` | Product detail + configurator |
| `/cart` | Cart |
| `/checkout` | Order review and placement (no payment) |
| `/order/{order_no}` | Order confirmation / detail |
| `/account` | Customer dashboard, orders, enquiries |
| `/quote` | Request a quote (guest or customer) |
| `/login` | Portal handoff / login-required page |
| `/admin` | Staff admin |

Existing marketing pages (`/`, `/pages/*`, `/products/*`) stay online during phase 1.

## Data model (key tables)

- `categories`, `products`
- `option_groups`, `option_values` — variant pricing
- `bundle_items` — kits
- `customers` — mirror of portal customers
- `carts`, `cart_items`
- `orders`, `order_items`
- `enquiries`
- `admin_users`, `settings`

Prices are stored as integer **paise** to avoid floating point errors. GST rates are
stored as basis points (`1800` = 18%).

Dimension pricing: `unit price = base + Σ option deltas + Σ percent adjustments + area(m²) × rate`.
Area is the external surface area of the box: `2 × (wh + wd + hd)`, in metres.

## Portal login contract

The portal sets an `HttpOnly`, `Secure` cookie on `.ronakfire.com` containing an
HS256 JWT. Required claims:

```json
{
  "iss": "rfipl.ronakfire.com",
  "aud": "ronakfire.com",
  "sub": "portal-customer-id",
  "exp": 1767225600,
  "email": "buyer@example.com",
  "name": "Buyer Name",
  "company": "Buyer Pvt. Ltd.",
  "customer_code": "RF-00042",
  "tier": "dealer",
  "gstin": "27ABCDE1234F1Z5"
}
```

The store verifies signature, issuer, audience and expiry, then upserts the local
customer row. It never reads a password. Configure the shared secret in
`config.local.php` (`portal.jwt_secret`), never in Git.

Login flow:

1. `/login` redirects to `portal.login_url?redirect_uri=<store>/login?portal=1`.
2. The portal authenticates the user and redirects back.
3. The browser sends the shared cookie; the store verifies it and starts a local session.
4. Portal logout clears the cookie; the store's `/logout` clears the local session.

If `portal.jwt_secret` is empty, only local development login works. A production
installer check refuses to enable the store with an empty secret.

## Security

- PDO prepared statements everywhere, no string-interpolated SQL.
- CSRF token on every state-changing form and JSON request.
- Session cookie `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS; ID regenerated on login.
- Prices, option deltas and customer data are never trusted from the browser.
- Output escaped with `e()`; CSP already restricts scripts to same origin.
- `app/`, `storage/`, `config.local.php` and `database/` denied by `.htaccess`.
- Admin password hashed with `password_hash()`; login throttled per session.

## Phase 2 (after this foundation is reviewed)

1. Replace the marketing pages with PHP templates using the new design system.
2. Add the remaining categories to the configurator.
3. Add saved addresses, quote-to-order conversion, reorder, invoice PDFs.
4. Add portal API sync for price tiers and credit limits (instead of local overrides).
5. Add stock/lead-time feed, order notifications, and dispatch tracking.
