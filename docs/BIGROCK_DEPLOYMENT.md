# Deploying Ronak Fire on BigRock cPanel

This guide moves the PHP/MySQL store from the local XAMPP checkout to BigRock
shared hosting (cPanel). It assumes the primary domain is `ronakfire.com` and
the site lives directly in `public_html`.

> **Before you start:** take a full local backup (files + database). Keep the
> local copy working until the live site has been tested.

---

## 0. What you need

- BigRock cPanel login (Manage Orders → cPanel).
- The domain's nameservers already pointing at BigRock.
- The local project at `C:\Prasad\Ronakfire Site`.
- XAMPP MySQL running locally (for the export step).
- An FTP client (FileZilla) or cPanel File Manager.
- The production database name, user and password (created in step 2).

Recommended production stack:

| Item | Value |
| --- | --- |
| PHP | 8.2 or 8.3 (minimum 8.0) |
| Database | MySQL 8 / MariaDB 10.4+ |
| Document root | `public_html` |
| App URL | `https://ronakfire.com` |

---

## 1. Set the PHP version and extensions

1. cPanel → **MultiPHP Manager**.
2. Select the domain, choose **PHP 8.2** (or 8.3), click **Apply**.
3. cPanel → **Select PHP Version** / **PHP Extensions** and confirm these are on:
   - `pdo_mysql`, `mysqli`
   - `mbstring`
   - `dom`, `xml`, `libxml`
   - `gd`
   - `fileinfo`
   - `openssl`
   - `zlib`
   - `curl`

`dompdf` (used for quote/invoice PDFs) needs `dom`, `mbstring` and `gd`.

> The root `.htaccess` contains a cPanel-generated `ea-php83` handler block.
> After changing the PHP version in MultiPHP Manager, cPanel may rewrite that
> block. If the site returns **500 Internal Server Error**, delete only the
> block between `# php -- BEGIN cPanel-generated handler` and
> `# php -- END cPanel-generated handler`, then re-apply the PHP version from
> MultiPHP Manager.

---

## 2. Create the MySQL database and user

1. cPanel → **MySQL® Databases**.
2. **Create New Database**: e.g. `ronak_store`.
   cPanel prefixes it with your account, e.g. `a17590e4_ronak_store`.
3. **Add New User**: e.g. `ronak`, with a strong password.
   cPanel creates `a17590e4_ronak`.
4. **Add User to Database** → select the database → grant **ALL PRIVILEGES**.
5. Write down the exact names and password:

```
DB host: localhost
DB name: a17590e4_ronak_store
DB user: a17590e4_ronak
DB pass: <strong password>
```

Do not use the local XAMPP `root` account in production.

---

## 3. Export the local database

The local database is MariaDB 10.4 (`ronak_store`, user `root`, no password).
Use `--result-file` so PowerShell does not change the file encoding.

```powershell
cd "C:\Prasad\Ronakfire Site"

C:\xampp\mysql\bin\mysqldump.exe `
  -u root `
  --default-character-set=utf8mb4 `
  --single-transaction `
  --routines --triggers `
  --no-tablespaces `
  --result-file="C:\Prasad\Ronakfire Site\ronak_store.sql" `
  ronak_store
```

If XAMPP MySQL is not running, start **MySQL** from the XAMPP Control Panel
first.

Alternative: open <http://localhost/phpmyadmin>, select `ronak_store` →
**Export** → **Quick** → format **SQL** → **Export**.

The dump should contain the tables:

```
admin_users, bundle_items, cart_items, carts, categories, customer_addresses,
customers, enquiries, migrations, option_groups, option_values, order_items,
orders, products, settings
```

---

## 4. Prepare the files to upload

Upload the **contents** of the project root into `public_html`, not the folder
itself. Include hidden files (`.htaccess`).

### Include

- `app/`
- `assets/`
- `css/`, `js/`, `images/`
- `vendor/` (dompdf — required unless you run Composer on the server)
- `database/` (schema and migrations, optional but harmless; blocked by `.htaccess`)
- `storage/.htaccess`
- `store.php`
- `.htaccess`
- `robots.txt`, `sitemap.xml`
- `send-mail.php` (only if the legacy static forms are still used)

### Exclude

- `.git/`
- `.freebuff/`
- `app.zip`, `storage.zip`
- `config.local.php` (create a new one on the server)
- `storage/ronak.sqlite`
- `database/database.sqlite`
- `dev-router.php`
- `docs/`
- `legacy/` (old static HTML; `.htaccess` redirects the old URLs to the new pages)
- `bin/` (only needed if you have SSH; otherwise import SQL with phpMyAdmin)
- Any local logs, backups, `.sql` dumps or test scripts

> `vendor/` must be uploaded. Without it, quote/invoice PDF generation fails.
> It is blocked from direct web access by `.htaccess`.
>
> Zips do not preserve empty folders. After extracting, create
> `public_html/storage/quotes/` in File Manager (or let the app create it on the
> first quote request, provided `storage/` is writable).

### Upload options

**FileZilla (recommended for ~47 MB):**

1. Connect with the cPanel FTP account (cPanel → **FTP Accounts**).
2. Enable **Server → Force showing hidden files**.
3. Upload the included files/folders into `public_html`.
4. Overwrite when prompted, but do **not** upload `config.local.php` from local.

**File Manager zip:**

1. Create a zip of only the included files (no `.git`, no local config).
2. cPanel → **File Manager** → `public_html` → **Upload**.
3. Select the zip → **Extract** → delete the zip afterwards.

If the zip is larger than the upload limit, upload it in parts or use FTP.

### Build a clean zip on Windows

This creates a staging copy without the local-only files, then zips it:

```powershell
$src   = "C:\Prasad\Ronakfire Site"
$stage = "$env:TEMP\ronak-deploy"

