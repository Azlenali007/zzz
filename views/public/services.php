<?php
$pageTitle = 'Services & Pricing';
$currentRoute = 'services';
require_once __DIR__ . '/../layouts/header.php';

// Fetch real categories and services from MySQL
$categories = Database::fetchAll('SELECT * FROM categories WHERE status = "active" ORDER BY sort_order ASC, name ASC');

$selectedCat = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$search = trim($_GET['q'] ?? '');

$sql = 'SELECT s.*, c.name as category_name FROM services s JOIN categories c ON s.category_id = c.id WHERE s.status = "active"';
$params = [];

if ($selectedCat > 0) {
    $sql .= ' AND s.category_id = ?';
    $params[] = $selectedCat;
}

if (!empty($search)) {
    $sql .= ' AND (s.name LIKE ? OR s.id = ?)';
    $params[] = '%' . $search . '%';
    $params[] = (int) $search;
}

$sql .= ' ORDER BY c.sort_order ASC, s.id ASC';
$services = Database::fetchAll($sql, $params);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{ 
    activeModal: null,
    serviceDetails: {}
}">

    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-white">Services & Pricing</h1>
        <p class="text-slate-400 text-sm mt-1">Live database listing of active services, rates per 1,000, and order limitations.</p>
    </div>

    <!-- Filters & Search Bar -->
    <div class="p-4 rounded-2xl bg-[#0e1326] border border-slate-800/80 mb-8 flex flex-col md:flex-row gap-4 items-center justify-between">
        
        <!-- Category Filter -->
        <div class="flex items-center gap-2 w-full md:w-auto overflow-x-auto pb-2 md:pb-0">
            <a href="/services<?= !empty($search) ? '?q=' . urlencode($search) : '' ?>" class="px-3.5 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition <?= $selectedCat === 0 ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-300 hover:text-white border border-slate-800' ?>">
                All Categories
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="/services?category=<?= $cat['id'] ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="px-3.5 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition <?= $selectedCat === (int) $cat['id'] ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-300 hover:text-white border border-slate-800' ?>">
                    <?= e($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Search Input -->
        <form method="GET" action="/services" class="w-full md:w-72 relative">
            <?php if ($selectedCat > 0): ?>
                <input type="hidden" name="category" value="<?= $selectedCat ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search services..." class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-900/90 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition">
            <div class="absolute left-3 top-2.5 text-slate-500">
                <?= heroicon('magnifying-glass', 'w-4 h-4') ?>
            </div>
        </form>

    </div>

    <!-- Services Table or Empty State -->
    <?php if (empty($services)): ?>
        <div class="p-16 rounded-2xl bg-[#0e1326] border border-slate-800/80 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-500">
                <?= heroicon('list-bullet', 'w-7 h-7') ?>
            </div>
            <h3 class="text-lg font-bold text-white mb-1">No services available</h3>
            <p class="text-sm text-slate-400 max-w-md mx-auto">
                <?= !empty($search) || $selectedCat > 0 ? 'No services match your active filters. Try resetting search parameters.' : 'There are currently no active services in the database. Services will appear here once added or imported by the administrator.' ?>
            </p>
            <?php if (!empty($search) || $selectedCat > 0): ?>
                <div class="mt-4">
                    <a href="/services" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 transition">
                        Reset Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-slate-800/80 bg-[#0e1326] shadow-xl">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-900/60 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <th class="py-4 px-4 sm:px-6">ID</th>
                        <th class="py-4 px-4">Service</th>
                        <th class="py-4 px-4">Category</th>
                        <th class="py-4 px-4 text-right">Rate / 1K</th>
                        <th class="py-4 px-4 text-center">Min / Max</th>
                        <th class="py-4 px-4 text-center">Features</th>
                        <th class="py-4 px-4 sm:px-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php foreach ($services as $s): ?>
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-4 px-4 sm:px-6 font-mono text-xs text-slate-500 font-semibold">#<?= $s['id'] ?></td>
                            <td class="py-4 px-4 max-w-md">
                                <div class="font-medium text-slate-200"><?= e($s['name']) ?></div>
                                <?php if (!empty($s['description'])): ?>
                                    <p class="text-xs text-slate-400 line-clamp-1 mt-0.5"><?= e(strip_tags($s['description'])) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4 text-xs text-slate-300">
                                <span class="px-2.5 py-1 rounded-md bg-slate-900 border border-slate-800 font-medium">
                                    <?= e($s['category_name']) ?>
                                </span>
                            </td>
                            <td class="py-4 px-4 text-right font-bold text-emerald-400 font-mono">
                                <?= format_currency($s['rate']) ?>
                            </td>
                            <td class="py-4 px-4 text-center text-xs font-mono text-slate-400">
                                <?= number_format($s['min_quantity']) ?> / <?= number_format($s['max_quantity']) ?>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <?php if ($s['refill_supported']): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20" title="Refill Available">Refill</span>
                                    <?php endif; ?>
                                    <?php if ($s['cancel_supported']): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/20" title="Cancel Supported">Cancel</span>
                                    <?php endif; ?>
                                    <?php if ($s['dripfeed_supported']): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20" title="Drip-feed Enabled">Drip</span>
                                    <?php endif; ?>
                                    <?php if (!$s['refill_supported'] && !$s['cancel_supported'] && !$s['dripfeed_supported']): ?>
                                        <span class="text-xs text-slate-600">-</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-4 px-4 sm:px-6 text-right whitespace-nowrap">
                                <button @click="activeModal = <?= $s['id'] ?>; serviceDetails = <?= htmlspecialchars(json_encode([
                                    'id' => $s['id'],
                                    'name' => $s['name'],
                                    'category' => $s['category_name'],
                                    'rate' => format_currency($s['rate']),
                                    'min' => number_format($s['min_quantity']),
                                    'max' => number_format($s['max_quantity']),
                                    'description' => $s['description'] ?: 'No detailed description provided for this service.',
                                    'refill' => (bool) $s['refill_supported'],
                                    'cancel' => (bool) $s['cancel_supported'],
                                ]), ENT_QUOTES, 'UTF-8') ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 transition">
                                    Details
                                </button>
                                <?php if (is_logged_in()): ?>
                                    <a href="/new-order?service_id=<?= $s['id'] ?>" class="ml-2 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white transition">
                                        Order
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Service Details Modal (Alpine.js) -->
    <div x-show="activeModal !== null" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="activeModal = null">
        <div class="min-h-screen px-4 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="activeModal = null"></div>

            <div class="relative bg-[#0e1326] border border-slate-800 rounded-2xl max-w-xl w-full p-6 shadow-2xl z-10" @click.stop>
                <div class="flex items-start justify-between pb-4 border-b border-slate-800">
                    <div>
                        <span class="text-xs font-semibold text-blue-400 uppercase tracking-wider" x-text="serviceDetails.category"></span>
                        <h3 class="text-lg font-bold text-white mt-1" x-text="serviceDetails.name"></h3>
                    </div>
                    <button @click="activeModal = null" class="text-slate-400 hover:text-white">
                        <?= heroicon('x-mark', 'w-5 h-5') ?>
                    </button>
                </div>

                <div class="py-4 space-y-4">
                    <div class="grid grid-cols-3 gap-3 p-3 rounded-xl bg-slate-900/80 border border-slate-800 text-center">
                        <div>
                            <div class="text-[11px] text-slate-500">Rate / 1K</div>
                            <div class="text-sm font-bold text-emerald-400 font-mono" x-text="serviceDetails.rate"></div>
                        </div>
                        <div>
                            <div class="text-[11px] text-slate-500">Min Order</div>
                            <div class="text-sm font-bold text-white font-mono" x-text="serviceDetails.min"></div>
                        </div>
                        <div>
                            <div class="text-[11px] text-slate-500">Max Order</div>
                            <div class="text-sm font-bold text-white font-mono" x-text="serviceDetails.max"></div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Service Description</h4>
                        <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800/80 text-xs text-slate-300 leading-relaxed whitespace-pre-line" x-text="serviceDetails.description"></div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                    <button @click="activeModal = null" class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-300">Close</button>
                    <?php if (is_logged_in()): ?>
                        <a :href="'/new-order?service_id=' + serviceDetails.id" class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white">Create Order</a>
                    <?php else: ?>
                        <a href="/login" class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white">Sign In to Order</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
