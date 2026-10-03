<?php
$pageTitle = 'Service Management';
$currentAdminRoute = 'services';
require_once __DIR__ . '/../layouts/admin_header.php';

$categories = Database::fetchAll('SELECT * FROM categories ORDER BY sort_order ASC, name ASC');
$providers = Database::fetchAll('SELECT * FROM providers ORDER BY name ASC');

$selectedCat = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$search = trim($_GET['q'] ?? '');

$sql = '
    SELECT s.*, c.name as category_name, p.name as provider_name 
    FROM services s 
    LEFT JOIN categories c ON s.category_id = c.id 
    LEFT JOIN providers p ON s.provider_id = p.id 
    WHERE 1=1
';
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

$sql .= ' ORDER BY s.id DESC';
$services = Database::fetchAll($sql, $params);
?>

<div class="space-y-6" x-data="{
    createModal: false,
    editModal: false,
    activeService: {},
    categories: <?= htmlspecialchars(json_encode($categories), ENT_QUOTES, 'UTF-8') ?>,
    providers: <?= htmlspecialchars(json_encode($providers), ENT_QUOTES, 'UTF-8') ?>
}">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Services & Rates</h2>
            <p class="text-xs text-slate-400 mt-0.5">Define selling rates, minimum/maximum limits, and provider API mappings.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/providers/import" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-purple-600/20 text-purple-300 border border-purple-500/30 hover:bg-purple-600 hover:text-white transition">
                <?= heroicon('document-arrow-down', 'w-4 h-4') ?> Import From API
            </a>
            <button @click="createModal = true" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 transition">
                <?= heroicon('plus', 'w-4 h-4') ?> Add Real Service
            </button>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="p-4 rounded-2xl bg-[#0e1326] border border-slate-800/80 flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="flex items-center gap-2 w-full sm:w-auto overflow-x-auto pb-2 sm:pb-0 text-xs">
            <a href="/admin/services<?= !empty($search) ? '?q=' . urlencode($search) : '' ?>" class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition <?= $selectedCat === 0 ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-300 hover:text-white border border-slate-800' ?>">All Categories</a>
            <?php foreach ($categories as $cat): ?>
                <a href="/admin/services?category=<?= $cat['id'] ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition <?= $selectedCat === (int)$cat['id'] ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-300 hover:text-white border border-slate-800' ?>">
                    <?= e($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <form method="GET" action="/admin/services" class="w-full sm:w-72 relative">
            <?php if ($selectedCat > 0): ?>
                <input type="hidden" name="category" value="<?= $selectedCat ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search service name, ID..." class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition">
            <div class="absolute left-3 top-2.5 text-slate-500">
                <?= heroicon('magnifying-glass', 'w-4 h-4') ?>
            </div>
        </form>
    </div>

    <!-- Services Table -->
    <?php if (empty($services)): ?>
        <div class="p-16 rounded-2xl bg-[#0e1326] border border-slate-800/80 text-center">
            <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-500">
                <?= heroicon('list-bullet', 'w-6 h-6') ?>
            </div>
            <h3 class="text-base font-bold text-white mb-1">No services found</h3>
            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                No services available yet. You can manually create a service or import from an upstream provider API.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-slate-800/80 bg-[#0e1326] shadow-xl">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-900/60 font-semibold uppercase tracking-wider text-slate-400 text-[10px]">
                        <th class="py-4 px-4 sm:px-6">ID</th>
                        <th class="py-4 px-4">Service Name</th>
                        <th class="py-4 px-4">Category</th>
                        <th class="py-4 px-4 text-right">Selling Rate</th>
                        <th class="py-4 px-4 text-right">Provider Cost</th>
                        <th class="py-4 px-4 text-center">Min / Max</th>
                        <th class="py-4 px-4">Provider</th>
                        <th class="py-4 px-4 text-center">Status</th>
                        <th class="py-4 px-4 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php foreach ($services as $s): ?>
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-4 px-4 sm:px-6 font-mono text-slate-500 font-bold">#<?= $s['id'] ?></td>
                            <td class="py-4 px-4 max-w-xs font-bold text-slate-200">
                                <div><?= e($s['name']) ?></div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <?php if ($s['refill_supported']): ?>
                                        <span class="text-[9px] px-1.5 py-0.2 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20">Refill</span>
                                    <?php endif; ?>
                                    <?php if ($s['cancel_supported']): ?>
                                        <span class="text-[9px] px-1.5 py-0.2 rounded bg-purple-500/10 text-purple-400 border border-purple-500/20">Cancel</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-slate-300 font-medium"><?= e($s['category_name'] ?: 'Uncategorized') ?></td>
                            <td class="py-4 px-4 text-right font-mono font-bold text-emerald-400"><?= format_currency($s['rate']) ?></td>
                            <td class="py-4 px-4 text-right font-mono text-slate-400"><?= format_currency($s['original_rate']) ?></td>
                            <td class="py-4 px-4 text-center font-mono text-slate-400"><?= number_format($s['min_quantity']) ?> / <?= number_format($s['max_quantity']) ?></td>
                            <td class="py-4 px-4 font-mono text-slate-300">
                                <?php if ($s['provider_name']): ?>
                                    <span class="text-purple-400"><?= e($s['provider_name']) ?></span> (ID: <?= e($s['provider_service_id']) ?>)
                                <?php else: ?>
                                    <span class="text-slate-600">Manual</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4 text-center"><?= status_badge($s['status']) ?></td>
                            <td class="py-4 px-4 sm:px-6 text-right space-x-1 whitespace-nowrap">
                                <button type="button" @click="activeService = <?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>; editModal = true" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                                    Edit
                                </button>
                                <form method="POST" action="/admin/services/delete" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                                    <button type="submit" onclick="return confirm('Delete this service permanently?')" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-rose-950/40 border border-rose-500/30 text-rose-300 hover:bg-rose-900/60 transition">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Add Service Modal -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="createModal = false">
        <div class="min-h-screen px-4 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="createModal = false"></div>
            <div class="relative bg-[#0e1326] border border-slate-800 rounded-3xl max-w-2xl w-full p-6 shadow-2xl z-10" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Add New Service</h3>
                    <button @click="createModal = false" class="text-slate-400 hover:text-white"><?= heroicon('x-mark', 'w-5 h-5') ?></button>
                </div>
                <form method="POST" action="/admin/services/create" class="py-4 space-y-4">
                    <?= csrf_field() ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Category</label>
                            <select name="category_id" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                                <template x-for="cat in categories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Service Name</label>
                            <input type="text" name="name" required placeholder="e.g. Instagram Real Likes" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Selling Rate / 1K ($)</label>
                            <input type="number" step="0.0001" name="rate" required placeholder="1.5000" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Original Provider Cost ($)</label>
                            <input type="number" step="0.0001" name="original_rate" value="0.0000" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Min Quantity</label>
                            <input type="number" name="min_quantity" value="10" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Max Quantity</label>
                            <input type="number" name="max_quantity" value="10000" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Provider Integration (Optional)</label>
                            <select name="provider_id" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                                <option value="">-- Manual (No API) --</option>
                                <template x-for="p in providers" :key="p.id">
                                    <option :value="p.id" x-text="p.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Provider Service ID</label>
                            <input type="text" name="provider_service_id" placeholder="e.g. 429" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="flex items-center gap-6 py-2">
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                            <input type="checkbox" name="refill_supported" value="1" class="rounded bg-slate-900 border-slate-800 text-blue-600">
                            <span>Refill Guaranteed</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                            <input type="checkbox" name="cancel_supported" value="1" class="rounded bg-slate-900 border-slate-800 text-purple-600">
                            <span>Cancel Supported</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Service Description / Instructions</label>
                        <textarea name="description" rows="3" placeholder="Notes for users (delivery speed, start time, link format)" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500"></textarea>
                    </div>

                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" @click="createModal = false" class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30">Save Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Service Modal -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="editModal = false">
        <div class="min-h-screen px-4 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="editModal = false"></div>
            <div class="relative bg-[#0e1326] border border-slate-800 rounded-3xl max-w-2xl w-full p-6 shadow-2xl z-10" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Edit Service #<span x-text="activeService.id"></span></h3>
                    <button @click="editModal = false" class="text-slate-400 hover:text-white"><?= heroicon('x-mark', 'w-5 h-5') ?></button>
                </div>
                <form method="POST" action="/admin/services/update" class="py-4 space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="service_id" :value="activeService.id">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Category</label>
                            <select name="category_id" x-model="activeService.category_id" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                                <template x-for="cat in categories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Service Name</label>
                            <input type="text" name="name" x-model="activeService.name" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Selling Rate / 1K ($)</label>
                            <input type="number" step="0.0001" name="rate" x-model="activeService.rate" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Original Provider Cost ($)</label>
                            <input type="number" step="0.0001" name="original_rate" x-model="activeService.original_rate" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Min Quantity</label>
                            <input type="number" name="min_quantity" x-model="activeService.min_quantity" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Max Quantity</label>
                            <input type="number" name="max_quantity" x-model="activeService.max_quantity" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Provider Integration</label>
                            <select name="provider_id" x-model="activeService.provider_id" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                                <option value="">-- Manual (No API) --</option>
                                <template x-for="p in providers" :key="p.id">
                                    <option :value="p.id" x-text="p.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Provider Service ID</label>
                            <input type="text" name="provider_service_id" x-model="activeService.provider_service_id" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 py-2">
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                            <input type="checkbox" name="refill_supported" value="1" :checked="activeService.refill_supported == 1" class="rounded bg-slate-900 border-slate-800 text-blue-600">
                            <span>Refill Guaranteed</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                            <input type="checkbox" name="cancel_supported" value="1" :checked="activeService.cancel_supported == 1" class="rounded bg-slate-900 border-slate-800 text-purple-600">
                            <span>Cancel Supported</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-slate-400">Status:</label>
                            <select name="status" x-model="activeService.status" class="px-2 py-1 rounded bg-slate-900 border border-slate-800 text-xs text-white">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Description / Instructions</label>
                        <textarea name="description" x-model="activeService.description" rows="3" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500"></textarea>
                    </div>

                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30">Update Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
