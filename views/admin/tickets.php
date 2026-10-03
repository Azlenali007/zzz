<?php
$pageTitle = 'Support Tickets';
$currentAdminRoute = 'tickets';
require_once __DIR__ . '/../layouts/admin_header.php';

$statusFilter = strtolower(trim($_GET['status'] ?? 'all'));

$sql = '
    SELECT t.*, u.username, u.email 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE 1=1
';
$params = [];

if ($statusFilter !== 'all') {
    $sql .= ' AND t.status = ?';
    $params[] = $statusFilter;
}

$sql .= ' ORDER BY t.id DESC';
$tickets = Database::fetchAll($sql, $params);
?>

<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">Customer Support Tickets</h2>
            <p class="text-xs text-slate-400 mt-0.5">Respond to inquiries, order status requests, and technical issues.</p>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="p-3 rounded-2xl bg-[#0e1326] border border-slate-800/80 flex items-center gap-2 text-xs">
        <a href="/admin/tickets" class="px-3.5 py-1.5 rounded-lg font-medium transition <?= $statusFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-300 hover:text-white border border-slate-800' ?>">All Tickets</a>
        <a href="/admin/tickets?status=open" class="px-3.5 py-1.5 rounded-lg font-medium transition <?= $statusFilter === 'open' ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-300 hover:text-white border border-slate-800' ?>">Open / Awaiting</a>
        <a href="/admin/tickets?status=answered" class="px-3.5 py-1.5 rounded-lg font-medium transition <?= $statusFilter === 'answered' ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-300 hover:text-white border border-slate-800' ?>">Answered</a>
        <a href="/admin/tickets?status=closed" class="px-3.5 py-1.5 rounded-lg font-medium transition <?= $statusFilter === 'closed' ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-300 hover:text-white border border-slate-800' ?>">Closed</a>
    </div>

    <!-- Tickets Table -->
    <?php if (empty($tickets)): ?>
        <div class="p-16 rounded-2xl bg-[#0e1326] border border-slate-800/80 text-center">
            <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-500">
                <?= heroicon('chat-bubble', 'w-6 h-6') ?>
            </div>
            <h3 class="text-base font-bold text-white mb-1">No support tickets found</h3>
            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                No tickets currently match the selected filter.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-slate-800/80 bg-[#0e1326] shadow-xl">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-900/60 font-semibold uppercase tracking-wider text-slate-400 text-[10px]">
                        <th class="py-4 px-4 sm:px-6">ID</th>
                        <th class="py-4 px-4">User</th>
                        <th class="py-4 px-4">Subject</th>
                        <th class="py-4 px-4 text-center">Priority</th>
                        <th class="py-4 px-4 text-center">Status</th>
                        <th class="py-4 px-4 text-right">Created</th>
                        <th class="py-4 px-4 sm:px-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-mono">
                    <?php foreach ($tickets as $t): ?>
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-4 px-4 sm:px-6 text-slate-500 font-bold">#<?= $t['id'] ?></td>
                            <td class="py-4 px-4 font-sans font-bold text-slate-200"><?= e($t['username']) ?></td>
                            <td class="py-4 px-4 font-sans font-medium text-white max-w-md truncate">
                                <a href="/admin/tickets/view?id=<?= $t['id'] ?>" class="hover:text-blue-400 transition">
                                    <?= e($t['subject']) ?>
                                </a>
                            </td>
                            <td class="py-4 px-4 text-center font-sans">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase <?= match($t['priority']) {
                                    'high' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
                                    'low' => 'bg-slate-800 text-slate-400',
                                    default => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
                                } ?>">
                                    <?= e($t['priority']) ?>
                                </span>
                            </td>
                            <td class="py-4 px-4 text-center font-sans"><?= status_badge($t['status']) ?></td>
                            <td class="py-4 px-4 text-right text-slate-500 whitespace-nowrap"><?= format_date($t['created_at'], 'M d, H:i') ?></td>
                            <td class="py-4 px-4 sm:px-6 text-right font-sans whitespace-nowrap">
                                <a href="/admin/tickets/view?id=<?= $t['id'] ?>" class="px-3 py-1 rounded-lg text-xs font-semibold bg-blue-600/20 text-blue-400 border border-blue-500/30 hover:bg-blue-600 hover:text-white transition">
                                    Open Ticket
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