Remove-Item $stage -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Path $stage | Out-Null

robocopy $src $stage /E `
  /XD .git .freebuff docs legacy bin `
  /XF app.zip storage.zip config.local.php dev-router.php database.sqlite ronak.sqlite *.log

Compress-Archive -Path "$stage\*" -DestinationPath "$env:TEMP\ronak-deploy.zip" -Force
```

`robocopy` exits with code `1` when it copies files successfully; that is normal.

Upload `ronak-deploy.zip` to `public_html`, extract it, then delete the zip.

---

## 5. Import the database

1. cPanel → **phpMyAdmin**.
2. Select the new database in the left sidebar (e.g. `a17590e4_ronak_store`).
3. **Import** tab → **Choose File** → `ronak_store.sql`.
4. Character set: `utf-8` / `utf8mb4`.
5. **Go**.

Verify the row counts match local:

```sql
SELECT 'products', COUNT(*) FROM products
UNION ALL SELECT 'categories', COUNT(*) FROM categories
UNION ALL SELECT 'customers', COUNT(*) FROM customers
UNION ALL SELECT 'orders', COUNT(*) FROM orders
UNION ALL SELECT 'admin_users', COUNT(*) FROM admin_users;
```

Local reference: 28 products, 9 categories, 2 customers, 1 admin user.

If phpMyAdmin rejects the file because of `DEFINER` clauses (usually only for
views/routines), re-export without `--routines` or remove those lines.

---

## 6. Create `config.local.php` on the server

In cPanel File Manager, open `public_html` → **+ File** → name it
`config.local.php`, then **Edit**. Paste:

```php
<?php
/**
 * Production configuration. Never commit this file.
 */
return [
    'app_env' => 'production',
    'app_url' => 'https://ronakfire.com',

    'db' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'a17590e4_ronak_store',
        'user' => 'a17590e4_ronak',
        'password' => 'PUT_THE_DB_PASSWORD_HERE',
    ],

    'portal' => [
        // Leave empty to keep the email + password form on /login.
        // Setting this makes /login redirect straight to rfipl.ronakfire.com.
        'jwt_secret' => '',
        'cookie_domain' => '',
    ],

    'store' => [
        'support_email' => 'sales@ronakfire.com',
        'order_email' => 'sales@ronakfire.com',
        'notify_orders' => true,
    ],

    'mail' => [
        'driver' => 'mail',
        'from' => 'sales@ronakfire.com',
        'from_name' => 'Ronak Fire India',
    ],

    'security' => [
        'dev_login' => false,
    ],
];
```

Replace the database name, user and password with the values from step 2.

> `app_env => production` turns off on-screen PHP errors and ignores
> `security.dev_login`. Keep `jwt_secret` empty unless the customer portal SSO
> is live.

`config.local.php` is blocked by `.htaccess`, but it still contains secrets.
Keep it out of Git and out of any zip you share.

---

## 7. Set permissions

In cPanel File Manager (or FTP):

- Folders: `755`
- Files: `644`
- `storage/` and `storage/quotes/`: `755` (use `775` only if PDFs fail)
- `config.local.php`: `600` or `644`

Do **not** use `777`. On cPanel the PHP process runs as your account user, so
`755` normally allows writes to `storage/quotes`.

---

## 8. Enable SSL and HTTPS

1. cPanel → **SSL/TLS Status** → run **AutoSSL** for `ronakfire.com` and
   `www.ronakfire.com`.
2. Wait until the certificate shows as valid.
3. Confirm `https://ronakfire.com` loads without a warning.
4. Then force HTTPS: in `public_html/.htaccess`, uncomment the block at the
   bottom:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{HTTPS} off
  RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

