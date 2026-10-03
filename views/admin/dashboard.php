<?php
$pageTitle = 'Admin Dashboard';
$currentAdminRoute = 'dashboard';
require_once __DIR__ . '/../layouts/admin_header.php';

// Real metrics from MySQL
$totalUsers = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM users WHERE role = "user"')['cnt'] ?? 0);
$totalOrders = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM orders')['cnt'] ?? 0);
$pendingOrders = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM orders WHERE status = "pending"')['cnt'] ?? 0);
$completedOrders = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM orders WHERE status = "completed"')['cnt'] ?? 0);
$failedOrders = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM orders WHERE status IN ("canceled", "cancelled", "failed")')['cnt'] ?? 0);
$totalRevenue = (float) (Database::fetchOne('SELECT COALESCE(SUM(charge), 0) as rev FROM orders WHERE status NOT IN ("refunded", "canceled", "cancelled", "failed")')['rev'] ?? 0);
$pendingPayments = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM payments WHERE status = "pending"')['cnt'] ?? 0);
$openTickets = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM tickets WHERE status = "open"')['cnt'] ?? 0);
$activeProviders = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM providers WHERE status = "active"')['cnt'] ?? 0);

// Daily revenue & orders for chart
$dailyChartData = [];
if ($totalOrders > 0) {
    $dailyStats = Database::fetchAll('
        SELECT DATE(created_at) as order_date, COUNT(*) as cnt, SUM(charge) as rev
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
        GROUP BY DATE(created_at)
        ORDER BY order_date ASC
    ');

    $chartDates = [];
    $chartOrders = [];
    $chartRev = [];
    foreach ($dailyStats as $ds) {
        $chartDates[] = date('M d', strtotime($ds['order_date']));
        $chartOrders[] = (int) $ds['cnt'];
        $chartRev[] = round((float) $ds['rev'], 2);
    }
    $dailyChartData = [
        'dates' => $chartDates,
        'orders' => $chartOrders,
        'revenue' => $chartRev,
    ];
}

// Recent orders
$recentOrders = Database::fetchAll('
    SELECT o.*, u.username, s.name as service_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN services s ON o.service_id = s.id
    ORDER BY o.id DESC
    LIMIT 6
');

// Recent users
$recentUsers = Database::fetchAll('
    SELECT * FROM users
    WHERE role = "user"
    ORDER BY id DESC
    LIMIT 5
');
?>

<div class="space-y-8">

    <!-- Top Real Metric Cards Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Users -->
        <div class="p-5 rounded-2xl bg-[#0e1326] border border-slate-800/80">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Users</span>
                <span class="p-2 rounded-lg bg-blue-500/10 text-blue-400"><?= heroicon('users', 'w-4 h-4') ?></span>
            </div>
            <div class="text-2xl font-black text-white"><?= number_format($totalUsers) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Registered clients</div>
        </div>

        <!-- Total Revenue -->
        <div class="p-5 rounded-2xl bg-[#0e1326] border border-slate-800/80">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Revenue</span>
                <span class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400"><?= heroicon('wallet', 'w-4 h-4') ?></span>
            </div>
            <div class="text-2xl font-black text-emerald-400 font-mono"><?= format_currency($totalRevenue) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Processed order charges</div>
        </div>

        <!-- Total Orders -->
        <div class="p-5 rounded-2xl bg-[#0e1326] border border-slate-800/80">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Orders</span>
                <span class="p-2 rounded-lg bg-purple-500/10 text-purple-400"><?= heroicon('shopping-cart', 'w-4 h-4') ?></span>
            </div>
            <div class="text-2xl font-black text-white"><?= number_format($totalOrders) ?></div>
            <div class="text-[11px] text-slate-500 mt-1"><?= number_format($completedOrders) ?> completed</div>
        </div>

        <!-- Pending Payments -->
        <div class="p-5 rounded-2xl bg-[#0e1326] border border-slate-800/80">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pending Deposits</span>
                <span class="p-2 rounded-lg bg-amber-500/10 text-amber-400"><?= heroicon('credit-card', 'w-4 h-4') ?></span>
            </div>
            <div class="text-2xl font-black text-amber-400"><?= number_format($pendingPayments) ?></div>
            <a href="/admin/payments" class="text-[11px] text-blue-400 hover:underline mt-1 block">Review deposits &rarr;</a>
        </div>

    </div>

    <!-- Secondary Metric Pill Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 rounded-2xl bg-[#0a0e1e] border border-slate-800 text-xs">
        <div class="p-3 text-center">
            <span class="text-slate-500 block">Pending Orders</span>
            <span class="text-base font-bold text-amber-400 font-mono"><?= number_format($pendingOrders) ?></span>
        </div>
        <div class="p-3 text-center border-l border-slate-800">
            <span class="text-slate-500 block">Failed / Canceled</span>
            <span class="text-base font-bold text-rose-400 font-mono"><?= number_format($failedOrders) ?></span>
        </div>
        <div class="p-3 text-center border-l border-slate-800">
            <span class="text-slate-500 block">Open Tickets</span>
            <span class="text-base font-bold text-blue-400 font-mono"><?= number_format($openTickets) ?></span>
        </div>
        <div class="p-3 text-center border-l border-slate-800">
            <span class="text-slate-500 block">Active Providers</span>
            <span class="text-base font-bold text-emerald-400 font-mono"><?= number_format($activeProviders) ?></span>
        </div>
    </div>

    <!-- Revenue & Order Growth Analytics Chart -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-base font-bold text-white">System Revenue & Order Velocity</h3>
                <p class="text-xs text-slate-400 mt-0.5">Real daily performance metrics directly from the MySQL database.</p>
            </div>
            <?php if (!empty($dailyChartData['dates'])): ?>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20">Last 14 Days</span>
            <?php endif; ?>
        </div>

        <?php if (!empty($dailyChartData['dates']) && count($dailyChartData['dates']) > 0): ?>
            <div id="adminAnalyticsChart" class="h-64 w-full"></div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (window.ApexCharts) {
                        var options = {
                            chart: {
                                type: 'bar',
                                height: 260,
                                toolbar: { show: false },
                                background: 'transparent'
                            },
                            theme: { mode: 'dark' },
                            colors: ['#3b82f6', '#10b981'],
                            series: [
                                { name: 'Orders Placed', data: <?= json_encode($dailyChartData['orders']) ?> },
                                { name: 'Revenue ($)', data: <?= json_encode($dailyChartData['revenue']) ?> }
                            ],
                            xaxis: {
                                categories: <?= json_encode($dailyChartData['dates']) ?>,
                                labels: { style: { colors: '#64748b', fontSize: '11px' } },
                                axisBorder: { show: false },
                                axisTicks: { show: false }
                            },
                            yaxis: {
                                labels: { style: { colors: '#64748b', fontSize: '11px' } }
                            },
                            grid: { borderColor: '#1e293b', strokeDashArray: 3 }
                        };
                        var chart = new ApexCharts(document.querySelector("#adminAnalyticsChart"), options);
                        chart.render();
                    }
                });
            </script>
        <?php else: ?>
            <div class="py-12 text-center">
                <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-500">
                    <?= heroicon('chart-bar', 'w-6 h-6') ?>
                </div>
                <h4 class="text-sm font-semibold text-white">No order analytics to display yet</h4>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    Live charts will dynamically render here as users place real orders in the database.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Orders & Users Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Recent Orders (2 Cols) -->
        <div class="lg:col-span-2 p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-white">Latest Orders</h3>
                <a href="/admin/orders" class="text-xs font-semibold text-blue-400 hover:underline">Manage All &rarr;</a>
            </div>

            <?php if (empty($recentOrders)): ?>
                <p class="text-xs text-slate-500 py-8 text-center">No orders recorded in database yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px]">
                                <th class="pb-2.5">ID</th>
                                <th class="pb-2.5 font-sans">User</th>
                                <th class="pb-2.5 font-sans">Service</th>
                                <th class="pb-2.5 text-center">Qty</th>
                                <th class="pb-2.5 text-right">Charge</th>
                                <th class="pb-2.5 text-right font-sans">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <?php foreach ($recentOrders as $ro): ?>
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-3 text-slate-500 font-bold">#<?= $ro['id'] ?></td>
                                    <td class="py-3 font-sans text-slate-200"><?= e($ro['username']) ?></td>
                                    <td class="py-3 font-sans text-slate-300 truncate max-w-xs"><?= e($ro['service_name']) ?></td>
                                    <td class="py-3 text-center text-slate-400"><?= number_format($ro['quantity']) ?></td>
                                    <td class="py-3 text-right font-bold text-emerald-400"><?= format_currency($ro['charge']) ?></td>
                                    <td class="py-3 text-right font-sans"><?= status_badge($ro['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Users (1 Col) -->
        <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-white">Recent Users</h3>
                <a href="/admin/users" class="text-xs font-semibold text-blue-400 hover:underline">View All &rarr;</a>
            </div>

            <?php if (empty($recentUsers)): ?>
                <p class="text-xs text-slate-500 py-8 text-center">No client accounts registered yet.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($recentUsers as $ru): ?>
                        <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-blue-600/20 text-blue-400 flex items-center justify-center font-bold font-mono">
                                    <?= strtoupper(substr($ru['username'], 0, 2)) ?>
                                </div>
                                <div>
                                    <span class="font-bold text-white block"><?= e($ru['username']) ?></span>
                                    <span class="text-[11px] text-slate-500 font-mono"><?= format_date($ru['created_at'], 'M d') ?></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-emerald-400 font-mono block"><?= format_currency($ru['balance']) ?></span>
                                <?= status_badge($ru['status']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
