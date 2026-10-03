<?php
$pageTitle = 'User Management';
$currentAdminRoute = 'users';
require_once __DIR__ . '/../layouts/admin_header.php';

$search = trim($_GET['q'] ?? '');
$statusFilter = strtolower(trim($_GET['status'] ?? 'all'));

$sql = 'SELECT * FROM users WHERE role = "user"';
$params = [];

if ($statusFilter !== 'all') {
    $sql .= ' AND status = ?';
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $sql .= ' AND (username LIKE ? OR email LIKE ? OR id = ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = (int) $search;
}

$sql .= ' ORDER BY id DESC';
$users = Database::fetchAll($sql, $params);
?>

<div class="space-y-6" x-data="{
    balanceModal: false,
    selectedUser: null,
    adjustType: 'add',
    adjustAmount: '',
    adjustReason: '',

    openBalanceModal(user) {
        this.selectedUser = user;
        this.adjustType = 'add';
        this.adjustAmount = '';
        this.adjustReason = '';
        this.balanceModal = true;
    }
}">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Client Accounts</h2>
            <p class="text-xs text-slate-400 mt-0.5">Manage registered users, balance adjustments, and security status.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="p-4 rounded-2xl bg-[#0e1326] border border-slate-800/80 flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="flex items-center gap-2">
            <a href="/admin/users" class="px-3 py-1.5 rounded-lg text-xs font-medium transition <?= $statusFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-300 hover:text-white border border-slate-800' ?>">All Users</a>
            <a href="/admin/users?status=active" class="px-3 py-1.5 rounded-lg text-xs font-medium transition <?= $statusFilter === 'active' ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-300 hover:text-white border border-slate-800' ?>">Active</a>
            <a href="/admin/users?status=banned" class="px-3 py-1.5 rounded-lg text-xs font-medium transition <?= $statusFilter === 'banned' ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-300 hover:text-white border border-slate-800' ?>">Banned</a>
        </div>

        <form method="GET" action="/admin/users" class="w-full sm:w-72 relative">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search username, email, ID..." class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition">
            <div class="absolute left-3 top-2.5 text-slate-500">
                <?= heroicon('magnifying-glass', 'w-4 h-4') ?>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <?php if (empty($users)): ?>
        <div class="p-16 rounded-2xl bg-[#0e1326] border border-slate-800/80 text-center">
            <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-500">
                <?= heroicon('users', 'w-6 h-6') ?>
            </div>
            <h3 class="text-base font-bold text-white mb-1">No users found</h3>
            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                No client accounts matched your criteria or no users registered yet.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-slate-800/80 bg-[#0e1326] shadow-xl">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-900/60 font-semibold uppercase tracking-wider text-slate-400 text-[10px]">
                        <th class="py-4 px-4 sm:px-6">ID</th>
                        <th class="py-4 px-4">User</th>
                        <th class="py-4 px-4">Email</th>
                        <th class="py-4 px-4 text-right">Balance</th>
                        <th class="py-4 px-4 text-center">Status</th>
                        <th class="py-4 px-4 text-right">Registered</th>
                        <th class="py-4 px-4 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-4 px-4 sm:px-6 font-mono text-slate-500 font-bold">#<?= $u['id'] ?></td>
                            <td class="py-4 px-4 font-bold text-slate-200"><?= e($u['username']) ?></td>
                            <td class="py-4 px-4 text-slate-400 font-mono"><?= e($u['email']) ?></td>
                            <td class="py-4 px-4 text-right font-mono font-bold text-emerald-400"><?= format_currency($u['balance']) ?></td>
                            <td class="py-4 px-4 text-center"><?= status_badge($u['status']) ?></td>
                            <td class="py-4 px-4 text-right font-mono text-slate-500"><?= format_date($u['created_at'], 'M d, Y') ?></td>
                            <td class="py-4 px-4 sm:px-6 text-right space-x-1 whitespace-nowrap">
                                <button type="button" @click="openBalanceModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>)" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-600 hover:text-white transition">
                                    Adjust Funds
                                </button>
                                
                                <form method="POST" action="/admin/users/toggle-status" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" onclick="return confirm('Change status for this user?')" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold <?= $u['status'] === 'active' ? 'bg-rose-600/20 text-rose-400 border border-rose-500/30 hover:bg-rose-600 hover:text-white' : 'bg-blue-600/20 text-blue-400 border border-blue-500/30 hover:bg-blue-600 hover:text-white' ?> transition">
                                        <?= $u['status'] === 'active' ? 'Ban' : 'Activate' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Adjust Balance Modal -->
    <div x-show="balanceModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="balanceModal = false">
        <div class="min-h-screen px-4 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="balanceModal = false"></div>

            <div class="relative bg-[#0e1326] border border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl z-10" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Adjust User Balance</h3>
                    <button @click="balanceModal = false" class="text-slate-400 hover:text-white">
                        <?= heroicon('x-mark', 'w-5 h-5') ?>
                    </button>
                </div>

                <form method="POST" action="/admin/users/adjust-balance" class="py-4 space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" :value="selectedUser ? selectedUser.id : ''">

                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs">
                        <span class="text-slate-400">Target User:</span>
                        <span class="font-bold text-white ml-1" x-text="selectedUser ? selectedUser.username + ' (Current: $' + parseFloat(selectedUser.balance).toFixed(4) + ')' : ''"></span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Action</label>
                        <select name="type" x-model="adjustType" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                            <option value="add">Add Funds (Credit)</option>
                            <option value="deduct">Deduct Funds (Debit)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Amount ($)</label>
                        <input type="number" step="0.0001" min="0.0001" name="amount" x-model="adjustAmount" required placeholder="0.00" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Reason / Audit Note</label>
                        <input type="text" name="reason" required placeholder="e.g. Manual bank deposit, refund compensation" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>

                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" @click="balanceModal = false" class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-600/30">Apply Adjustment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
