# Ronak Fire India Pvt. Ltd. — website

Static marketing site (HTML / CSS / vanilla JS) with a single PHP form handler,
deployed on Apache / cPanel at <https://ronakfire.com>.

## Structure

```
/                       index.html (home), robots.txt, sitemap.xml, send-mail.php, .htaccess
/pages/                 about-us, contact-us, products (listing), privacy-policy,
                        refund-cancellation-policy, testimonial, 404
/products/              9 product detail pages (?id=N opens a tab)
/css/                   stylesheets
/js/                    site scripts (custom.js) + vendor libs + analytics.js
/images/
  banner/  bg/  favicon/  icons/  pdf/  unit/       shared artwork
  pages/                                            about / contact / testimonial imagery
  services/                                         "why choose us" service imagery
  products/<product>/                               product photography
```

* All internal links and asset paths are **root-relative** (`/css/...`,
  `/products/extinguisher.html`). This keeps the shared header/footer byte-identical
  on every page regardless of folder depth, so pages can be moved without
  touching the shared markup. The site therefore must be served by a web server
  (it will *not* render correctly when opened directly from the file system).
* Old flat URLs (`/about-us.html`, `/extinguisher.html`, …) 301-redirect to the
  current paths — see the redirect block in `.htaccess`.
* Site-wide config lives in `.htaccess` (security headers, CSP, caching,
  compression, redirects) and `js/analytics.js` (GA4 measurement id + events).

## Form handling

`send-mail.php` receives every enquiry form (AJAX via `js/custom.js`, with a
no-JavaScript fallback) and emails the enquiry to the address configured at the
top of the file.

## Conventions

* HTML is formatted with Prettier (`--end-of-line crlf`) so pages stay uniform.
* Structured data is embedded as `application/ld+json` in each page's `<head>`.
