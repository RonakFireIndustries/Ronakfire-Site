<?php

namespace App;

use RuntimeException;

final class ApiClient
{
    private string $baseUrl;
    private int $timeout;
    private ?string $accessToken = null;
    private ?string $refreshToken = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('api.base_url', 'https://rfipl-backend.vercel.app'), '/');
        $this->timeout = (int) config('api.timeout', 30);
    }

    public function isEnabled(): bool
    {
        return (bool) config('api.enabled', false);
    }

    public function login(): bool
    {
        $email = (string) config('api.auth.email', '');
        $password = (string) config('api.auth.password', '');
        if ($email === '' || $password === '') {
            return false;
        }

        $response = $this->request('POST', '/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ], false);

        if (isset($response['accessToken'])) {
            $this->accessToken = $response['accessToken'];
            $this->refreshToken = $response['refreshToken'] ?? null;
            return true;
        }

        return false;
    }

    public function get(string $path, array $params = []): array
    {
        if (!$this->accessToken && !$this->login()) {
            throw new RuntimeException('Failed to authenticate with API');
        }

        $url = $this->baseUrl . $path;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $response = $this->rawRequest($url, 'GET');
        return is_array($response) ? $response : [];
    }

    public function post(string $path, array $data = []): array
    {
        if (!$this->accessToken && !$this->login()) {
            throw new RuntimeException('Failed to authenticate with API');
        }
        return $this->request('POST', $path, $data, true);
    }

    public function put(string $path, array $data = []): array
    {
        if (!$this->accessToken && !$this->login()) {
            throw new RuntimeException('Failed to authenticate with API');
        }
        return $this->request('PUT', $path, $data, true);
    }

    public function delete(string $path): array
    {
        if (!$this->accessToken && !$this->login()) {
            throw new RuntimeException('Failed to authenticate with API');
        }
        return $this->request('DELETE', $path, [], true);
    }

    private function request(string $method, string $path, array $data = [], bool $auth = true): array
    {
        $url = $this->baseUrl . $path;
        return $this->rawRequest($url, $method, $data, $auth);
    }

    private function rawRequest(string $url, string $method, array $data = [], bool $auth = true): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $headers = ['Accept: application/json'];
        if ($method !== 'GET') {
            $headers[] = 'Content-Type: application/json';
        }
        if ($auth && $this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method !== 'GET' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($response)) {
            throw new RuntimeException('API request failed');
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid API response');
        }

        if ($httpCode >= 400) {
            throw new RuntimeException($decoded['message'] ?? 'API error: ' . $httpCode);
        }

        return $decoded;
    }
}
