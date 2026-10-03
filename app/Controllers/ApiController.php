<?php
/**
 * Reseller API v2 Controller
 * Plain PHP SMM Reseller Protocol
 */

require_once __DIR__ . '/../Services/ProviderClient.php';

class ApiController {
    public static function handle(): void {
        header('Content-Type: application/json; charset=utf-8');

        $apiKey = trim($_POST['key'] ?? '');
        $action = trim($_POST['action'] ?? '');

        if (empty($apiKey)) {
            echo json_encode(['error' => 'API key is required']);
            exit;
        }

        // Authenticate user by API key
        $user = Database::fetchOne('SELECT * FROM users WHERE api_key = ? AND status = "active"', [$apiKey]);
        if (!$user) {
            echo json_encode(['error' => 'Invalid or inactive API key']);
            exit;
        }

        switch ($action) {
            case 'services':
                self::services();
                break;
            case 'add':
                self::addOrder($user);
                break;
            case 'status':
                self::orderStatus($user);
                break;
            case 'balance':
                self::userBalance($user);
                break;
            default:
                echo json_encode(['error' => 'Invalid action parameter']);
                exit;
        }
    }

    private static function services(): void {
        $services = Database::fetchAll('
            SELECT s.id as service, s.name, s.type, c.name as category, s.rate, s.min_quantity as min, s.max_quantity as max,
                   IF(s.refill_supported = 1, true, false) as refill,
                   IF(s.cancel_supported = 1, true, false) as cancel,
                   s.description as `desc`
            FROM services s 
            JOIN categories c ON s.category_id = c.id 
            WHERE s.status = "active"
            ORDER BY c.sort_order ASC, s.id ASC
        ');
        echo json_encode($services, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private static function addOrder(array $user): void {
        $serviceId = (int) ($_POST['service'] ?? 0);
        $link = trim($_POST['link'] ?? '');
        $quantity = (int) ($_POST['quantity'] ?? 0);

        if ($serviceId <= 0 || empty($link) || $quantity <= 0) {
            echo json_encode(['error' => 'Missing service, link, or quantity parameter']);
            exit;
        }

        $service = Database::fetchOne('SELECT * FROM services WHERE id = ? AND status = "active"', [$serviceId]);
        if (!$service) {
            echo json_encode(['error' => 'Service not found or inactive']);
            exit;
        }

        if ($quantity < (int)$service['min_quantity'] || $quantity > (int)$service['max_quantity']) {
            echo json_encode(['error' => sprintf('Quantity must be between %d and %d', $service['min_quantity'], $service['max_quantity'])]);
            exit;
        }

        $charge = ($quantity / 1000) * (float) $service['rate'];

        // Begin transaction for balance & order creation
        Database::beginTransaction();
        try {
            // Re-fetch current balance with lock
            $freshUser = Database::fetchOne('SELECT balance FROM users WHERE id = ? FOR UPDATE', [$user['id']]);
            $currentBalance = (float) ($freshUser['balance'] ?? 0);

            if ($currentBalance < $charge) {
                Database::rollBack();
                echo json_encode(['error' => 'Insufficient wallet balance']);
                exit;
            }

            $newBalance = $currentBalance - $charge;
            Database::execute('UPDATE users SET balance = ? WHERE id = ?', [$newBalance, $user['id']]);

            // Insert order
            $orderId = Database::insert('
                INSERT INTO orders (user_id, service_id, link, quantity, charge, status, provider_id, api_order, created_at)
                VALUES (?, ?, ?, ?, ?, "pending", ?, 1, NOW())
            ', [$user['id'], $service['id'], $link, $quantity, $charge, $service['provider_id']]);

            // Insert double-entry ledger transaction
            Database::insert('
                INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, reference_id, description, created_at)
                VALUES (?, "order", ?, ?, ?, ?, ?, NOW())
            ', [$user['id'], -$charge, $currentBalance, $newBalance, (string)$orderId, 'API Order #' . $orderId . ' for ' . $service['name']]);

            // Submit order to upstream provider if mapped
            if (!empty($service['provider_id']) && !empty($service['provider_service_id'])) {
                $provider = Database::fetchOne('SELECT * FROM providers WHERE id = ? AND status = "active"', [$service['provider_id']]);
                if ($provider) {
                    $client = new ProviderClient($provider['api_url'], $provider['api_key']);
                    $providerResp = $client->addOrder($service['provider_service_id'], $link, $quantity);

                    if (!empty($providerResp['order'])) {
                        Database::execute('
                            UPDATE orders 
                            SET provider_order_id = ?, provider_response = ?, status = "processing" 
                            WHERE id = ?
                        ', [(string)$providerResp['order'], json_encode($providerResp), $orderId]);
                    } else {
                        Database::execute('
                            UPDATE orders 
                            SET provider_response = ? 
                            WHERE id = ?
                        ', [json_encode($providerResp), $orderId]);
                    }
                }
            }

            Database::commit();
            log_audit('api_order_create', 'Created API Order #' . $orderId . ' for user ' . $user['username'], $user['id']);

            echo json_encode(['order' => $orderId]);
            exit;
        } catch (Exception $e) {
            Database::rollBack();
            error_log('API order creation error: ' . $e->getMessage());
            echo json_encode(['error' => 'Server error creating order']);
            exit;
        }
    }

    private static function orderStatus(array $user): void {
        $orderId = (int) ($_POST['order'] ?? 0);
        if ($orderId <= 0) {
            echo json_encode(['error' => 'Invalid order ID']);
            exit;
        }

        $order = Database::fetchOne('SELECT * FROM orders WHERE id = ? AND user_id = ?', [$orderId, $user['id']]);
        if (!$order) {
            echo json_encode(['error' => 'Order not found']);
            exit;
        }

        $statusStr = match($order['status']) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
            'partial' => 'Partial',
            'canceled' => 'Canceled',
            'refunded' => 'Refunded',
            default => 'Pending'
        };

        echo json_encode([
            'charge' => number_format((float) $order['charge'], 4, '.', ''),
            'start_count' => (string) $order['start_count'],
            'status' => $statusStr,
            'remains' => (string) $order['remains'],
            'currency' => get_setting('currency', 'USD')
        ]);
        exit;
    }

    private static function userBalance(array $user): void {
        $fresh = Database::fetchOne('SELECT balance FROM users WHERE id = ?', [$user['id']]);
        $bal = (float) ($fresh['balance'] ?? 0);
        echo json_encode([
            'balance' => number_format($bal, 4, '.', ''),
            'currency' => get_setting('currency', 'USD')
        ]);
        exit;
    }
}
