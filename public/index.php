<?php
/**
 * ApexSMM Front Controller & Router
 * Plain PHP & MySQL Architecture
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Services/ProviderClient.php';
require_once __DIR__ . '/../app/Controllers/ApiController.php';

// Parse Request URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestUri = rtrim($requestUri, '/');
if (empty($requestUri)) {
    $requestUri = '/';
}
$method = $_SERVER['REQUEST_METHOD'];

// If installer is not completed, force redirect to /install (unless calling install routes)
if (!is_installed() && !str_starts_with($requestUri, '/install') && !str_starts_with($requestUri, '/dist')) {
    redirect('/install');
}

// Maintenance Mode Check
if (get_setting('maintenance_mode') === '1' && !is_admin() && !str_starts_with($requestUri, '/login') && !str_starts_with($requestUri, '/dist') && !str_starts_with($requestUri, '/admin')) {
    http_response_code(503);
    echo '<!DOCTYPE html><html><head><title>Under Maintenance</title><link rel="stylesheet" href="/dist/assets/style.css"></head><body class="bg-[#070913] text-white flex items-center justify-center min-h-screen text-center p-6"><div class="max-w-md p-8 rounded-3xl bg-[#0e1326] border border-slate-800 shadow-2xl"><h1 class="text-2xl font-bold mb-2">Scheduled Maintenance</h1><p class="text-sm text-slate-400">Our platform is temporarily offline for database optimization. Please check back shortly.</p></div></body></html>';
    exit;
}

// -------------------------------------------------------------
// Reseller API Endpoint
// -------------------------------------------------------------
if ($requestUri === '/api/v2' || $requestUri === '/api/v1') {
    ApiController::handle();
    exit;
}

// -------------------------------------------------------------
// Installer Routes
// -------------------------------------------------------------
if ($requestUri === '/install') {
    require_once VIEWS_PATH . '/installer/install.php';
    exit;
}

if ($requestUri === '/install/test-db' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $host = $input['host'] ?? '127.0.0.1';
    $port = $input['port'] ?? '3306';
    $name = $input['name'] ?? '';
    $user = $input['user'] ?? '';
    $pass = $input['pass'] ?? '';

    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo json_encode(['success' => true, 'message' => 'MySQL connection established successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
    }
    exit;
}

if ($requestUri === '/install/execute' && $method === 'POST') {
    if (is_installed()) {
        set_flash('error', 'Installation is locked.');
        redirect('/login');
    }

    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminEmail = trim($_POST['admin_email'] ?? 'admin@apexsmm.com');
    $adminPass = $_POST['admin_pass'] ?? '';

    if (empty($adminPass) || strlen($adminPass) < 6) {
        set_flash('error', 'Password must be at least 6 characters.');
        redirect('/install');
    }

    try {
        // Execute schema and defaults if tables missing
        $schema = file_get_contents(CONFIG_PATH . '/schema.sql');
        Database::getConnection()->exec($schema);

        $defaults = file_get_contents(CONFIG_PATH . '/defaults.sql');
        Database::getConnection()->exec($defaults);

        // Create or update admin account
        $hash = password_hash($adminPass, PASSWORD_BCRYPT);
        $apiKey = 'apex_sec_' . bin2hex(random_bytes(16));
        Database::execute('
            INSERT INTO users (username, email, password_hash, role, balance, api_key, status)
            VALUES (?, ?, ?, "admin", 0.0000, ?, "active")
            ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = "admin"
        ', [$adminUser, $adminEmail, $hash, $apiKey]);

        // Create lock file
        file_put_contents(CONFIG_PATH . '/installed.lock', json_encode(['installed_at' => date('c')]));

        set_flash('success', 'ApexSMM installed successfully. You can now sign in with your admin credentials.');
        redirect('/login');
    } catch (Exception $e) {
        set_flash('error', 'Installation error: ' . $e->getMessage());
        redirect('/install');
    }
}

// -------------------------------------------------------------
// Public Routes
// -------------------------------------------------------------
if ($requestUri === '/') {
    require_once VIEWS_PATH . '/public/home.php';
    exit;
}

if ($requestUri === '/services') {
    require_once VIEWS_PATH . '/public/services.php';
    exit;
}

if ($requestUri === '/how-it-works') {
    require_once VIEWS_PATH . '/public/how_it_works.php';
    exit;
}

if ($requestUri === '/faq') {
    require_once VIEWS_PATH . '/public/faq.php';
    exit;
}

if ($requestUri === '/contact') {
    if ($method === 'POST') {
        if (!csrf_verify()) {
            set_flash('error', 'Security token mismatch. Please try again.');
            redirect('/contact');
        }
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? 'Website Inquiry');
        $msg = trim($_POST['message'] ?? '');

        // Store notification for admins
        Database::insert('
            INSERT INTO notifications (user_id, title, message, type, created_at)
            VALUES (NULL, ?, ?, "info", NOW())
        ', ['New Contact Message: ' . $subject, "From: {$name} ({$email})\n\n{$msg}"]);

        set_flash('success', 'Thank you! Your inquiry has been received. Our team will contact you shortly.');
        redirect('/contact');
    }
    require_once VIEWS_PATH . '/public/contact.php';
    exit;
}

// -------------------------------------------------------------
// Authentication Routes
// -------------------------------------------------------------
if ($requestUri === '/login') {
    if (is_logged_in()) {
        redirect('/dashboard');
    }

    if ($method === 'POST') {
        if (!csrf_verify()) {
            set_flash('error', 'Security token invalid. Please refresh.');
            redirect('/login');
        }

        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = Database::fetchOne('SELECT * FROM users WHERE (username = ? OR email = ?)', [$login, $login]);

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] === 'banned') {
                set_flash('error', 'Your account has been suspended. Please contact support.');
                redirect('/login');
            }

            // Regenerate session ID for security
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_role'] = $user['role'];

            log_audit('user_login', 'User logged in successfully', $user['id']);
            set_flash('success', 'Welcome back, ' . $user['username'] . '!');

            if ($user['role'] === 'admin') {
                redirect('/admin');
            }
            redirect('/dashboard');
        } else {
            set_flash('error', 'Invalid login credentials.');
            redirect('/login');
        }
    }

    require_once VIEWS_PATH . '/public/login.php';
    exit;
}

if ($requestUri === '/register') {
    if (is_logged_in()) {
        redirect('/dashboard');
    }

    if (get_setting('registration_enabled', '1') !== '1') {
        set_flash('error', 'New user registrations are currently disabled by administrator.');
        redirect('/login');
    }

    if ($method === 'POST') {
        if (!csrf_verify()) {
            set_flash('error', 'Security token mismatch. Please try again.');
            redirect('/register');
        }

        $username = strtolower(trim($_POST['username'] ?? ''));
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';

        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            set_flash('error', 'Username must be 3-30 alphanumeric characters or underscores.');
            redirect('/register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Please provide a valid email address.');
            redirect('/register');
        }

        if (strlen($password) < 6) {
            set_flash('error', 'Password must be at least 6 characters.');
            redirect('/register');
        }

        if ($password !== $confirm) {
            set_flash('error', 'Passwords do not match.');
            redirect('/register');
        }

        // Check if username or email already exists
        $exists = Database::fetchOne('SELECT id FROM users WHERE username = ? OR email = ?', [$username, $email]);
        if ($exists) {
            set_flash('error', 'A user with that username or email address already exists.');
            redirect('/register');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $apiKey = 'apex_sec_' . bin2hex(random_bytes(16));

        $userId = Database::insert('
            INSERT INTO users (username, email, password_hash, role, balance, api_key, status, created_at)
            VALUES (?, ?, ?, "user", 0.0000, ?, "active", NOW())
        ', [$username, $email, $hash, $apiKey]);

        // Auto login
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = 'user';

        log_audit('user_register', 'New user registered account: ' . $username, $userId);

        // Welcome notification
        Database::insert('
            INSERT INTO notifications (user_id, title, message, type, created_at)
            VALUES (?, "Welcome to ApexSMM!", "Your account is active. Top up your wallet balance to begin submitting high-speed orders.", "success", NOW())
        ', [$userId]);

        set_flash('success', 'Account created successfully! Welcome to ApexSMM.');
        redirect('/dashboard');
    }

    require_once VIEWS_PATH . '/public/register.php';
    exit;
}

if ($requestUri === '/logout') {
    if (is_logged_in()) {
        log_audit('user_logout', 'User signed out', $_SESSION['user_id']);
    }
    unset($_SESSION['user_id'], $_SESSION['user_role']);
    session_destroy();
    redirect('/login');
}

// -------------------------------------------------------------
// User Client Routes (Protected)
// -------------------------------------------------------------
if ($requestUri === '/dashboard') {
    require_login();
    require_once VIEWS_PATH . '/user/dashboard.php';
    exit;
}

if ($requestUri === '/new-order') {
    require_login();
    $u = auth_user();

    if ($method === 'POST') {
        if (!csrf_verify()) {
            set_flash('error', 'Security token invalid.');
            redirect('/new-order');
        }

        $serviceId = (int) ($_POST['service_id'] ?? 0);
        $link = trim($_POST['link'] ?? '');
        $quantity = (int) ($_POST['quantity'] ?? 0);

        if ($serviceId <= 0 || empty($link) || $quantity <= 0) {
            set_flash('error', 'Please fill in all order details.');
            redirect('/new-order');
        }

        $service = Database::fetchOne('SELECT * FROM services WHERE id = ? AND status = "active"', [$serviceId]);
        if (!$service) {
            set_flash('error', 'Selected service is no longer available.');
            redirect('/new-order');
        }

        if ($quantity < (int)$service['min_quantity'] || $quantity > (int)$service['max_quantity']) {
            set_flash('error', sprintf('Quantity must be between %d and %d.', $service['min_quantity'], $service['max_quantity']));
            redirect('/new-order');
        }

        $charge = ($quantity / 1000) * (float) $service['rate'];

        // Begin transaction
        Database::beginTransaction();
        try {
            $fresh = Database::fetchOne('SELECT balance FROM users WHERE id = ? FOR UPDATE', [$u['id']]);
            $currentBalance = (float) ($fresh['balance'] ?? 0);

            if ($currentBalance < $charge) {
                Database::rollBack();
                set_flash('error', 'Insufficient wallet balance. Please add funds to proceed.');
                redirect('/new-order');
            }

            $newBalance = $currentBalance - $charge;
            Database::execute('UPDATE users SET balance = ? WHERE id = ?', [$newBalance, $u['id']]);

            // Create order
            $orderId = Database::insert('
                INSERT INTO orders (user_id, service_id, link, quantity, charge, status, provider_id, created_at)
                VALUES (?, ?, ?, ?, ?, "pending", ?, NOW())
            ', [$u['id'], $service['id'], $link, $quantity, $charge, $service['provider_id']]);

            // Record transaction
            Database::insert('
                INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, reference_id, description, created_at)
                VALUES (?, "order", ?, ?, ?, ?, ?, NOW())
            ', [$u['id'], -$charge, $currentBalance, $newBalance, (string)$orderId, 'Order #' . $orderId . ' - ' . $service['name']]);

            // Submit to provider API if mapped
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
            log_audit('order_placed', 'Placed Order #' . $orderId . ' for ' . $service['name'], $u['id']);

            set_flash('success', 'Order #' . $orderId . ' placed successfully!');
            redirect('/orders');
        } catch (Exception $e) {
            Database::rollBack();
            set_flash('error', 'Error placing order: ' . $e->getMessage());
            redirect('/new-order');
        }
    }

    require_once VIEWS_PATH . '/user/new_order.php';
    exit;
}

if ($requestUri === '/orders') {
    require_login();
    require_once VIEWS_PATH . '/user/orders.php';
    exit;
}

if ($requestUri === '/orders/refill' && $method === 'POST') {
    require_login();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/orders');
    }

    $u = auth_user();
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $order = Database::fetchOne('SELECT o.*, p.api_url, p.api_key FROM orders o LEFT JOIN providers p ON o.provider_id = p.id WHERE o.id = ? AND o.user_id = ?', [$orderId, $u['id']]);

    if (!$order) {
        set_flash('error', 'Order not found.');
        redirect('/orders');
    }

    if (!empty($order['provider_id']) && !empty($order['provider_order_id'])) {
        $client = new ProviderClient($order['api_url'], $order['api_key']);
        $resp = $client->refillOrder($order['provider_order_id']);
        if (!empty($resp['refill'])) {
            Database::execute('UPDATE orders SET refill_status = "refill_sent" WHERE id = ?', [$orderId]);
            set_flash('success', 'Refill request submitted to provider successfully.');
        } else {
            set_flash('error', 'Provider response: ' . ($resp['error'] ?? 'Refill unavailable at this time.'));
        }
    } else {
        Database::execute('UPDATE orders SET refill_status = "refill_requested" WHERE id = ?', [$orderId]);
        set_flash('success', 'Refill request submitted to support staff.');
    }
    redirect('/orders');
}

if ($requestUri === '/orders/cancel' && $method === 'POST') {
    require_login();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/orders');
    }

    $u = auth_user();
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $order = Database::fetchOne('SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = "pending"', [$orderId, $u['id']]);

    if (!$order) {
        set_flash('error', 'Only pending orders can be canceled.');
        redirect('/orders');
    }

    Database::beginTransaction();
    try {
        $refundAmount = (float) $order['charge'];
        $fresh = Database::fetchOne('SELECT balance FROM users WHERE id = ? FOR UPDATE', [$u['id']]);
        $currBal = (float) $fresh['balance'];
        $newBal = $currBal + $refundAmount;

        Database::execute('UPDATE users SET balance = ? WHERE id = ?', [$newBal, $u['id']]);
        Database::execute('UPDATE orders SET status = "canceled" WHERE id = ?', [$orderId]);

        Database::insert('
            INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, reference_id, description, created_at)
            VALUES (?, "refund", ?, ?, ?, ?, ?, NOW())
        ', [$u['id'], $refundAmount, $currBal, $newBal, (string)$orderId, 'Refund for canceled order #' . $orderId]);

        Database::commit();
        set_flash('success', 'Order #' . $orderId . ' canceled and ' . format_currency($refundAmount) . ' refunded to your wallet.');
    } catch (Exception $e) {
        Database::rollBack();
        set_flash('error', 'Cancellation error: ' . $e->getMessage());
    }
    redirect('/orders');
}

if ($requestUri === '/add-funds') {
    require_login();
    $u = auth_user();

    if ($method === 'POST') {
        if (!csrf_verify()) {
            set_flash('error', 'Security token error.');
            redirect('/add-funds');
        }

        $gatewayCode = trim($_POST['gateway'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $txid = trim($_POST['transaction_id'] ?? '');

        $gw = Database::fetchOne('SELECT * FROM payment_gateways WHERE code = ? AND status = "active"', [$gatewayCode]);
        if (!$gw) {
            set_flash('error', 'Selected payment gateway is unavailable.');
            redirect('/add-funds');
        }

        if ($amount < (float)$gw['min_amount'] || $amount > (float)$gw['max_amount']) {
            set_flash('error', sprintf('Amount must be between %s and %s.', format_currency($gw['min_amount']), format_currency($gw['max_amount'])));
            redirect('/add-funds');
        }

        // Handle proof upload if provided
        $proofPath = null;
        if (!empty($_FILES['proof']['name']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'pdf'])) {
                $filename = 'proof_' . $u['id'] . '_' . time() . '.' . $ext;
                $target = PUBLIC_PATH . '/uploads/' . $filename;
                if (move_uploaded_file($_FILES['proof']['tmp_name'], $target)) {
                    $proofPath = '/uploads/' . $filename;
                }
            }
        }

        $feePercent = (float) $gw['fee_percent'];
        $feeAmount = ($amount * $feePercent) / 100;

        $paymentId = Database::insert('
            INSERT INTO payments (user_id, gateway, amount, fee, transaction_id, proof_image, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, "pending", NOW())
        ', [$u['id'], $gw['code'], $amount, $feeAmount, $txid, $proofPath]);

        log_audit('payment_submitted', 'Submitted deposit request #' . $paymentId . ' of ' . format_currency($amount), $u['id']);

        set_flash('success', 'Deposit request #' . $paymentId . ' submitted. Funds will be credited once verified by staff.');
        redirect('/add-funds');
    }

    require_once VIEWS_PATH . '/user/add_funds.php';
    exit;
}

if ($requestUri === '/tickets') {
    require_login();
    require_once VIEWS_PATH . '/user/tickets.php';
    exit;
}

if ($requestUri === '/tickets/create' && $method === 'POST') {
    require_login();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/tickets');
    }

    $u = auth_user();
    $subject = trim($_POST['subject'] ?? '');
    $priority = strtolower(trim($_POST['priority'] ?? 'medium'));
    $message = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($message)) {
        set_flash('error', 'Please provide subject and message.');
        redirect('/tickets');
    }

    Database::beginTransaction();
    try {
        $ticketId = Database::insert('
            INSERT INTO tickets (user_id, subject, priority, status, created_at)
            VALUES (?, ?, ?, "open", NOW())
        ', [$u['id'], $subject, in_array($priority, ['low', 'medium', 'high']) ? $priority : 'medium']);

        Database::insert('
            INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message, created_at)
            VALUES (?, ?, 0, ?, NOW())
        ', [$ticketId, $u['id'], $message]);

        Database::commit();
        set_flash('success', 'Support ticket #' . $ticketId . ' opened.');
        redirect('/tickets/view?id=' . $ticketId);
    } catch (Exception $e) {
        Database::rollBack();
        set_flash('error', 'Error creating ticket: ' . $e->getMessage());
        redirect('/tickets');
    }
}

if ($requestUri === '/tickets/view') {
    require_login();
    $u = auth_user();
    $ticketId = (int) ($_GET['id'] ?? 0);

    $ticket = Database::fetchOne('SELECT * FROM tickets WHERE id = ? AND user_id = ?', [$ticketId, $u['id']]);
    if (!$ticket) {
        set_flash('error', 'Ticket not found.');
        redirect('/tickets');
    }

    $messages = Database::fetchAll('SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY id ASC', [$ticketId]);
    require_once VIEWS_PATH . '/user/ticket_view.php';
    exit;
}

if ($requestUri === '/tickets/reply' && $method === 'POST') {
    require_login();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/tickets');
    }

    $u = auth_user();
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    $ticket = Database::fetchOne('SELECT * FROM tickets WHERE id = ? AND user_id = ?', [$ticketId, $u['id']]);
    if (!$ticket || empty($message)) {
        set_flash('error', 'Invalid ticket or empty message.');
        redirect('/tickets');
    }

    Database::insert('
        INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message, created_at)
        VALUES (?, ?, 0, ?, NOW())
    ', [$ticketId, $u['id'], $message]);

    Database::execute('UPDATE tickets SET status = "user_reply", updated_at = NOW() WHERE id = ?', [$ticketId]);

    set_flash('success', 'Reply posted.');
    redirect('/tickets/view?id=' . $ticketId);
}

if ($requestUri === '/notifications') {
    require_login();
    require_once VIEWS_PATH . '/user/notifications.php';
    exit;
}

if ($requestUri === '/profile') {
    require_login();
    require_once VIEWS_PATH . '/user/profile.php';
    exit;
}

if ($requestUri === '/profile/change-password' && $method === 'POST') {
    require_login();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/profile');
    }

    $u = auth_user();
    $curr = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($curr, $u['password_hash'])) {
        set_flash('error', 'Current password is incorrect.');
        redirect('/profile');
    }

    if (strlen($new) < 6 || $new !== $confirm) {
        set_flash('error', 'New passwords must match and be at least 6 characters.');
        redirect('/profile');
    }

    $hash = password_hash($new, PASSWORD_BCRYPT);
    Database::execute('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $u['id']]);

    set_flash('success', 'Password updated successfully.');
    redirect('/profile');
}

if ($requestUri === '/profile/regenerate-api' && $method === 'POST') {
    require_login();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/profile');
    }

    $u = auth_user();
    $newKey = 'apex_sec_' . bin2hex(random_bytes(16));
    Database::execute('UPDATE users SET api_key = ? WHERE id = ?', [$newKey, $u['id']]);

    set_flash('success', 'New API key generated successfully.');
    redirect('/profile');
}

if ($requestUri === '/api-docs') {
    require_once VIEWS_PATH . '/user/api_docs.php';
    exit;
}

// -------------------------------------------------------------
// Admin Routes (Protected - Role: Admin)
// -------------------------------------------------------------
if ($requestUri === '/admin') {
    require_admin();
    require_once VIEWS_PATH . '/admin/dashboard.php';
    exit;
}

if ($requestUri === '/admin/users') {
    require_admin();
    require_once VIEWS_PATH . '/admin/users.php';
    exit;
}

if ($requestUri === '/admin/users/adjust-balance' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/users');
    }

    $targetUserId = (int) ($_POST['user_id'] ?? 0);
    $type = $_POST['type'] ?? 'add';
    $amount = (float) ($_POST['amount'] ?? 0);
    $reason = trim($_POST['reason'] ?? 'Admin adjustment');

    if ($targetUserId <= 0 || $amount <= 0) {
        set_flash('error', 'Invalid user or adjustment amount.');
        redirect('/admin/users');
    }

    Database::beginTransaction();
    try {
        $targetUser = Database::fetchOne('SELECT balance FROM users WHERE id = ? FOR UPDATE', [$targetUserId]);
        if (!$targetUser) {
            throw new Exception('User not found');
        }

        $currentBal = (float) $targetUser['balance'];
        $diff = ($type === 'add') ? $amount : -$amount;
        $newBal = $currentBal + $diff;
        if ($newBal < 0) $newBal = 0.0000;

        Database::execute('UPDATE users SET balance = ? WHERE id = ?', [$newBal, $targetUserId]);

        Database::insert('
            INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, reference_id, description, created_at)
            VALUES (?, "admin_adjustment", ?, ?, ?, ?, ?, NOW())
        ', [$targetUserId, $diff, $currentBal, $newBal, 'ADJ_' . time(), $reason]);

        Database::commit();
        log_audit('balance_adjusted', sprintf('Adjusted user #%d balance by %s (%s)', $targetUserId, ($diff >= 0 ? '+' : '') . $diff, $reason));

        set_flash('success', 'User balance updated.');
    } catch (Exception $e) {
        Database::rollBack();
        set_flash('error', 'Adjustment failed: ' . $e->getMessage());
    }
    redirect('/admin/users');
}

if ($requestUri === '/admin/users/toggle-status' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/users');
    }

    $targetUserId = (int) ($_POST['user_id'] ?? 0);
    $u = Database::fetchOne('SELECT status FROM users WHERE id = ?', [$targetUserId]);
    if ($u) {
        $newStatus = ($u['status'] === 'active') ? 'banned' : 'active';
        Database::execute('UPDATE users SET status = ? WHERE id = ?', [$newStatus, $targetUserId]);
        log_audit('user_status_changed', "User #{$targetUserId} status changed to {$newStatus}");
        set_flash('success', "User status updated to {$newStatus}.");
    }
    redirect('/admin/users');
}

if ($requestUri === '/admin/categories') {
    require_admin();
    require_once VIEWS_PATH . '/admin/categories.php';
    exit;
}

if ($requestUri === '/admin/categories/create' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/categories');
    }

    $name = trim($_POST['name'] ?? '');
    $sort = (int) ($_POST['sort_order'] ?? 0);

    if (!empty($name)) {
        Database::insert('INSERT INTO categories (name, sort_order, status, created_at) VALUES (?, ?, "active", NOW())', [$name, $sort]);
        set_flash('success', 'Category created.');
    }
    redirect('/admin/categories');
}

if ($requestUri === '/admin/categories/update' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/categories');
    }

    $id = (int) ($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $sort = (int) ($_POST['sort_order'] ?? 0);
    $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

    if ($id > 0 && !empty($name)) {
        Database::execute('UPDATE categories SET name = ?, sort_order = ?, status = ? WHERE id = ?', [$name, $sort, $status, $id]);
        set_flash('success', 'Category updated.');
    }
    redirect('/admin/categories');
}

if ($requestUri === '/admin/categories/delete' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/categories');
    }

    $id = (int) ($_POST['category_id'] ?? 0);
    Database::execute('DELETE FROM categories WHERE id = ?', [$id]);
    set_flash('success', 'Category deleted.');
    redirect('/admin/categories');
}

if ($requestUri === '/admin/services') {
    require_admin();
    require_once VIEWS_PATH . '/admin/services.php';
    exit;
}

if ($requestUri === '/admin/services/create' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/services');
    }

    $catId = (int) ($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $rate = (float) ($_POST['rate'] ?? 0);
    $origRate = (float) ($_POST['original_rate'] ?? 0);
    $min = (int) ($_POST['min_quantity'] ?? 10);
    $max = (int) ($_POST['max_quantity'] ?? 10000);
    $providerId = !empty($_POST['provider_id']) ? (int) $_POST['provider_id'] : null;
    $providerServiceId = trim($_POST['provider_service_id'] ?? '') ?: null;
    $refill = !empty($_POST['refill_supported']) ? 1 : 0;
    $cancel = !empty($_POST['cancel_supported']) ? 1 : 0;
    $desc = trim($_POST['description'] ?? '');

    if ($catId > 0 && !empty($name) && $rate > 0) {
        Database::insert('
            INSERT INTO services (category_id, name, rate, original_rate, min_quantity, max_quantity, provider_id, provider_service_id, refill_supported, cancel_supported, description, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "active", NOW())
        ', [$catId, $name, $rate, $origRate, $min, $max, $providerId, $providerServiceId, $refill, $cancel, $desc]);

        set_flash('success', 'Service created successfully.');
    } else {
        set_flash('error', 'Please enter valid service details and rate.');
    }
    redirect('/admin/services');
}

if ($requestUri === '/admin/services/update' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/services');
    }

    $id = (int) ($_POST['service_id'] ?? 0);
    $catId = (int) ($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $rate = (float) ($_POST['rate'] ?? 0);
    $origRate = (float) ($_POST['original_rate'] ?? 0);
    $min = (int) ($_POST['min_quantity'] ?? 10);
    $max = (int) ($_POST['max_quantity'] ?? 10000);
    $providerId = !empty($_POST['provider_id']) ? (int) $_POST['provider_id'] : null;
    $providerServiceId = trim($_POST['provider_service_id'] ?? '') ?: null;
    $refill = !empty($_POST['refill_supported']) ? 1 : 0;
    $cancel = !empty($_POST['cancel_supported']) ? 1 : 0;
    $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
    $desc = trim($_POST['description'] ?? '');

    Database::execute('
        UPDATE services 
        SET category_id = ?, name = ?, rate = ?, original_rate = ?, min_quantity = ?, max_quantity = ?, provider_id = ?, provider_service_id = ?, refill_supported = ?, cancel_supported = ?, status = ?, description = ?
        WHERE id = ?
    ', [$catId, $name, $rate, $origRate, $min, $max, $providerId, $providerServiceId, $refill, $cancel, $status, $desc, $id]);

    set_flash('success', 'Service updated successfully.');
    redirect('/admin/services');
}

if ($requestUri === '/admin/services/delete' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/services');
    }

    $id = (int) ($_POST['service_id'] ?? 0);
    Database::execute('DELETE FROM services WHERE id = ?', [$id]);
    set_flash('success', 'Service deleted.');
    redirect('/admin/services');
}

if ($requestUri === '/admin/providers') {
    require_admin();
    require_once VIEWS_PATH . '/admin/providers.php';
    exit;
}

if ($requestUri === '/admin/providers/create' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/providers');
    }

    $name = trim($_POST['name'] ?? '');
    $url = trim($_POST['api_url'] ?? '');
    $key = trim($_POST['api_key'] ?? '');

    if (!empty($name) && !empty($url) && !empty($key)) {
        Database::insert('INSERT INTO providers (name, api_url, api_key, status, created_at) VALUES (?, ?, ?, "active", NOW())', [$name, $url, $key]);
        set_flash('success', 'Provider connected.');
    }
    redirect('/admin/providers');
}

if ($requestUri === '/admin/providers/update' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/providers');
    }

    $id = (int) ($_POST['provider_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $url = trim($_POST['api_url'] ?? '');
    $key = trim($_POST['api_key'] ?? '');
    $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

    if (!empty($key)) {
        Database::execute('UPDATE providers SET name = ?, api_url = ?, api_key = ?, status = ? WHERE id = ?', [$name, $url, $key, $status, $id]);
    } else {
        Database::execute('UPDATE providers SET name = ?, api_url = ?, status = ? WHERE id = ?', [$name, $url, $status, $id]);
    }

    set_flash('success', 'Provider details saved.');
    redirect('/admin/providers');
}

if ($requestUri === '/admin/providers/delete' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/providers');
    }

    $id = (int) ($_POST['provider_id'] ?? 0);
    Database::execute('DELETE FROM providers WHERE id = ?', [$id]);
    Database::execute('UPDATE services SET provider_id = NULL WHERE provider_id = ?', [$id]);
    set_flash('success', 'Provider removed.');
    redirect('/admin/providers');
}

if ($requestUri === '/admin/providers/test-balance' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/providers');
    }

    $id = (int) ($_POST['provider_id'] ?? 0);
    $p = Database::fetchOne('SELECT * FROM providers WHERE id = ?', [$id]);
    if ($p) {
        $client = new ProviderClient($p['api_url'], $p['api_key']);
        $resp = $client->getBalance();

        if (isset($resp['balance'])) {
            $bal = (float) $resp['balance'];
            $curr = $resp['currency'] ?? 'USD';
            Database::execute('UPDATE providers SET balance = ?, currency = ? WHERE id = ?', [$bal, $curr, $id]);
            set_flash('success', sprintf('Live Balance for %s: %s %s', $p['name'], number_format($bal, 4), $curr));
        } else {
            set_flash('error', 'Provider returned error: ' . ($resp['error'] ?? 'Unknown response'));
        }
    }
    redirect('/admin/providers');
}

if ($requestUri === '/admin/providers/import') {
    require_admin();
    require_once VIEWS_PATH . '/admin/provider_import.php';
    exit;
}

if ($requestUri === '/admin/providers/fetch-services' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/providers/import');
    }

    $id = (int) ($_POST['provider_id'] ?? 0);
    $p = Database::fetchOne('SELECT * FROM providers WHERE id = ?', [$id]);

    if (!$p) {
        set_flash('error', 'Provider not found.');
        redirect('/admin/providers/import');
    }

    $client = new ProviderClient($p['api_url'], $p['api_key']);
    $services = $client->getServices();

    if (is_array($services) && !isset($services['error'])) {
        $_SESSION['fetched_services'] = $services;
        set_flash('success', 'Fetched ' . count($services) . ' live services from ' . $p['name']);
    } else {
        $_SESSION['fetch_error'] = 'API error: ' . ($services['error'] ?? 'Invalid response');
    }
    redirect('/admin/providers/import?provider_id=' . $id);
}

if ($requestUri === '/admin/providers/save-imported' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/providers/import');
    }

    $providerId = (int) ($_POST['provider_id'] ?? 0);
    $margin = (float) ($_POST['profit_margin'] ?? 30);
    $servicesData = $_POST['services'] ?? [];

    $importedCount = 0;
    Database::beginTransaction();
    try {
        foreach ($servicesData as $item) {
            if (empty($item['import'])) continue;

            $catName = trim($item['category'] ?? 'General');
            $srvName = trim($item['name'] ?? '');
            $srvId = trim($item['service'] ?? '');
            $origRate = (float) ($item['rate'] ?? 0);
            $sellingRate = $origRate * (1 + ($margin / 100));
            $min = (int) ($item['min'] ?? 10);
            $max = (int) ($item['max'] ?? 10000);
            $refill = !empty($item['refill']) ? 1 : 0;
            $cancel = !empty($item['cancel']) ? 1 : 0;
            $desc = trim($item['desc'] ?? '');

            // Ensure category exists
            $cat = Database::fetchOne('SELECT id FROM categories WHERE name = ?', [$catName]);
            if (!$cat) {
                $catId = Database::insert('INSERT INTO categories (name, sort_order, status, created_at) VALUES (?, 0, "active", NOW())', [$catName]);
            } else {
                $catId = (int) $cat['id'];
            }

            // Insert service
            Database::insert('
                INSERT INTO services (category_id, provider_id, provider_service_id, name, rate, original_rate, min_quantity, max_quantity, refill_supported, cancel_supported, description, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "active", NOW())
            ', [$catId, $providerId, $srvId, $srvName, $sellingRate, $origRate, $min, $max, $refill, $cancel, $desc]);

            $importedCount++;
        }
        Database::commit();
        set_flash('success', "Successfully imported {$importedCount} services into your database!");
    } catch (Exception $e) {
        Database::rollBack();
        set_flash('error', 'Import failed: ' . $e->getMessage());
    }
    redirect('/admin/services');
}

if ($requestUri === '/admin/providers/sync-services' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/providers');
    }

    $providerId = (int) ($_POST['provider_id'] ?? 0);
    $p = Database::fetchOne('SELECT * FROM providers WHERE id = ?', [$providerId]);

    if (!$p) {
        set_flash('error', 'Provider not found.');
        redirect('/admin/providers');
    }

    $client = new ProviderClient($p['api_url'], $p['api_key']);
    $services = $client->getServices();

    if (!is_array($services) || isset($services['error'])) {
        set_flash('error', 'Sync failed: ' . ($services['error'] ?? 'Could not fetch services'));
        redirect('/admin/providers');
    }

    // Map by service ID
    $mapped = [];
    foreach ($services as $s) {
        if (!empty($s['service'])) {
            $mapped[(string)$s['service']] = $s;
        }
    }

    $localServices = Database::fetchAll('SELECT * FROM services WHERE provider_id = ?', [$providerId]);
    $synced = 0;

    foreach ($localServices as $ls) {
        $psId = (string) $ls['provider_service_id'];
        if (isset($mapped[$psId])) {
            $ps = $mapped[$psId];
            $origRate = (float) ($ps['rate'] ?? $ls['original_rate']);
            $min = (int) ($ps['min'] ?? $ls['min_quantity']);
            $max = (int) ($ps['max'] ?? $ls['max_quantity']);
            $refill = !empty($ps['refill']) ? 1 : 0;
            $cancel = !empty($ps['cancel']) ? 1 : 0;

            Database::execute('
                UPDATE services 
                SET original_rate = ?, min_quantity = ?, max_quantity = ?, refill_supported = ?, cancel_supported = ? 
                WHERE id = ?
            ', [$origRate, $min, $max, $refill, $cancel, $ls['id']]);

            $synced++;
        }
    }

    Database::execute('UPDATE providers SET last_sync = NOW() WHERE id = ?', [$providerId]);
    log_audit('provider_sync', "Synchronized {$synced} services for provider {$p['name']}");

    set_flash('success', "Synchronized {$synced} services with upstream API without overwriting your custom selling rates.");
    redirect('/admin/providers');
}

if ($requestUri === '/admin/orders') {
    require_admin();
    require_once VIEWS_PATH . '/admin/orders.php';
    exit;
}

if ($requestUri === '/admin/orders/check-status' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/orders');
    }

    $orderId = (int) ($_POST['order_id'] ?? 0);
    $order = Database::fetchOne('SELECT o.*, p.api_url, p.api_key FROM orders o JOIN providers p ON o.provider_id = p.id WHERE o.id = ?', [$orderId]);

    if (!$order || empty($order['provider_order_id'])) {
        set_flash('error', 'Order has no mapped provider order ID.');
        redirect('/admin/orders');
    }

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
        ', [$mappedStatus, $startCount, $remains, json_encode($resp), $orderId]);

        set_flash('success', "Order #{$orderId} updated: Status is {$mappedStatus}, Remains: {$remains}");
    } else {
        set_flash('error', 'Provider error: ' . ($resp['error'] ?? 'Unknown status response'));
    }
    redirect('/admin/orders');
}

if ($requestUri === '/admin/orders/update-status' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/orders');
    }

    $orderId = (int) ($_POST['order_id'] ?? 0);
    $newStatus = strtolower(trim($_POST['status'] ?? 'pending'));

    $order = Database::fetchOne('SELECT * FROM orders WHERE id = ?', [$orderId]);
    if ($order) {
        Database::beginTransaction();
        try {
            // Handle automatic refund if marked canceled/refunded and wasn't before
            if (in_array($newStatus, ['canceled', 'refunded']) && !in_array($order['status'], ['canceled', 'refunded'])) {
                $refund = (float) $order['charge'];
                $u = Database::fetchOne('SELECT balance FROM users WHERE id = ? FOR UPDATE', [$order['user_id']]);
                $curr = (float) $u['balance'];
                $newBal = $curr + $refund;

                Database::execute('UPDATE users SET balance = ? WHERE id = ?', [$newBal, $order['user_id']]);
                Database::insert('
                    INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, reference_id, description, created_at)
                    VALUES (?, "refund", ?, ?, ?, ?, ?, NOW())
                ', [$order['user_id'], $refund, $curr, $newBal, (string)$orderId, 'Admin refund for Order #' . $orderId]);
            }

            Database::execute('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?', [$newStatus, $orderId]);
            Database::commit();

            log_audit('order_status_updated', "Admin changed Order #{$orderId} status to {$newStatus}");
            set_flash('success', "Order #{$orderId} status updated to {$newStatus}.");
        } catch (Exception $e) {
            Database::rollBack();
            set_flash('error', 'Failed updating order: ' . $e->getMessage());
        }
    }
    redirect('/admin/orders');
}

if ($requestUri === '/admin/payments') {
    require_admin();
    require_once VIEWS_PATH . '/admin/payments.php';
    exit;
}

if ($requestUri === '/admin/payments/approve' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/payments');
    }

    $paymentId = (int) ($_POST['payment_id'] ?? 0);
    $payment = Database::fetchOne('SELECT * FROM payments WHERE id = ? AND status = "pending"', [$paymentId]);

    if (!$payment) {
        set_flash('error', 'Pending payment request not found.');
        redirect('/admin/payments');
    }

    Database::beginTransaction();
    try {
        $amount = (float) $payment['amount'];
        $u = Database::fetchOne('SELECT balance FROM users WHERE id = ? FOR UPDATE', [$payment['user_id']]);
        $currentBal = (float) $u['balance'];
        $newBal = $currentBal + $amount;

        // Update user balance
        Database::execute('UPDATE users SET balance = ? WHERE id = ?', [$newBal, $payment['user_id']]);

        // Update payment status
        Database::execute('UPDATE payments SET status = "completed", updated_at = NOW() WHERE id = ?', [$paymentId]);

        // Record double-entry transaction ledger
        Database::insert('
            INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, reference_id, description, created_at)
            VALUES (?, "deposit", ?, ?, ?, ?, ?, NOW())
        ', [$payment['user_id'], $amount, $currentBal, $newBal, 'DEP_' . $paymentId, 'Deposit verified via ' . $payment['gateway']]);

        // Notify user
        Database::insert('
            INSERT INTO notifications (user_id, title, message, type, created_at)
            VALUES (?, "Deposit Approved!", ?, "success", NOW())
        ', [$payment['user_id'], "Your payment of " . format_currency($amount) . " has been verified and added to your wallet balance."]);

        Database::commit();
        log_audit('payment_approved', "Approved deposit #{$paymentId} of " . format_currency($amount) . " for user #{$payment['user_id']}");

        set_flash('success', 'Deposit approved and ' . format_currency($amount) . ' credited to user wallet.');
    } catch (Exception $e) {
        Database::rollBack();
        set_flash('error', 'Failed approving deposit: ' . $e->getMessage());
    }
    redirect('/admin/payments');
}

if ($requestUri === '/admin/payments/reject' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/payments');
    }

    $paymentId = (int) ($_POST['payment_id'] ?? 0);
    Database::execute('UPDATE payments SET status = "failed", updated_at = NOW() WHERE id = ?', [$paymentId]);

    log_audit('payment_rejected', "Rejected deposit #{$paymentId}");
    set_flash('success', 'Payment marked as rejected.');
    redirect('/admin/payments');
}

if ($requestUri === '/admin/payments/update-gateway' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/payments');
    }

    $gwId = (int) ($_POST['gateway_id'] ?? 0);
    $min = (float) ($_POST['min_amount'] ?? 1);
    $max = (float) ($_POST['max_amount'] ?? 10000);
    $fee = (float) ($_POST['fee_percent'] ?? 0);
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    $instructions = trim($_POST['instructions'] ?? '');
    $config = trim($_POST['config_data'] ?? '');

    Database::execute('
        UPDATE payment_gateways 
        SET min_amount = ?, max_amount = ?, fee_percent = ?, status = ?, instructions = ?, config_data = ?
        WHERE id = ?
    ', [$min, $max, $fee, $status, $instructions, $config, $gwId]);

    set_flash('success', 'Gateway settings saved.');
    redirect('/admin/payments');
}

if ($requestUri === '/admin/tickets') {
    require_admin();
    require_once VIEWS_PATH . '/admin/tickets.php';
    exit;
}

if ($requestUri === '/admin/tickets/view') {
    require_admin();
    $ticketId = (int) ($_GET['id'] ?? 0);
    $ticket = Database::fetchOne('
        SELECT t.*, u.username, u.email 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = ?
    ', [$ticketId]);

    if (!$ticket) {
        set_flash('error', 'Ticket not found.');
        redirect('/admin/tickets');
    }

    $messages = Database::fetchAll('SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY id ASC', [$ticketId]);
    require_once VIEWS_PATH . '/admin/ticket_view.php';
    exit;
}

if ($requestUri === '/admin/tickets/reply' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/tickets');
    }

    $u = auth_user();
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if (!empty($message) && $ticketId > 0) {
        Database::insert('
            INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message, created_at)
            VALUES (?, ?, 1, ?, NOW())
        ', [$ticketId, $u['id'], $message]);

        Database::execute('UPDATE tickets SET status = "answered", updated_at = NOW() WHERE id = ?', [$ticketId]);

        // Send user notification
        $t = Database::fetchOne('SELECT user_id, subject FROM tickets WHERE id = ?', [$ticketId]);
        if ($t) {
            Database::insert('
                INSERT INTO notifications (user_id, title, message, type, created_at)
                VALUES (?, "Support Reply Received", ?, "ticket", NOW())
            ', [$t['user_id'], 'Staff responded to your ticket: ' . $t['subject']]);
        }

        set_flash('success', 'Staff response sent.');
    }
    redirect('/admin/tickets/view?id=' . $ticketId);
}

if ($requestUri === '/admin/tickets/status' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/tickets');
    }

    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $status = $_POST['status'] ?? 'open';

    if (in_array($status, ['open', 'answered', 'closed'])) {
        Database::execute('UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $ticketId]);
        set_flash('success', "Ticket status changed to {$status}.");
    }
    redirect('/admin/tickets/view?id=' . $ticketId);
}

if ($requestUri === '/admin/announcements') {
    require_admin();
    require_once VIEWS_PATH . '/admin/announcements.php';
    exit;
}

if ($requestUri === '/admin/announcements/create' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/announcements');
    }

    $title = trim($_POST['title'] ?? '');
    $type = $_POST['type'] ?? 'info';
    $message = trim($_POST['message'] ?? '');

    if (!empty($title) && !empty($message)) {
        Database::insert('
            INSERT INTO notifications (user_id, title, message, type, created_at)
            VALUES (NULL, ?, ?, ?, NOW())
        ', [$title, $message, $type]);

        log_audit('announcement_broadcast', 'Broadcasted announcement: ' . $title);
        set_flash('success', 'Announcement published to all customer dashboards.');
    }
    redirect('/admin/announcements');
}

if ($requestUri === '/admin/settings') {
    require_admin();
    require_once VIEWS_PATH . '/admin/settings.php';
    exit;
}

if ($requestUri === '/admin/settings/update' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/settings');
    }

    $keys = [
        'site_name', 'site_tagline', 'site_description',
        'currency', 'currency_symbol', 'timezone',
        'contact_email', 'contact_telegram'
    ];

    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            Database::execute('INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)', [$k, trim($_POST[$k])]);
        }
    }

    $reg = !empty($_POST['registration_enabled']) ? '1' : '0';
    $maint = !empty($_POST['maintenance_mode']) ? '1' : '0';

    Database::execute('INSERT INTO settings (key_name, key_value) VALUES ("registration_enabled", ?) ON DUPLICATE KEY UPDATE key_value = ?', [$reg, $reg]);
    Database::execute('INSERT INTO settings (key_name, key_value) VALUES ("maintenance_mode", ?) ON DUPLICATE KEY UPDATE key_value = ?', [$maint, $maint]);

    log_audit('settings_updated', 'Updated website system settings');
    set_flash('success', 'System settings saved.');
    redirect('/admin/settings');
}

if ($requestUri === '/admin/tools') {
    require_admin();
    require_once VIEWS_PATH . '/admin/tools.php';
    exit;
}

if ($requestUri === '/admin/tools/run-cron' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/tools');
    }

    // Execute cron routine
    require_once ROOT_PATH . '/cron.php';
    set_flash('success', 'Cron routine executed successfully.');
    redirect('/admin/tools');
}

if ($requestUri === '/admin/tools/backup-db' && $method === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        set_flash('error', 'Security token error.');
        redirect('/admin/tools');
    }

    $backupName = 'backup_' . date('Y_m_d_His') . '.sql';
    $backupPath = STORAGE_PATH . '/backups/' . $backupName;

    // Build database dump
    $tables = Database::fetchAll('SHOW TABLES');
    $sqlDump = "-- ApexSMM Database Dump\n-- Generated: " . date('c') . "\n\n";

    foreach ($tables as $tRow) {
        $tName = array_values($tRow)[0];
        $createTable = Database::fetchOne("SHOW CREATE TABLE `{$tName}`");
        $sqlDump .= "DROP TABLE IF EXISTS `{$tName}`;\n" . $createTable['Create Table'] . ";\n\n";

        $rows = Database::fetchAll("SELECT * FROM `{$tName}`");
        foreach ($rows as $r) {
            $cols = array_map(fn($c) => "`$c`", array_keys($r));
            $vals = array_map(function($v) {
                return $v === null ? 'NULL' : Database::getConnection()->quote($v);
            }, array_values($r));
            $sqlDump .= "INSERT INTO `{$tName}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
        }
        $sqlDump .= "\n";
    }

    file_put_contents($backupPath, $sqlDump);
    log_audit('database_backup', 'Created snapshot backup: ' . $backupName);

    set_flash('success', 'Database backup snapshot created: ' . $backupName);
    redirect('/admin/tools');
}

// -------------------------------------------------------------
// 404 Fallback
// -------------------------------------------------------------
http_response_code(404);
echo '<!DOCTYPE html><html><head><title>404 Not Found</title><link rel="stylesheet" href="/dist/assets/style.css"></head><body class="bg-[#070913] text-white flex items-center justify-center min-h-screen text-center p-6"><div class="max-w-md p-8 rounded-3xl bg-[#0e1326] border border-slate-800 shadow-2xl"><h1 class="text-4xl font-extrabold text-blue-500 mb-2">404</h1><h2 class="text-xl font-bold mb-2">Page Not Found</h2><p class="text-xs text-slate-400 mb-6">The requested page or endpoint does not exist.</p><a href="/" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold">Back to Home</a></div></body></html>';
exit;
