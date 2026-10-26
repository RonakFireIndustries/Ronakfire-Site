<?php

namespace App;

final class PortalAuth
{
    /**
     * Verify the shared portal cookie and return its claims.
     *
     * Supports either a signed HS256 JWT (preferred) or, when portal.profile_url
     * is configured, an opaque session token validated against the portal API.
     */
    public static function claimsFromRequest(): ?array
    {
        $cookieName = (string) config('portal.cookie', 'rf_portal_token');
        $token = $_COOKIE[$cookieName] ?? null;
        if (!is_string($token) || $token === '') {
            return null;
        }

        $claims = self::verifyJwt($token);
        if ($claims !== null) {
            return $claims;
        }

        return self::verifyWithPortal($token);
    }

    public static function verifyJwt(string $token): ?array
    {
        $secret = (string) config('portal.jwt_secret', '');
        if ($secret === '') {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header64, $payload64, $signature64] = $parts;
        $header = json_decode(self::base64UrlDecode($header64), true);
        $payload = json_decode(self::base64UrlDecode($payload64), true);

        if (!is_array($header) || !is_array($payload) || ($header['alg'] ?? '') !== 'HS256') {
            return null;
        }

        $expected = hash_hmac('sha256', $header64 . '.' . $payload64, $secret, true);
        $provided = self::base64UrlDecode($signature64);
        if (!hash_equals($expected, $provided)) {
            return null;
        }

        $now = time();

        // A portal login token must always expire and must be issued for this site.
        $exp = $payload['exp'] ?? null;
        if (!is_numeric($exp) || (int) $exp < $now - 60) {
            return null;
        }
        if (isset($payload['nbf']) && (!is_numeric($payload['nbf']) || (int) $payload['nbf'] > $now + 60)) {
            return null;
        }

        $issuer = (string) config('portal.issuer', '');
        if ($issuer !== '' && (string) ($payload['iss'] ?? '') !== $issuer) {
            return null;
        }

        $audience = config('portal.audience', '');
        if ($audience !== '') {
            $aud = $payload['aud'] ?? null;
            if ($aud === null) {
                return null;
            }
            $aud = is_array($aud) ? $aud : [$aud];
            if (!in_array($audience, $aud, true)) {
                return null;
            }
        }

        // Without a stable portal identifier there is no account to map.
        $portalId = trim((string) ($payload['sub'] ?? $payload['customer_id'] ?? $payload['id'] ?? ''));
        if ($portalId === '') {
            return null;
        }

        return $payload;
    }

    /**
     * Optional fallback for portals that issue an opaque session token.
     * The portal endpoint must return the customer object as JSON.
     */
    private static function verifyWithPortal(string $token): ?array
    {
        $url = (string) config('portal.profile_url', '');
        if ($url === '' || !function_exists('curl_init')) {
            return null;
        }

        $cookieName = (string) config('portal.cookie', 'rf_portal_token');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
                'Cookie: ' . $cookieName . '=' . rawurlencode($token),
            ],
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false || $status !== 200) {
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            return null;
        }
        $data = $data['customer'] ?? $data['user'] ?? $data['data'] ?? $data;
        return is_array($data) ? $data : null;
    }

    public static function loginUrl(string $returnTo = '/account'): string
    {
        $base = (string) config('portal.login_url', 'https://rfipl.ronakfire.com/login');
        $param = (string) config('portal.return_param', 'redirect_uri');
        $separator = str_contains($base, '?') ? '&' : '?';
        return $base . $separator . rawurlencode($param) . '=' . rawurlencode(abs_url($returnTo));
    }

    public static function logoutUrl(): string
    {
        return (string) config('portal.logout_url', 'https://rfipl.ronakfire.com/logout');
    }

    /**
     * Expire the shared cookie from the store side as well, so local logout
     * cannot be undone by the next request reading a still-valid portal cookie.
     */
    public static function clearCookie(): void
    {
        $name = (string) config('portal.cookie', 'rf_portal_token');
        $domain = (string) config('portal.cookie_domain', '');
        $options = [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        if ($domain !== '') {
            $options['domain'] = $domain;
        }
        setcookie($name, '', $options);
    }

    private static function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        return $decoded === false ? '' : $decoded;
    }
}
