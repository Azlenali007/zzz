<?php
$pageTitle = 'Provider API Management';
$currentAdminRoute = 'providers';
require_once __DIR__ . '/../layouts/admin_header.php';

$providers = Database::fetchAll('
    SELECT p.*, COUNT(s.id) as service_count 
    FROM providers p 
    LEFT JOIN services s ON p.id = s.provider_id 
    GROUP BY p.id 
    ORDER BY p.id DESC
');
?>

<div class="space-y-6" x-data="{ createModal: false, editModal: false, activeProvider: {} }">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Upstream Providers</h2>
            <p class="text-xs text-slate-400 mt-0.5">Integrate third-party SMM panels via standard SMM API v2 protocol.</p>
        </div>
        <button @click="createModal = true" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 transition">
            <?= heroicon('plus', 'w-4 h-4') ?> Connect Provider API
        </button>
    </div>

    <!-- Providers Table -->
    <?php if (empty($providers)): ?>
        <div class="p-16 rounded-2xl bg-[#0e1326] border border-slate-800/80 text-center">
            <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-500">
                <?= heroicon('server', 'w-6 h-6') ?>
            </div>
            <h3 class="text-base font-bold text-white mb-1">No providers connected</h3>
            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                Connect your upstream SMM provider (e.g. Peakerr, JustAnotherPanel, Secsers) to automate order fulfillment and import services.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-slate-800/80 bg-[#0e1326] shadow-xl">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-900/60 font-semibold uppercase tracking-wider text-slate-400 text-[10px]">
                        <th class="py-4 px-4 sm:px-6">ID</th>
                        <th class="py-4 px-4">Provider Name</th>
                        <th class="py-4 px-4">API URL</th>
                        <th class="py-4 px-4 text-right">Balance</th>
                        <th class="py-4 px-4 text-center">Mapped Services</th>
                        <th class="py-4 px-4 text-center">Status</th>
                        <th class="py-4 px-4 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-mono">
                    <?php foreach ($providers as $p): ?>
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-4 px-4 sm:px-6 text-slate-500 font-bold">#<?= $p['id'] ?></td>
                            <td class="py-4 px-4 font-sans font-bold text-slate-200"><?= e($p['name']) ?></td>
                            <td class="py-4 px-4 text-slate-400 max-w-xs truncate"><?= e($p['api_url']) ?></td>
                            <td class="py-4 px-4 text-right font-bold text-emerald-400"><?= format_currency($p['balance']) ?> <?= e($p['currency']) ?></td>
                            <td class="py-4 px-4 text-center text-blue-400 font-bold"><?= $p['service_count'] ?></td>
                            <td class="py-4 px-4 text-center font-sans"><?= status_badge($p['status']) ?></td>
                            <td class="py-4 px-4 sm:px-6 text-right font-sans space-x-1 whitespace-nowrap">
                                <form method="POST" action="/admin/providers/test-balance" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="provider_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-600 hover:text-white transition" title="Fetch live balance via API">
                                        Check Balance
                                    </button>
                                </form>
                                <a href="/admin/providers/import?provider_id=<?= $p['id'] ?>" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-purple-600/20 text-purple-400 border border-purple-500/30 hover:bg-purple-600 hover:text-white transition">
                                    Import / Sync
                                </a>
                                <button type="button" @click="activeProvider = <?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>; editModal = true" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                                    Edit
                                </button>
                                <form method="POST" action="/admin/providers/delete" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="provider_id" value="<?= $p['id'] ?>">
                                    <button type="submit" onclick="return confirm('Remove this provider?')" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-rose-950/40 border border-rose-500/30 text-rose-300 hover:bg-rose-900/60 transition">
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

    <!-- Add Provider Modal -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="createModal = false">
        <div class="min-h-screen px-4 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="createModal = false"></div>
            <div class="relative bg-[#0e1326] border border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl z-10" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Connect Provider API</h3>
                    <button @click="createModal = false" class="text-slate-400 hover:text-white"><?= heroicon('x-mark', 'w-5 h-5') ?></button>
                </div>
                <form method="POST" action="/admin/providers/create" class="py-4 space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Provider Name</label>
                        <input type="text" name="name" required placeholder="e.g. GlobalSMM or Secsers" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">API Endpoint URL</label>
                        <input type="url" name="api_url" required placeholder="https://provider.com/api/v2" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Provider API Key</label>
                        <input type="password" name="api_key" required placeholder="••••••••" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" @click="createModal = false" class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30">Connect Provider</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Provider Modal -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="editModal = false">
        <div class="min-h-screen px-4 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="editModal = false"></div>
            <div class="relative bg-[#0e1326] border border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl z-10" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Edit Provider</h3>
                    <button @click="editModal = false" class="text-slate-400 hover:text-white"><?= heroicon('x-mark', 'w-5 h-5') ?></button>
                </div>
                <form method="POST" action="/admin/providers/update" class="py-4 space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="provider_id" :value="activeProvider.id">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Provider Name</label>
                        <input type="text" name="name" x-model="activeProvider.name" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">API Endpoint URL</label>
                        <input type="url" name="api_url" x-model="activeProvider.api_url" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">New API Key (Leave blank to keep existing)</label>
                        <input type="password" name="api_key" placeholder="••••••••" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Status</label>
                        <select name="status" x-model="activeProvider.status" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
