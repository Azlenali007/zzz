<?php
/**
 * Real SMM Provider API v2 Client
 * Handles real communication with SMM providers
 */

class ProviderClient {
    private string $apiUrl;
    private string $apiKey;

    public function __construct(string $apiUrl, string $apiKey) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
    }

    private function post(array $data): array {
        $data['key'] = $this->apiKey;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_USERAGENT => 'ApexSMM/1.0',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['error' => 'cURL connection error: ' . $err];
        }

        if (empty($response)) {
            return ['error' => 'Empty response from provider (HTTP ' . $httpCode . ')'];
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            return [
                'error' => 'Invalid JSON received from provider',
                'raw' => substr($response, 0, 500)
            ];
        }

        return $decoded;
    }

    /**
     * Fetch Balance from Provider
     */
    public function getBalance(): array {
        return $this->post(['action' => 'balance']);
    }

    /**
     * Fetch Services from Provider
     */
    public function getServices(): array {
        return $this->post(['action' => 'services']);
    }

    /**
     * Send New Order to Provider
     */
    public function addOrder(string|int $serviceId, string $link, int $quantity, ?int $runs = null, ?int $interval = null): array {
        $payload = [
            'action' => 'add',
            'service' => (string) $serviceId,
            'link' => $link,
            'quantity' => $quantity,
        ];
        if ($runs !== null && $runs > 1) {
            $payload['runs'] = $runs;
            $payload['interval'] = $interval ?? 60;
        }
        return $this->post($payload);
    }

    /**
     * Get Order Status from Provider
     */
    public function getOrderStatus(string|int $orderId): array {
        return $this->post([
            'action' => 'status',
            'order' => (string) $orderId,
        ]);
    }

    /**
     * Request Refill
     */
    public function refillOrder(string|int $orderId): array {
        return $this->post([
            'action' => 'refill',
            'order' => (string) $orderId,
        ]);
    }

    /**
     * Request Cancel
     */
    public function cancelOrder(string|int $orderId): array {
        return $this->post([
            'action' => 'cancel',
            'orders' => (string) $orderId,
        ]);
    }
}
