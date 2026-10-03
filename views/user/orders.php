<?php
$pageTitle = 'My Orders';
$currentRoute = 'orders';
require_once __DIR__ . '/../layouts/header.php';

$userId = $user['id'];
$statusFilter = strtolower(trim($_GET['status'] ?? 'all'));
$search = trim($_GET['q'] ?? '');

$validStatuses = ['all', 'pending', 'processing', 'in_progress', 'completed', 'partial', 'canceled', 'refunded', 'failed'];
if (!in_array($statusFilter, $validStatuses)) {
    $statusFilter = 'all';
}

$sql = '
    SELECT o.*, s.name as service_name, s.refill_supported, s.cancel_supported
    FROM orders o 
    JOIN services s ON o.service_id = s.id 
    WHERE o.user_id = ?
';
$params = [$userId];

if ($statusFilter !== 'all') {
    $sql .= ' AND o.status = ?';
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $sql .= ' AND (o.link LIKE ? OR o.id = ?)';
    $params[] = '%' . $search . '%';
    $params[] = (int) $search;
}

$sql .= ' ORDER BY o.id DESC';
$orders = Database::fetchAll($sql, $params);

// Counts per status for tab badges
$statusCounts = Database::fetchAll('
    SELECT status, COUNT(*) as cnt 
    FROM orders 
    WHERE user_id = ? 
    GROUP BY status
', [$userId]);

$countsMap = ['all' => 0];
foreach ($statusCounts as $sc) {
    $st = strtolower($sc['status']);
    $countsMap[$st] = (int) $sc['cnt'];
    $countsMap['all'] += (int) $sc['cnt'];
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-white">Order History</h1>
            <p class="text-slate-400 text-sm mt-1">Real-time status updates and order execution logs.</p>
        </div>
        <a href="/new-order" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 transition text-sm">
            <?= heroicon('plus', 'w-4 h-4') ?> New Order
        </a>
    </div>

    <!-- Filter Tabs & Search -->
    <div class="p-4 rounded-2xl bg-[#0e1326] border border-slate-800/80 mb-8 flex flex-col lg:flex-row gap-4 items-center justify-between">
        
        <!-- Status Tabs -->
        <div class="flex items-center gap-1.5 w-full lg:w-auto overflow-x-auto pb-2 lg:pb-0 text-xs">
            <?php foreach (['all' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'partial' => 'Partial', 'canceled' => 'Canceled', 'refunded' => 'Refunded'] as $stKey => $stLabel): ?>
                <a href="/orders?status=<?= $stKey ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition flex items-center gap-1.5 <?= $statusFilter === $stKey ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-300 hover:text-white border border-slate-800' ?>">
                    <span><?= $stLabel ?></span>
                    <?php if (($countsMap[$stKey] ?? 0) > 0): ?>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] <?= $statusFilter === $stKey ? 'bg-white/20 text-white' : 'bg-slate-800 text-slate-400' ?>"><?= $countsMap[$stKey] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Search -->
        <form method="GET" action="/orders" class="w-full lg:w-72 relative">
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search ID or link..." class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition">
            <div class="absolute left-3 top-2.5 text-slate-500">
                <?= heroicon('magnifying-glass', 'w-4 h-4') ?>
            </div>
        </form>

    </div>

    <!-- Orders Table -->
    <?php if (empty($orders)): ?>
        <div class="p-16 rounded-2xl bg-[#0e1326] border border-slate-800/80 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-500">
                <?= heroicon('shopping-cart', 'w-7 h-7') ?>
            </div>
            <h3 class="text-lg font-bold text-white mb-1">No orders found</h3>
            <p class="text-sm text-slate-400 max-w-md mx-auto">
                <?= !empty($search) || $statusFilter !== 'all' ? 'No orders match your selected filters. Try changing or clearing the status filter.' : 'You have not placed any orders yet. Click "New Order" to get started.' ?>
            </p>
            <div class="mt-5">
                <a href="/new-order" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold bg-blue-600 hover:bg-blue-500 text-white text-xs transition">
                    <?= heroicon('plus', 'w-4 h-4') ?> Create First Order
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-slate-800/80 bg-[#0e1326] shadow-xl">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-900/60 font-semibold uppercase tracking-wider text-slate-400 text-[11px]">
                        <th class="py-4 px-4 sm:px-6">ID</th>
                        <th class="py-4 px-4">Service</th>
                        <th class="py-4 px-4">Target Link</th>
                        <th class="py-4 px-4 text-center">Quantity</th>
                        <th class="py-4 px-4 text-center">Start / Remains</th>
                        <th class="py-4 px-4 text-right">Charge</th>
                        <th class="py-4 px-4 text-center">Status</th>
                        <th class="py-4 px-4 text-right">Date</th>
                        <th class="py-4 px-4 sm:px-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-mono">
                    <?php foreach ($orders as $o): ?>
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-4 px-4 sm:px-6 text-slate-500 font-bold">#<?= $o['id'] ?></td>
                            <td class="py-4 px-4 font-sans text-slate-200 max-w-xs truncate">
                                <?= e($o['service_name']) ?>
                            </td>
                            <td class="py-4 px-4 max-w-[200px] truncate text-slate-400 font-sans">
                                <a href="<?= e($o['link']) ?>" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:underline">
                                    <?= e($o['link']) ?>
                                </a>
                            </td>
                            <td class="py-4 px-4 text-center text-slate-200 font-semibold"><?= number_format($o['quantity']) ?></td>
                            <td class="py-4 px-4 text-center text-slate-400">
                                <?= number_format($o['start_count']) ?> / <?= number_format($o['remains']) ?>
                            </td>
                            <td class="py-4 px-4 text-right font-bold text-emerald-400"><?= format_currency($o['charge']) ?></td>
                            <td class="py-4 px-4 text-center font-sans">
                                <?= status_badge($o['status']) ?>
                            </td>
                            <td class="py-4 px-4 text-right text-slate-500 whitespace-nowrap">
                                <?= format_date($o['created_at'], 'M d, H:i') ?>
                            </td>
                            <td class="py-4 px-4 sm:px-6 text-right font-sans whitespace-nowrap">
                                <?php if ($o['status'] === 'completed' && $o['refill_supported']): ?>
                                    <form method="POST" action="/orders/refill" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded-md text-[11px] font-semibold bg-blue-600/20 text-blue-400 border border-blue-500/30 hover:bg-blue-600 hover:text-white transition">
                                            Refill
                                        </button>
                                    </form>
                                <?php elseif ($o['status'] === 'pending' && $o['cancel_supported']): ?>
                                    <form method="POST" action="/orders/cancel" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded-md text-[11px] font-semibold bg-rose-600/20 text-rose-400 border border-rose-500/30 hover:bg-rose-600 hover:text-white transition">
                                            Cancel
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-slate-600 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
