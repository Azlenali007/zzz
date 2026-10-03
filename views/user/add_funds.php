<?php
$pageTitle = 'Add Funds';
$currentRoute = 'add_funds';
require_once __DIR__ . '/../layouts/header.php';

$userId = $user['id'];

// Fetch real active payment gateways from MySQL
$gateways = Database::fetchAll('SELECT * FROM payment_gateways WHERE status = "active" ORDER BY id ASC');

// Fetch user payments
$payments = Database::fetchAll('SELECT * FROM payments WHERE user_id = ? ORDER BY id DESC LIMIT 15', [$userId]);

// Fetch user wallet transactions
$transactions = Database::fetchAll('SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT 15', [$userId]);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{
    gateways: <?= htmlspecialchars(json_encode($gateways), ENT_QUOTES, 'UTF-8') ?>,
    selectedCode: '<?= !empty($gateways) ? $gateways[0]['code'] : '' ?>',
    amount: 25,

    get currentGateway() {
        return this.gateways.find(g => g.code === this.selectedCode) || null;
    },

    get gatewayConfig() {
        if (!this.currentGateway || !this.currentGateway.config_data) return {};
        try {
            return JSON.parse(this.currentGateway.config_data);
        } catch (e) {
            return {};
        }
    },

    get feeAmount() {
        if (!this.currentGateway) return 0;
        const feePct = parseFloat(this.currentGateway.fee_percent) || 0;
        return (parseFloat(this.amount || 0) * feePct) / 100;
    },

    get totalToPay() {
        return parseFloat(this.amount || 0) + this.feeAmount;
    }
}">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-white">Add Funds to Wallet</h1>
            <p class="text-slate-400 text-sm mt-1">Select an active payment channel to credit your balance.</p>
        </div>
        <div class="p-3.5 rounded-2xl bg-[#0e1326] border border-slate-800 flex items-center gap-3">
            <span class="text-xs text-slate-400">Current Balance:</span>
            <span class="text-xl font-extrabold text-emerald-400 font-mono"><?= format_currency($user['balance']) ?></span>
        </div>
    </div>

    <?php if (empty($gateways)): ?>
        <div class="p-16 rounded-2xl bg-[#0e1326] border border-slate-800/80 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-500">
                <?= heroicon('wallet', 'w-7 h-7') ?>
            </div>
            <h3 class="text-lg font-bold text-white mb-1">No payment gateways available</h3>
            <p class="text-sm text-slate-400 max-w-md mx-auto">
                There are currently no active payment methods configured. Please contact the administrator or submit a ticket.
            </p>
        </div>
    <?php else: ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            
            <!-- Deposit Form (2 Cols) -->
            <div class="lg:col-span-2 p-8 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl">
                <form method="POST" action="/add-funds" enctype="multipart/form-data" class="space-y-6">
                    <?= csrf_field() ?>

                    <!-- Gateway Selector -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Select Payment Method</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <template x-for="g in gateways" :key="g.code">
                                <label :class="selectedCode === g.code ? 'border-blue-500 bg-blue-950/20 text-white' : 'border-slate-800 bg-slate-900/60 text-slate-400 hover:border-slate-700'" class="p-4 rounded-xl border flex items-center justify-between cursor-pointer transition">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="gateway" :value="g.code" x-model="selectedCode" class="text-blue-600 focus:ring-0">
                                        <div>
                                            <div class="text-sm font-semibold" x-text="g.name"></div>
                                            <div class="text-[11px] text-slate-500" x-text="'Min: $' + parseFloat(g.min_amount).toFixed(2) + ' / Fee: ' + parseFloat(g.fee_percent).toFixed(1) + '%'"></div>
                                        </div>
                                    </div>
                                    <?= heroicon('credit-card', 'w-5 h-5 text-slate-400') ?>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Deposit Amount -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Amount to Deposit (USD)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-slate-500 font-bold">$</span>
                            <input type="number" name="amount" x-model="amount" step="0.01" :min="currentGateway ? currentGateway.min_amount : 1" :max="currentGateway ? currentGateway.max_amount : 10000" required class="w-full pl-8 pr-4 py-3 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500 transition">
                        </div>
                    </div>

                    <!-- Gateway Specific Instructions -->
                    <div x-show="currentGateway" class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-400">Payment Instructions</h4>
                        <p class="text-xs text-slate-300 leading-relaxed" x-text="currentGateway ? currentGateway.instructions : ''"></p>

                        <!-- Crypto TRC20 Address Display -->
                        <template x-if="gatewayConfig.trc20_address">
                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between font-mono text-xs">
                                <span class="text-slate-400">Deposit Address:</span>
                                <span class="text-emerald-400 font-bold select-all" x-text="gatewayConfig.trc20_address"></span>
                            </div>
                        </template>

                        <!-- Bank Details Display -->
                        <template x-if="gatewayConfig.bank_name">
                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1 font-mono text-xs text-slate-300">
                                <div>Bank: <strong class="text-white" x-text="gatewayConfig.bank_name"></strong></div>
                                <div>Account: <strong class="text-white" x-text="gatewayConfig.account_number"></strong></div>
                                <div>Name: <strong class="text-white" x-text="gatewayConfig.account_name"></strong></div>
                                <div>SWIFT/BIC: <strong class="text-white" x-text="gatewayConfig.swift_code"></strong></div>
                            </div>
                        </template>
                    </div>

                    <!-- Transaction ID / Reference -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Transaction ID / TXID / Reference Hash</label>
                        <input type="text" name="transaction_id" required placeholder="e.g. b29a8f... or bank reference" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono placeholder-slate-500 focus:outline-none focus:border-blue-500 transition">
                        <span class="text-[11px] text-slate-500 mt-1 block">Paste your transaction hash or reference number after completing the transfer.</span>
                    </div>

                    <!-- Optional Receipt Proof Upload -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Upload Proof Receipt (Optional)</label>
                        <input type="file" name="proof" accept="image/png,image/jpeg,image/webp,application/pdf" class="w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700 cursor-pointer">
                    </div>

                    <!-- Calculation Summary -->
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between text-xs font-mono">
                        <span class="text-slate-400">Total Credit to Wallet:</span>
                        <span class="text-base font-bold text-emerald-400" x-text="'$' + parseFloat(amount || 0).toFixed(2)"></span>
                    </div>

                    <button type="submit" class="w-full py-4 rounded-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white shadow-xl shadow-blue-600/30 transition text-sm">
                        Submit Payment Verification
                    </button>
                </form>
            </div>

            <!-- Guidelines Sidebar (1 Col) -->
            <div class="space-y-6">
                <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <?= heroicon('shield-check', 'w-5 h-5 text-emerald-400') ?> Funding Guarantee
                    </h3>
                    <ul class="space-y-2.5 text-xs text-slate-400 leading-relaxed">
                        <li class="flex items-start gap-2">
                            <?= heroicon('check', 'w-4 h-4 text-emerald-400 shrink-0 mt-0.5') ?>
                            <span>Manual deposits are verified by staff within 10-30 minutes.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <?= heroicon('check', 'w-4 h-4 text-emerald-400 shrink-0 mt-0.5') ?>
                            <span>Every wallet modification is audited in double-entry transaction ledgers.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <?= heroicon('check', 'w-4 h-4 text-emerald-400 shrink-0 mt-0.5') ?>
                            <span>Zero hidden processing fees on cryptocurrency deposits.</span>
                        </li>
                    </ul>
                </div>

                <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-2 text-xs text-slate-400">
                    <h4 class="font-bold text-slate-200">Need Immediate Help?</h4>
                    <p>If your payment has not been credited after 1 hour, please open a support ticket with your transaction ID.</p>
                    <a href="/tickets" class="inline-flex items-center gap-1.5 text-blue-400 font-semibold hover:underline mt-2">
                        Open Support Ticket <?= heroicon('arrow-right-on-rectangle', 'w-3.5 h-3.5') ?>
                    </a>
                </div>
            </div>

        </div>

        <!-- Payment History Table -->
        <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4 mb-8">
            <h3 class="text-base font-bold text-white">Deposit Requests & History</h3>
            
            <?php if (empty($payments)): ?>
                <p class="text-xs text-slate-400 py-6 text-center">No deposit transactions submitted yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px]">
                                <th class="pb-3 px-2">ID</th>
                                <th class="pb-3 px-2">Gateway</th>
                                <th class="pb-3 px-2">Amount</th>
                                <th class="pb-3 px-2">Reference / TXID</th>
                                <th class="pb-3 px-2 text-center">Status</th>
                                <th class="pb-3 px-2 text-right">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <?php foreach ($payments as $p): ?>
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-3 px-2 text-slate-500 font-bold">#<?= $p['id'] ?></td>
                                    <td class="py-3 px-2 font-sans font-medium text-slate-300"><?= e(ucfirst(str_replace('_', ' ', $p['gateway']))) ?></td>
                                    <td class="py-3 px-2 font-bold text-emerald-400"><?= format_currency($p['amount']) ?></td>
                                    <td class="py-3 px-2 text-slate-400 max-w-xs truncate"><?= e($p['transaction_id'] ?: '-') ?></td>
                                    <td class="py-3 px-2 text-center font-sans"><?= status_badge($p['status']) ?></td>
                                    <td class="py-3 px-2 text-right text-slate-500"><?= format_date($p['created_at'], 'M d, H:i') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Detailed Wallet Transactions Audit -->
        <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-white">Wallet Transaction Audit Trail</h3>
            
            <?php if (empty($transactions)): ?>
                <p class="text-xs text-slate-400 py-6 text-center">No wallet activity recorded.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px]">
                                <th class="pb-3 px-2">Txn ID</th>
                                <th class="pb-3 px-2">Type</th>
                                <th class="pb-3 px-2">Amount</th>
                                <th class="pb-3 px-2">Balance Before</th>
                                <th class="pb-3 px-2">Balance After</th>
                                <th class="pb-3 px-2 font-sans">Description</th>
                                <th class="pb-3 px-2 text-right">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <?php foreach ($transactions as $t): ?>
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="py-3 px-2 text-slate-500">#<?= $t['id'] ?></td>
                                    <td class="py-3 px-2 font-sans">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold <?= $t['type'] === 'deposit' || $t['type'] === 'refund' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-800 text-slate-300' ?>">
                                            <?= e(ucfirst(str_replace('_', ' ', $t['type']))) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-2 font-bold <?= $t['type'] === 'deposit' || $t['type'] === 'refund' ? 'text-emerald-400' : 'text-slate-300' ?>">
                                        <?= ($t['type'] === 'deposit' || $t['type'] === 'refund' ? '+' : '-') . format_currency(abs($t['amount'])) ?>
                                    </td>
                                    <td class="py-3 px-2 text-slate-400"><?= format_currency($t['balance_before']) ?></td>
                                    <td class="py-3 px-2 font-bold text-white"><?= format_currency($t['balance_after']) ?></td>
                                    <td class="py-3 px-2 font-sans text-slate-300"><?= e($t['description']) ?></td>
                                    <td class="py-3 px-2 text-right text-slate-500"><?= format_date($t['created_at'], 'M d, H:i') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
