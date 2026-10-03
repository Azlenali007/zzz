<?php
$pageTitle = 'Payments & Gateways';
$currentAdminRoute = 'payments';
require_once __DIR__ . '/../layouts/admin_header.php';

$gateways = Database::fetchAll('SELECT * FROM payment_gateways ORDER BY id ASC');

// Pending deposits
$pendingPayments = Database::fetchAll('
    SELECT p.*, u.username, u.email 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.status = "pending" 
    ORDER BY p.id ASC
');

// Completed/History payments
$historyPayments = Database::fetchAll('
    SELECT p.*, u.username 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.status != "pending" 
    ORDER BY p.id DESC 
    LIMIT 20
');
?>

<div class="space-y-8" x-data="{
    editGatewayModal: false,
    activeGateway: {}
}">

    <div>
        <h2 class="text-2xl font-bold text-white">Payments & Gateways</h2>
        <p class="text-xs text-slate-400 mt-0.5">Configure deposit methods and approve client balance top-up requests.</p>
    </div>

    <!-- Pending Payment Approvals Card -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                <h3 class="text-base font-bold text-white">Pending Deposits Awaiting Approval (<?= count($pendingPayments) ?>)</h3>
            </div>
        </div>

        <?php if (empty($pendingPayments)): ?>
            <p class="text-xs text-slate-500 py-6 text-center">No pending deposits requiring verification.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-mono">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px]">
                            <th class="pb-3 px-3">ID</th>
                            <th class="pb-3 px-3 font-sans">Client</th>
                            <th class="pb-3 px-3 font-sans">Gateway</th>
                            <th class="pb-3 px-3 text-right">Amount</th>
                            <th class="pb-3 px-3">TXID / Reference</th>
                            <th class="pb-3 px-3 text-right">Date</th>
                            <th class="pb-3 px-3 text-right font-sans">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php foreach ($pendingPayments as $pp): ?>
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3 px-3 text-slate-500 font-bold">#<?= $pp['id'] ?></td>
                                <td class="py-3 px-3 font-sans font-bold text-slate-200"><?= e($pp['username']) ?></td>
                                <td class="py-3 px-3 font-sans font-medium text-purple-400"><?= e(ucfirst(str_replace('_', ' ', $pp['gateway']))) ?></td>
                                <td class="py-3 px-3 text-right font-bold text-emerald-400 text-sm"><?= format_currency($pp['amount']) ?></td>
                                <td class="py-3 px-3 text-slate-300 max-w-xs truncate select-all"><?= e($pp['transaction_id'] ?: '-') ?></td>
                                <td class="py-3 px-3 text-right text-slate-500"><?= format_date($pp['created_at'], 'M d, H:i') ?></td>
                                <td class="py-3 px-3 text-right font-sans space-x-2 whitespace-nowrap">
                                    <form method="POST" action="/admin/payments/approve" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="payment_id" value="<?= $pp['id'] ?>">
                                        <button type="submit" onclick="return confirm('Approve deposit and credit <?= format_currency($pp['amount']) ?> to user?')" class="px-3 py-1 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-600/30 transition">
                                            Approve & Credit
                                        </button>
                                    </form>
                                    <form method="POST" action="/admin/payments/reject" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="payment_id" value="<?= $pp['id'] ?>">
                                        <button type="submit" onclick="return confirm('Reject this deposit request?')" class="px-3 py-1 rounded-lg text-xs font-semibold bg-rose-950/40 border border-rose-500/30 text-rose-300 hover:bg-rose-900/60 transition">
                                            Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Payment Gateways Configuration Card -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
        <h3 class="text-base font-bold text-white">Configured Payment Gateways</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($gateways as $g): ?>
                <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800 flex flex-col justify-between space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-white text-sm"><?= e($g['name']) ?></span>
                                <span class="text-[10px] font-mono uppercase px-2 py-0.5 rounded bg-slate-800 text-slate-400"><?= e($g['code']) ?></span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1 line-clamp-2"><?= e($g['instructions']) ?></p>
                        </div>
                        <?= status_badge($g['status']) ?>
                    </div>

                    <div class="flex items-center justify-between text-xs pt-3 border-t border-slate-800 font-mono text-slate-400">
                        <div>
                            Limits: <strong>$<?= number_format($g['min_amount'], 2) ?></strong> - <strong>$<?= number_format($g['max_amount'], 2) ?></strong>
                        </div>
                        <button type="button" @click="activeGateway = <?= htmlspecialchars(json_encode($g), ENT_QUOTES, 'UTF-8') ?>; editGatewayModal = true" class="text-blue-400 font-semibold hover:underline font-sans">
                            Configure &rarr;
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Edit Gateway Modal -->
    <div x-show="editGatewayModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="editGatewayModal = false">
        <div class="min-h-screen px-4 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="editGatewayModal = false"></div>
            <div class="relative bg-[#0e1326] border border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl z-10" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Configure <span x-text="activeGateway.name"></span></h3>
                    <button @click="editGatewayModal = false" class="text-slate-400 hover:text-white"><?= heroicon('x-mark', 'w-5 h-5') ?></button>
                </div>
                <form method="POST" action="/admin/payments/update-gateway" class="py-4 space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="gateway_id" :value="activeGateway.id">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Min Deposit ($)</label>
                            <input type="number" step="0.01" name="min_amount" x-model="activeGateway.min_amount" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Max Deposit ($)</label>
                            <input type="number" step="0.01" name="max_amount" x-model="activeGateway.max_amount" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Fee Percentage (%)</label>
                            <input type="number" step="0.01" name="fee_percent" x-model="activeGateway.fee_percent" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Status</label>
                            <select name="status" x-model="activeGateway.status" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                                <option value="active">Active (Visible to Clients)</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Instructions for Client</label>
                        <textarea name="instructions" x-model="activeGateway.instructions" rows="3" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Configuration JSON (Wallet Addresses, Credentials)</label>
                        <textarea name="config_data" x-model="activeGateway.config_data" rows="3" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white font-mono focus:outline-none focus:border-blue-500"></textarea>
                    </div>

                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" @click="editGatewayModal = false" class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30">Save Gateway</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
