<?php
$pageTitle = 'Dashboard';
$currentRoute = 'dashboard';
require_once __DIR__ . '/../layouts/header.php';

$userId = $user['id'];

// Real metrics from database for this user
$stats = Database::fetchOne('
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status IN ("canceled", "cancelled", "failed") THEN 1 ELSE 0 END) as failed_orders,
        COALESCE(SUM(CASE WHEN status NOT IN ("canceled", "cancelled", "refunded", "failed") THEN charge ELSE 0 END), 0) as total_spent
    FROM orders 
    WHERE user_id = ?
', [$userId]);

$totalOrdersCount = (int) ($stats['total_orders'] ?? 0);
$completedOrdersCount = (int) ($stats['completed_orders'] ?? 0);
$pendingOrdersCount = (int) ($stats['pending_orders'] ?? 0);
$failedOrdersCount = (int) ($stats['failed_orders'] ?? 0);
$totalSpent = (float) ($stats['total_spent'] ?? 0);

// Recent orders
$recentOrders = Database::fetchAll('
    SELECT o.*, s.name as service_name 
    FROM orders o 
    JOIN services s ON o.service_id = s.id 
    WHERE o.user_id = ? 
    ORDER BY o.id DESC 
    LIMIT 5
', [$userId]);

// Daily order stats for chart (only if orders exist)
$chartData = [];
if ($totalOrdersCount > 0) {
    $dailyStats = Database::fetchAll('
        SELECT DATE(created_at) as order_date, COUNT(*) as cnt, SUM(charge) as total_charge
        FROM orders
        WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
        GROUP BY DATE(created_at)
        ORDER BY order_date ASC
    ', [$userId]);

    $chartDates = [];
    $chartCounts = [];
    $chartCharges = [];
    foreach ($dailyStats as $ds) {
        $chartDates[] = date('M d', strtotime($ds['order_date']));
        $chartCounts[] = (int) $ds['cnt'];
        $chartCharges[] = round((float) $ds['total_charge'], 2);
    }
    $chartData = [
        'dates' => $chartDates,
        'counts' => $chartCounts,
        'charges' => $chartCharges,
    ];
}

// Announcements / System notifications
$notifications = Database::fetchAll('
    SELECT * FROM notifications 
    WHERE (user_id = ? OR user_id IS NULL) 
    ORDER BY id DESC 
    LIMIT 3
', [$userId]);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    
    <!-- Welcome Header & Quick Action -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-6 rounded-3xl bg-gradient-to-r from-[#0e1326] via-[#131b36] to-[#0e1326] border border-slate-800/80 shadow-xl relative overflow-hidden">
        <div class="space-y-1 relative z-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Hello, <?= e($user['username']) ?></h1>
            <p class="text-xs sm:text-sm text-slate-400">Welcome to your management dashboard. Real-time statistics and order tracking.</p>
        </div>
        <div class="flex items-center gap-3 relative z-10 w-full sm:w-auto">
            <a href="/new-order" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white shadow-lg shadow-blue-600/30 transition text-sm">
                <?= heroicon('plus', 'w-4 h-4') ?> New Order
            </a>
            <a href="/add-funds" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-semibold bg-slate-900 hover:bg-slate-800 border border-slate-700 text-slate-200 transition text-sm">
                <?= heroicon('wallet', 'w-4 h-4 text-emerald-400') ?> Top Up
            </a>
        </div>
    </div>

    <!-- Real Database Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- Balance -->
        <div class="p-5 rounded-2xl bg-[#0e1326] border border-slate-800/80 hover:border-blue-500/40 transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Wallet Balance</span>
                <span class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400"><?= heroicon('wallet', 'w-4 h-4') ?></span>
            </div>
            <div class="text-2xl font-black text-emerald-400 font-mono"><?= format_currency($user['balance']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Available for new orders</div>
        </div>

        <!-- Total Spent -->
        <div class="p-5 rounded-2xl bg-[#0e1326] border border-slate-800/80 hover:border-blue-500/40 transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Spent</span>
                <span class="p-2 rounded-lg bg-blue-500/10 text-blue-400"><?= heroicon('credit-card', 'w-4 h-4') ?></span>
            </div>
            <div class="text-2xl font-black text-white font-mono"><?= format_currency($totalSpent) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">All-time order charges</div>
        </div>

        <!-- Total Orders -->
        <div class="p-5 rounded-2xl bg-[#0e1326] border border-slate-800/80 hover:border-blue-500/40 transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Orders</span>
                <span class="p-2 rounded-lg bg-purple-500/10 text-purple-400"><?= heroicon('shopping-cart', 'w-4 h-4') ?></span>
            </div>
            <div class="text-2xl font-black text-white"><?= number_format($totalOrdersCount) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Lifetime placed</div>
        </div>

        <!-- Completed Orders -->
        <div class="p-5 rounded-2xl bg-[#0e1326] border border-slate-800/80 hover:border-blue-500/40 transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Completed</span>
                <span class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400"><?= heroicon('check', 'w-4 h-4') ?></span>
            </div>
            <div class="text-2xl font-black text-emerald-400"><?= number_format($completedOrdersCount) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Fulfillment completed</div>
        </div>

        <!-- Pending / In Progress -->
        <div class="p-5 rounded-2xl bg-[#0e1326] border border-slate-800/80 hover:border-blue-500/40 transition col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pending</span>
                <span class="p-2 rounded-lg bg-amber-500/10 text-amber-400"><?= heroicon('arrow-path', 'w-4 h-4') ?></span>
            </div>
            <div class="text-2xl font-black text-amber-400"><?= number_format($pendingOrdersCount) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Awaiting provider processing</div>
        </div>

    </div>

    <!-- Order Analytics Chart or Proper Empty State -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-base font-bold text-white">Order Activity & Volume</h3>
                <p class="text-xs text-slate-400 mt-0.5">Real daily order counts from database records.</p>
            </div>
            <?php if (!empty($chartData['dates'])): ?>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20">Last 14 Days</span>
            <?php endif; ?>
        </div>

        <?php if (!empty($chartData['dates']) && count($chartData['dates']) > 0): ?>
            <div id="userOrderChart" class="h-64 w-full"></div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (window.ApexCharts) {
                        var options = {
                            chart: {
                                type: 'area',
                                height: 260,
                                toolbar: { show: false },
                                background: 'transparent'
                            },
                            theme: { mode: 'dark' },
                            stroke: { curve: 'smooth', width: 2 },
                            colors: ['#3b82f6', '#10b981'],
                            series: [
                                { name: 'Orders Placed', data: <?= json_encode($chartData['counts']) ?> },
                                { name: 'Spent ($)', data: <?= json_encode($chartData['charges']) ?> }
                            ],
                            xaxis: {
                                categories: <?= json_encode($chartData['dates']) ?>,
                                labels: { style: { colors: '#64748b', fontSize: '11px' } },
                                axisBorder: { show: false },
                                axisTicks: { show: false }
                            },
                            yaxis: {
                                labels: { style: { colors: '#64748b', fontSize: '11px' } }
                            },
                            grid: { borderColor: '#1e293b', strokeDashArray: 3 },
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shadeIntensity: 1,
                                    opacityFrom: 0.45,
                                    opacityTo: 0.05,
                                    stops: [20, 100]
                                }
                            }
                        };
                        var chart = new ApexCharts(document.querySelector("#userOrderChart"), options);
                        chart.render();
                    }
                });
            </script>
        <?php else: ?>
            <div class="py-12 text-center">
                <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-500">
                    <?= heroicon('chart-bar', 'w-6 h-6') ?>
                </div>
                <h4 class="text-sm font-semibold text-white">No chart statistics available yet</h4>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    Once you submit your first order, real dynamic analytics tracking your volume and spending will appear here.
                </p>
                <div class="mt-4">
                    <a href="/new-order" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white transition">
                        Place Your First Order
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Orders & Notifications Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Recent Orders (2 Cols) -->
        <div class="lg:col-span-2 p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-white">Recent Orders</h3>
                <a href="/orders" class="text-xs font-semibold text-blue-400 hover:underline">View All</a>
            </div>

            <?php if (empty($recentOrders)): ?>
                <div class="py-10 text-center">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-2 text-slate-500">
                        <?= heroicon('shopping-cart', 'w-5 h-5') ?>
                    </div>
                    <p class="text-xs text-slate-400">No orders placed yet.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 uppercase font-semibold text-[10px]">
                                <th class="pb-2.5">ID</th>
                                <th class="pb-2.5">Service</th>
                                <th class="pb-2.5 text-center">Quantity</th>
                                <th class="pb-2.5 text-right">Charge</th>
                                <th class="pb-2.5 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono">
                            <?php foreach ($recentOrders as $ro): ?>
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-3 text-slate-500 font-semibold">#<?= $ro['id'] ?></td>
                                    <td class="py-3 font-sans text-slate-300 truncate max-w-xs"><?= e($ro['service_name']) ?></td>
                                    <td class="py-3 text-center text-slate-400"><?= number_format($ro['quantity']) ?></td>
                                    <td class="py-3 text-right font-bold text-emerald-400"><?= format_currency($ro['charge']) ?></td>
                                    <td class="py-3 text-right"><?= status_badge($ro['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Announcements / Live Updates (1 Col) -->
        <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <?= heroicon('bell', 'w-4 h-4 text-blue-400') ?> Platform Updates
            </h3>

            <?php if (empty($notifications)): ?>
                <p class="text-xs text-slate-400 py-6 text-center">No new notifications available.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($notifications as $n): ?>
                        <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-200"><?= e($n['title']) ?></span>
                                <span class="text-[10px] text-slate-500 font-mono"><?= format_date($n['created_at'], 'M d') ?></span>
                            </div>
                            <p class="text-slate-400 leading-relaxed"><?= e($n['message']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
