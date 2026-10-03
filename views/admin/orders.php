<?php
$pageTitle = 'Order Management';
$currentAdminRoute = 'orders';
require_once __DIR__ . '/../layouts/admin_header.php';

$statusFilter = strtolower(trim($_GET['status'] ?? 'all'));
$search = trim($_GET['q'] ?? '');

$sql = '
    SELECT o.*, u.username, s.name as service_name, p.name as provider_name
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN services s ON o.service_id = s.id 
    LEFT JOIN providers p ON o.provider_id = p.id 
    WHERE 1=1
';
$params = [];

if ($statusFilter !== 'all') {
    $sql .= ' AND o.status = ?';
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $sql .= ' AND (o.link LIKE ? OR u.username LIKE ? OR o.id = ? OR o.provider_order_id = ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = (int) $search;
    $params[] = $search;
}

$sql .= ' ORDER BY o.id DESC';
$orders = Database::fetchAll($sql, $params);
?>

<div class="space-y-6" x-data="{
    statusModal: false,
    selectedOrder: null,
    newStatus: 'completed'
}">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Order Management</h2>
            <p class="text-xs text-slate-400 mt-0.5">Audit customer orders, check live upstream statuses, and process refunds.</p>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="p-4 rounded-2xl bg-[#0e1326] border border-slate-800/80 flex flex-col lg:flex-row gap-4 items-center justify-between">
        <div class="flex items-center gap-1.5 w-full lg:w-auto overflow-x-auto pb-2 lg:pb-0 text-xs">
            <?php foreach (['all' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'partial' => 'Partial', 'canceled' => 'Canceled', 'refunded' => 'Refunded', 'failed' => 'Failed'] as $sk => $sl): ?>
                <a href="/admin/orders?status=<?= $sk ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition <?= $statusFilter === $sk ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-300 hover:text-white border border-slate-800' ?>">
                    <?= $sl ?>
                </a>
            <?php endforeach; ?>
        </div>

        <form method="GET" action="/admin/orders" class="w-full lg:w-72 relative">
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search user, link, order ID..." class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition">
            <div class="absolute left-3 top-2.5 text-slate-500">
                <?= heroicon('magnifying-glass', 'w-4 h-4') ?>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <?php if (empty($orders)): ?>
        <div class="p-16 rounded-2xl bg-[#0e1326] border border-slate-800/80 text-center">
            <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-500">
                <?= heroicon('shopping-cart', 'w-6 h-6') ?>
            </div>
            <h3 class="text-base font-bold text-white mb-1">No orders found</h3>
            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                No orders match your selected filter criteria.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-slate-800/80 bg-[#0e1326] shadow-xl">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-900/60 font-semibold uppercase tracking-wider text-slate-400 text-[10px]">
                        <th class="py-4 px-4 sm:px-6">ID</th>
                        <th class="py-4 px-4">User</th>
                        <th class="py-4 px-4">Service</th>
                        <th class="py-4 px-4">Target Link</th>
                        <th class="py-4 px-4 text-center">Qty</th>
                        <th class="py-4 px-4 text-right">Charge</th>
                        <th class="py-4 px-4">Provider</th>
                        <th class="py-4 px-4 text-center">Status</th>
                        <th class="py-4 px-4 text-right">Date</th>
                        <th class="py-4 px-4 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-mono">
                    <?php foreach ($orders as $o): ?>
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3 px-4 sm:px-6 text-slate-500 font-bold">#<?= $o['id'] ?></td>
                            <td class="py-3 px-4 font-sans font-bold text-slate-200"><?= e($o['username']) ?></td>
                            <td class="py-3 px-4 font-sans text-slate-300 max-w-[200px] truncate" title="<?= e($o['service_name']) ?>"><?= e($o['service_name']) ?></td>
                            <td class="py-3 px-4 font-sans text-slate-400 max-w-[160px] truncate">
                                <a href="<?= e($o['link']) ?>" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:underline"><?= e($o['link']) ?></a>
                            </td>
                            <td class="py-3 px-4 text-center text-slate-200"><?= number_format($o['quantity']) ?></td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-400"><?= format_currency($o['charge']) ?></td>
                            <td class="py-3 px-4 font-sans text-slate-400">
                                <?php if ($o['provider_name']): ?>
                                    <span class="text-purple-400 font-mono text-[11px]"><?= e($o['provider_name']) ?></span>
                                    <span class="block text-[10px] text-slate-500">ID: <?= e($o['provider_order_id'] ?: 'Pending') ?></span>
                                <?php else: ?>
                                    <span class="text-slate-600">Manual</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-center font-sans"><?= status_badge($o['status']) ?></td>
                            <td class="py-3 px-4 text-right text-slate-500 whitespace-nowrap"><?= format_date($o['created_at'], 'M d, H:i') ?></td>
                            <td class="py-3 px-4 sm:px-6 text-right font-sans space-x-1 whitespace-nowrap">
                                <?php if ($o['provider_id'] && $o['provider_order_id']): ?>
                                    <form method="POST" action="/admin/orders/check-status" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <button type="submit" class="px-2 py-1 rounded text-[11px] font-semibold bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-600 hover:text-white transition" title="Fetch status from provider API">
                                            Poll Status
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <button type="button" @click="selectedOrder = <?= htmlspecialchars(json_encode($o), ENT_QUOTES, 'UTF-8') ?>; newStatus = '<?= $o['status'] ?>'; statusModal = true" class="px-2 py-1 rounded text-[11px] font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                                    Update
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Update Order Status Modal -->
    <div x-show="statusModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="statusModal = false">
        <div class="min-h-screen px-4 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="statusModal = false"></div>
            <div class="relative bg-[#0e1326] border border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl z-10" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Update Order #<span x-text="selectedOrder ? selectedOrder.id : ''"></span></h3>
                    <button @click="statusModal = false" class="text-slate-400 hover:text-white"><?= heroicon('x-mark', 'w-5 h-5') ?></button>
                </div>
                <form method="POST" action="/admin/orders/update-status" class="py-4 space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="order_id" :value="selectedOrder ? selectedOrder.id : ''">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">New Status</label>
                        <select name="status" x-model="newStatus" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="partial">Partial</option>
                            <option value="canceled">Canceled (Refund to Wallet)</option>
                            <option value="refunded">Refunded</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>

                    <div x-show="newStatus === 'canceled' || newStatus === 'refunded'" class="p-3 rounded-xl bg-amber-950/40 border border-amber-500/30 text-amber-200 text-xs">
                        <?= heroicon('exclamation-triangle', 'w-4 h-4 inline mr-1') ?>
                        Marking as canceled will automatically refund the full charge of <strong x-text="selectedOrder ? '$' + parseFloat(selectedOrder.charge).toFixed(4) : ''"></strong> back to the user's wallet.
                    </div>

                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" @click="statusModal = false" class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30">Save Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