5. Set `app_url` in `config.local.php` to the final URL (`https://ronakfire.com`
   or `https://www.ronakfire.com` — pick one and redirect the other).

---

## 9. Test the live site

Open each URL and confirm it works:

- [ ] `https://ronakfire.com/` — home page, images, CSS, banner video
- [ ] `/catalog` and a product page
- [ ] Add to cart, change quantity, open `/cart`
- [ ] `/checkout` — saved address buttons fill the form
- [ ] `/quote` — quote request submits
- [ ] `/login` — email/password form appears (not a portal redirect)
- [ ] Sign in, open `/account`, `/account/profile`, `/account/orders`,
      `/account/addresses`, `/account/inventory`, `/account/documents`
- [ ] Edit contact/address on `/account/profile` and save
- [ ] `/admin/login` — admin sign-in
- [ ] Admin order invoice / quote PDF downloads (tests `storage/quotes` write)
- [ ] Contact form sends email
- [ ] Old URLs such as `/about-us.html` 301-redirect to `/about`
- [ ] `http://` redirects to `https://`
- [ ] `/config.local.php`, `/app/`, `/vendor/`, `/storage/` return 403/404
- [ ] `https://ronakfire.com/robots.txt` and `/sitemap.xml` load

### Existing customer accounts

The two local customers were synced from the portal and have **no password
hash**, so they cannot use the password form until a password is set. Create or
reset accounts from **Admin → Customers → New customer** (it generates a
temporary password), or set a hash directly:

```sql
UPDATE customers
SET password_hash = '$2y$10$REPLACE_WITH_A_REAL_HASH',
    password_set_at = NOW()
WHERE email = 'buyer@example.com';
```

Generate the hash locally:

```powershell
php -r "echo password_hash('NewStrongPassword', PASSWORD_DEFAULT), PHP_EOL;"
```

---

## 10. Email delivery

`app/Mailer.php` uses PHP `mail()`, which cPanel routes through the local mail
server.

1. cPanel → **Email Accounts** → create `sales@ronakfire.com` (and
   `no-reply@ronakfire.com` if you use `send-mail.php`).
2. cPanel → **Email Deliverability** → ensure SPF and DKIM are valid for the
   domain.
3. Set `mail.from` to an address on your own domain (not Gmail/Yahoo).
4. Test by submitting the contact form and a quote request, then check the
   mailbox and cPanel → **Track Delivery**.
5. If mail is unreliable, switch to SMTP later (the current `Mailer` falls back
   to `mail()` unless a full SMTP implementation is added).

### What is sent for a quote request

All quote mail is gated by `store.notify_orders`. Keep it `true` in
`config.local.php`, otherwise neither sales nor the customer is emailed
(the enquiry is still saved in Admin → Enquiries).

| Request | To sales | To customer |
|---|---|---|
| `/quote` (single product or general enquiry) | Enquiry details | Acknowledgement, to the email entered on the form |
| `/cart/quote` signed in | Cart lines, totals and the quote PDF | Acknowledgement, to the account/form email |
| `/cart/quote` as a guest | Cart lines, totals and the quote PDF | Acknowledgement, to the email entered on the form |

The customer acknowledgement uses
`app/views/emails/quote-acknowledgement.php` and includes the reference
`RF-ENQ-<id>`. `mail()` returns success once the message is handed to the
local mail server, so confirm real delivery in cPanel → **Track Delivery**
and by checking the mailbox, not from the browser.

---

## 11. Security and cleanup

- [ ] Delete `app.zip`, `storage.zip`, `ronak_store.sql` and any backup files
      from `public_html` after deployment.
- [ ] Confirm `.git/`, `config.local.php`, `storage/*.sqlite` and
      `database/*.sqlite` are **not** on the server.
- [ ] `app/config.php` currently contains live-looking BigRock credentials
      (`a17590e4_...`). Move them to `config.local.php`, replace them in
      `app/config.php` with placeholders, and **rotate the database password**
      because the file is tracked in Git history.
- [ ] Use a strong admin password; do not reuse the local one.
- [ ] Keep `security.dev_login => false` and `app_env => production`.
- [ ] Do not enable directory listing (`.htaccess` already sets
      `Options -Indexes`).
- [ ] Remove or block `bin/` after the migration (`.htaccess` already blocks it).
- [ ] Consider disabling the `install.php` / `migrate.php` scripts once the
      database is live.

The root `.htaccess` already blocks:

