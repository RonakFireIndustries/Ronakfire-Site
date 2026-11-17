<?php

namespace App;

final class Auth
{
    private static ?array $customer = null;
    private static bool $customerResolved = false;
    private static ?array $admin = null;
    private static bool $adminResolved = false;

    public static function customer(): ?array
    {
        if (self::$customerResolved) {
            return self::$customer;
        }
        self::$customerResolved = true;

        $id = isset($_SESSION['customer_id']) ? (int) $_SESSION['customer_id'] : 0;
        if ($id > 0) {
            $row = db()->one(
                'SELECT * FROM customers WHERE id = ? AND is_active = 1',
                [$id]
            );
            if ($row) {
                self::$customer = $row;
                return self::$customer;
            }
            unset($_SESSION['customer_id']);
        }

        $claims = PortalAuth::claimsFromRequest();
        if ($claims !== null) {
            self::$customer = self::syncCustomer($claims);
        }

        return self::$customer;
    }

    /**
     * Create or refresh the local mirror of a portal customer.
     */
    public static function syncCustomer(array $claims): ?array
    {
        $portalId = trim((string) ($claims['sub'] ?? $claims['customer_id'] ?? $claims['id'] ?? ''));
        if ($portalId === '') {
            return null;
        }

        $email = trim((string) ($claims['email'] ?? ''));
        $company = trim((string) ($claims['company'] ?? $claims['company_name'] ?? ''));
        $name = trim((string) ($claims['name'] ?? $claims['contact_name'] ?? ''));
        $now = date('Y-m-d H:i:s');

        $existing = db()->one('SELECT * FROM customers WHERE portal_id = ?', [$portalId]);
        if (!$existing && $email !== '') {
            $existing = db()->one('SELECT * FROM customers WHERE email = ?', [$email]);
        }

        $fields = [
            'portal_id' => $portalId,
            'customer_code' => trim((string) ($claims['customer_code'] ?? ($existing['customer_code'] ?? ''))),
            'company_name' => $company !== '' ? $company : ($existing['company_name'] ?? 'Customer'),
            'contact_name' => $name !== '' ? $name : ($existing['contact_name'] ?? ''),
            'email' => $email !== '' ? $email : ($existing['email'] ?? ''),
            'phone' => trim((string) ($claims['phone'] ?? ($existing['phone'] ?? ''))),
            'gstin' => strtoupper(trim((string) ($claims['gstin'] ?? ($existing['gstin'] ?? '')))),
            'price_tier' => trim((string) ($claims['tier'] ?? ($existing['price_tier'] ?? 'standard'))),
            'last_login_at' => $now,
            'updated_at' => $now,
        ];

        if ($existing) {
            if ((int) $existing['is_active'] !== 1) {
                return null;
            }
            db()->update('customers', $fields, 'id = :id', ['id' => $existing['id']]);
            $id = (int) $existing['id'];
        } else {
            $fields['created_at'] = $now;
            $fields['is_active'] = 1;
            $fields['discount_bp'] = 0;
            $fields['credit_days'] = 0;
            $fields['credit_limit_paise'] = 0;
            $id = db()->insert('customers', $fields);
        }

        self::startCustomerSession($id);
        return db()->one('SELECT * FROM customers WHERE id = ?', [$id]);
    }

    private static function startCustomerSession(int $id): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['customer_id'] = $id;
        self::$customer = null;
        self::$customerResolved = false;
    }

    public static function logout(): void
    {
        unset($_SESSION['customer_id']);
        self::$customer = null;
        self::$customerResolved = true;
    }

    /* ---------------------------------------------------------------------
       Admin
       --------------------------------------------------------------------- */

    public static function admin(): ?array
    {
        if (self::$adminResolved) {
            return self::$admin;
        }
        self::$adminResolved = true;

        $id = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;
        if ($id > 0) {
            $row = db()->one('SELECT * FROM admin_users WHERE id = ? AND is_active = 1', [$id]);
            self::$admin = $row ?: null;
        }
        return self::$admin;
    }

    public static function attemptAdmin(string $email, string $password): bool
    {
        $user = db()->one('SELECT * FROM admin_users WHERE email = ? AND is_active = 1', [strtolower($email)]);
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $user['id'];
        self::$admin = $user;
        self::$adminResolved = true;
        db()->update('admin_users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
        return true;
    }

    public static function logoutAdmin(): void
    {
        unset($_SESSION['admin_id']);
        self::$admin = null;
        self::$adminResolved = true;
    }

    public static function requireAdmin(): void
    {
        if (!self::admin()) {
            redirect('/admin/login');
        }
    }

    /**
     * Sales users are admin_users with the 'sales' role. They can work with
     * the customers assigned to them, but not manage the catalog or staff.
     */
    public static function isSales(): bool
    {
        $admin = self::admin();
        return $admin !== null && ($admin['role'] ?? '') === 'sales';
    }
}
