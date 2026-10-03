<?php
$pageTitle = 'Import & Sync Services';
$currentAdminRoute = 'providers';
require_once __DIR__ . '/../layouts/admin_header.php';

$providers = Database::fetchAll('SELECT * FROM providers WHERE status = "active" ORDER BY name ASC');
$providerId = isset($_GET['provider_id']) ? (int) $_GET['provider_id'] : (!empty($providers) ? (int)$providers[0]['id'] : 0);
$selectedProvider = null;
foreach ($providers as $p) {
    if ((int)$p['id'] === $providerId) {
        $selectedProvider = $p;
        break;
    }
}

$fetchedServices = $_SESSION['fetched_services'] ?? null;
unset($_SESSION['fetched_services']);
$fetchError = $_SESSION['fetch_error'] ?? null;
unset($_SESSION['fetch_error']);
?>

<div class="space-y-6" x-data="{
    profitMargin: 30,
    selectedServices: [],
    selectAll: false,

    toggleAll(totalCount) {
        if (this.selectAll) {
            this.selectedServices = Array.from({length: totalCount}, (_, i) => i);
        } else {
            this.selectedServices = [];
        }
    }
}">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">Import & Sync Services</h2>
            <p class="text-xs text-slate-400 mt-0.5">Fetch live catalog directly from provider API and map into your database.</p>
        </div>
        <a href="/admin/providers" class="text-xs font-semibold text-slate-400 hover:text-white transition">
            &larr; Return to Providers
        </a>
    </div>

    <!-- Provider Selection & Actions Card -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-6">
        <?php if (empty($providers)): ?>
            <div class="p-8 text-center text-xs text-slate-400">
                No active providers found. <a href="/admin/providers" class="text-blue-400 font-bold hover:underline">Connect a provider API first</a>.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Select Upstream Provider</label>
                    <select onchange="window.location.href='/admin/providers/import?provider_id=' + this.value" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 font-medium">
                        <?php foreach ($providers as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $p['id'] == $providerId ? 'selected' : '' ?>>
                                <?= e($p['name']) ?> (<?= format_currency($p['balance']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Profit Margin Percentage</label>
                    <div class="relative">
                        <input type="number" x-model="profitMargin" min="0" max="500" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        <span class="absolute right-4 top-2.5 text-slate-500 font-bold">%</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Fetch Services Form -->
                    <form method="POST" action="/admin/providers/fetch-services" class="flex-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="provider_id" value="<?= $providerId ?>">
                        <button type="submit" class="w-full py-2.5 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 transition flex items-center justify-center gap-2">
                            <?= heroicon('document-arrow-down', 'w-4 h-4') ?> Fetch Live Services
                        </button>
                    </form>

                    <!-- Sync Existing Services Form -->
                    <form method="POST" action="/admin/providers/sync-services">
                        <?= csrf_field() ?>
                        <input type="hidden" name="provider_id" value="<?= $providerId ?>">
                        <button type="submit" onclick="return confirm('Synchronize existing services for this provider?')" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition" title="Sync rates & status without overwriting custom prices">
                            <?= heroicon('arrow-path', 'w-4 h-4 text-emerald-400') ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Error notice -->
    <?php if ($fetchError): ?>
        <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-500/40 text-rose-200 text-xs flex items-center gap-3">
            <?= heroicon('exclamation-triangle', 'w-5 h-5 text-rose-400 shrink-0') ?>
            <span><?= e($fetchError) ?></span>
        </div>
    <?php endif; ?>

    <!-- Fetched Services Table -->
    <?php if ($fetchedServices !== null): ?>
        <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-white">Fetched Services (<?= count($fetchedServices) ?>)</h3>
                    <p class="text-xs text-slate-400">Select which services to import into your database.</p>
                </div>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                        <input type="checkbox" x-model="selectAll" @change="toggleAll(<?= count($fetchedServices) ?>)" class="rounded bg-slate-900 border-slate-800 text-blue-600">
                        <span>Select All</span>
                    </label>
                </div>
            </div>

            <form method="POST" action="/admin/providers/save-imported" id="importForm">
                <?= csrf_field() ?>
                <input type="hidden" name="provider_id" value="<?= $providerId ?>">
                <input type="hidden" name="profit_margin" :value="profitMargin">

                <div class="overflow-x-auto max-h-[600px] rounded-xl border border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-slate-900 z-10">
                            <tr class="border-b border-slate-800 font-semibold uppercase text-slate-400 text-[10px]">
                                <th class="py-3 px-3 text-center">Import</th>
                                <th class="py-3 px-2">Provider ID</th>
                                <th class="py-3 px-4">Service Name</th>
                                <th class="py-3 px-3">Category</th>
                                <th class="py-3 px-3 text-right">Cost</th>
                                <th class="py-3 px-3 text-right">Selling Rate</th>
                                <th class="py-3 px-3 text-center">Min / Max</th>
                                <th class="py-3 px-3 text-center">Refill</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono">
                            <?php foreach ($fetchedServices as $idx => $fs): 
                                $cost = (float) ($fs['rate'] ?? 0);
                            ?>
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-2.5 px-3 text-center">
                                        <input type="checkbox" name="services[<?= $idx ?>][import]" value="1" :checked="selectedServices.includes(<?= $idx ?>)" class="rounded bg-slate-900 border-slate-800 text-blue-600">
                                        <input type="hidden" name="services[<?= $idx ?>][service]" value="<?= e($fs['service'] ?? '') ?>">
                                        <input type="hidden" name="services[<?= $idx ?>][name]" value="<?= e($fs['name'] ?? '') ?>">
                                        <input type="hidden" name="services[<?= $idx ?>][category]" value="<?= e($fs['category'] ?? 'General') ?>">
                                        <input type="hidden" name="services[<?= $idx ?>][rate]" value="<?= $cost ?>">
                                        <input type="hidden" name="services[<?= $idx ?>][min]" value="<?= (int) ($fs['min'] ?? 10) ?>">
                                        <input type="hidden" name="services[<?= $idx ?>][max]" value="<?= (int) ($fs['max'] ?? 10000) ?>">
                                        <input type="hidden" name="services[<?= $idx ?>][refill]" value="<?= !empty($fs['refill']) ? '1' : '0' ?>">
                                        <input type="hidden" name="services[<?= $idx ?>][cancel]" value="<?= !empty($fs['cancel']) ? '1' : '0' ?>">
                                        <input type="hidden" name="services[<?= $idx ?>][desc]" value="<?= e($fs['desc'] ?? '') ?>">
                                    </td>
                                    <td class="py-2.5 px-2 text-slate-500 font-bold">#<?= e($fs['service'] ?? '') ?></td>
                                    <td class="py-2.5 px-4 font-sans font-medium text-slate-200 max-w-sm truncate"><?= e($fs['name'] ?? '') ?></td>
                                    <td class="py-2.5 px-3 text-slate-300 font-sans"><?= e($fs['category'] ?? 'General') ?></td>
                                    <td class="py-2.5 px-3 text-right text-slate-400 font-bold"><?= format_currency($cost) ?></td>
                                    <td class="py-2.5 px-3 text-right text-emerald-400 font-bold" x-text="'$' + (<?= $cost ?> * (1 + (profitMargin / 100))).toFixed(4)"></td>
                                    <td class="py-2.5 px-3 text-center text-slate-400"><?= number_format((int)($fs['min'] ?? 10)) ?> / <?= number_format((int)($fs['max'] ?? 10000)) ?></td>
                                    <td class="py-2.5 px-3 text-center font-sans">
                                        <?= !empty($fs['refill']) ? '<span class="text-blue-400 font-bold">Yes</span>' : '<span class="text-slate-600">No</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pt-4 flex items-center justify-between border-t border-slate-800">
                    <span class="text-xs text-slate-400">Total catalog items ready for import: <?= count($fetchedServices) ?></span>
                    <button type="submit" class="px-6 py-3 rounded-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white shadow-xl shadow-blue-600/30 transition text-sm">
                        Import Checked Services into Database
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