```
app, bin, database, docs, legacy, storage, vendor,
config.local.php, config.example.php, dev-router.php,
dotfiles (except .well-known), .sql/.sqlite/.zip/.bak/.log files
```

---

## 12. Backups

1. cPanel → **Backup Wizard** → download a full backup after the site is live.
2. Schedule regular backups (cPanel → **Backup** or your host's backup tool).
3. For a database-only backup, use phpMyAdmin → Export, or a cron job:

```bash
mysqldump -u CPANELUSER_ronak -p'PASSWORD' CPANELUSER_ronak_store \
  | gzip > ~/backups/ronak_store_$(date +%F).sql.gz
```

Keep at least one copy outside the hosting account.

---

## 13. Troubleshooting

| Symptom | Likely cause | Fix |
| --- | --- | --- |
| 500 Internal Server Error | Bad `.htaccess` handler / PHP version | Remove the cPanel PHP handler block, re-apply PHP in MultiPHP Manager |
| 500 on every page | `vendor/autoload.php` missing | Upload `vendor/` |
| “Database connection failed” | Wrong DB name/user/password or host | Recheck `config.local.php`; host is usually `localhost` |
| Home page works, `/catalog` 404 | `mod_rewrite` disabled or `.htaccess` not uploaded | Confirm hidden files were uploaded; ask BigRock to enable `mod_rewrite` |
| `/` returns **403**, but `/store.php` returns 200 | Old cPanel/DirectFWD parking `index.html` in `public_html`, or `DirectoryIndex` still points at it | Delete/rename `public_html/index.html`; keep `DirectoryIndex store.php` and `RewriteRule ^$ store.php` in `.htaccess` |
| `favicon.ico` 404 | `.htaccess` points at `favicon-32.png`, but the file is `favicon-32x32.png` | Use `favicon-32x32.png` in the favicon rewrite rule |
| Mixed-content warning for `cdn.jsinit.directfwd.com` | Browser is still loading the old parking page | Remove the old `index.html`, clear cache, reload in incognito |
| CSS/images missing | Files uploaded into a subfolder | Move contents so `store.php` is directly in `public_html` |
| Blank page | PHP fatal error hidden by `app_env=production` | Temporarily set `app_env=local` or check cPanel **Errors** log |
| Login says “credentials do not match” | Customer has no password hash | Create/reset the customer from Admin |
| Login redirects to portal | `portal.jwt_secret` is set | Set it to `''` in `config.local.php` |
| PDF download fails | `storage/quotes` not writable | Set `storage` and `storage/quotes` to `775`, check ownership |
| Emails not delivered | Missing mailbox / SPF / DKIM | Create mailbox, enable SPF/DKIM, check Track Delivery |
| Session/login drops | `app_url` or cookie domain mismatch | Use one canonical domain and `https` in `app_url` |
| 403 on `/vendor/...` | Expected — vendor is blocked | No action; assets are loaded from `assets/` |

### Root returns 403 but `/store.php` works

This means the PHP app and database are fine; Apache is not rewriting the
document root. The root is a directory, so the `!-d` condition skips the
front-controller rule and Apache falls back to the directory index. BigRock's
custom 403 page is an old DirectFWD parking page, which is why the browser also
reports the `http://cdn.jsinit.directfwd.com/...` mixed-content warning.

In `public_html/.htaccess`:

```apache
Options -Indexes
DirectoryIndex store.php
```

and move the root rule above the `!-f` / `!-d` checks:

```apache
# The root is a directory, so rewrite it before the !-d check.
RewriteRule ^$ store.php [L,QSA]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(shop|catalog|product|cart|checkout|order|account|quote|login|logout|admin|about|contact|privacy|refund-policy)(/.*)?$ store.php [L,QSA]
```

Also delete or rename any old `public_html/index.html` left by the parking
page, then clear the browser cache.

For the favicon, the file is `favicon-32x32.png` (not `favicon-32.png`):

```apache
RewriteRule ^favicon\.ico$ /images/favicon/favicon-32x32.png [L,NC]
```


---

## 14. Deployment checklist

- [ ] PHP 8.2/8.3 selected; extensions enabled
- [ ] MySQL database + user created, privileges granted
- [ ] Local database exported and imported
- [ ] Files uploaded to `public_html` (including `vendor/` and `.htaccess`)
- [ ] Local `config.local.php` **not** uploaded; production one created
- [ ] `storage/quotes` writable
- [ ] SSL active; HTTPS forced; `app_url` updated
- [ ] Login, account pages, checkout, admin, PDFs and email tested
- [ ] Dev files, zips and SQL dumps removed
- [ ] Database password rotated if it was ever committed
- [ ] Backup taken
