<?php
/**
 * ApexSMM Automated Order Synchronizer (Cron Engine)
 * Plain PHP CLI / Web Runner
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/Services/ProviderClient.php';

// Check if running via CLI or authorized request
$isCli = (php_sapi_name() === 'cli');

// Update last run timestamp
Database::execute('INSERT INTO settings (key_name, key_value) VALUES ("cron_last_run", NOW()) ON DUPLICATE KEY UPDATE key_value = NOW()');

// Fetch pending/in-progress orders mapped to providers
$pendingOrders = Database::fetchAll('
    SELECT o.*, p.api_url, p.api_key 
    FROM orders o 
    JOIN providers p ON o.provider_id = p.id 
    WHERE o.provider_order_id IS NOT NULL 
      AND o.status IN ("pending", "processing", "in_progress")
    LIMIT 50
');

$updatedCount = 0;

foreach ($pendingOrders as $order) {
    try {
        $client = new ProviderClient($order['api_url'], $order['api_key']);
        $resp = $client->getOrderStatus($order['provider_order_id']);

        if (!empty($resp['status'])) {
            $rawStatus = strtolower(trim($resp['status']));
            $mappedStatus = match($rawStatus) {
                'completed' => 'completed',
                'processing' => 'processing',
                'in progress', 'inprogress' => 'in_progress',
                'partial' => 'partial',
                'canceled', 'cancelled' => 'canceled',
                'refunded' => 'refunded',
                default => $order['status']
            };

            $startCount = isset($resp['start_count']) ? (int) $resp['start_count'] : (int) $order['start_count'];
            $remains = isset($resp['remains']) ? (int) $resp['remains'] : (int) $order['remains'];

            Database::execute('
                UPDATE orders 
                SET status = ?, start_count = ?, remains = ?, provider_response = ?, updated_at = NOW() 
                WHERE id = ?
            ', [$mappedStatus, $startCount, $remains, json_encode($resp), $order['id']]);

            $updatedCount++;
        }
    } catch (Exception $e) {
        error_log('Cron order sync failure for order #' . $order['id'] . ': ' . $e->getMessage());
    }
}

log_audit('cron_sync', 'Automated cron checked ' . count($pendingOrders) . ' orders, updated ' . $updatedCount);

if ($isCli) {
    echo "Cron execution finished: checked " . count($pendingOrders) . " orders, updated " . $updatedCount . "\n";
}
