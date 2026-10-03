<?php
$pageTitle = 'Manage Ticket #' . ($ticket['id'] ?? '');
$currentAdminRoute = 'tickets';
require_once __DIR__ . '/../layouts/admin_header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="/admin/tickets" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition">
            &larr; Back to All Tickets
        </a>
        <div class="flex items-center gap-3">
            <form method="POST" action="/admin/tickets/status" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs font-semibold text-white focus:outline-none focus:border-blue-500">
                    <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Status: Open</option>
                    <option value="answered" <?= $ticket['status'] === 'answered' ? 'selected' : '' ?>>Status: Answered</option>
                    <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Status: Closed</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Ticket Header Card -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl flex items-start justify-between">
        <div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-mono text-purple-400 font-bold">Ticket #<?= $ticket['id'] ?></span>
                <span class="text-xs text-slate-400">by <strong class="text-white"><?= e($ticket['username']) ?></strong> (<?= e($ticket['email']) ?>)</span>
            </div>
            <h1 class="text-xl font-bold text-white mt-1"><?= e($ticket['subject']) ?></h1>
            <p class="text-xs text-slate-500 mt-1 font-mono">Priority: <span class="uppercase text-amber-400"><?= e($ticket['priority']) ?></span> &bull; <?= format_date($ticket['created_at'], 'M d, Y H:i') ?></p>
        </div>
        <?= status_badge($ticket['status']) ?>
    </div>

    <!-- Messages List -->
    <div class="space-y-4">
        <?php foreach ($messages as $m): ?>
            <div class="p-6 rounded-3xl border <?= $m['is_admin'] ? 'bg-purple-950/20 border-purple-500/30 mr-4 sm:mr-12' : 'bg-[#0e1326] border-slate-800/80 ml-4 sm:ml-12' ?>">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800/60 mb-3 text-xs">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-md <?= $m['is_admin'] ? 'bg-purple-600 text-white' : 'bg-blue-600/30 text-blue-400 border border-blue-500/30' ?> flex items-center justify-center font-bold text-[10px]">
                            <?= $m['is_admin'] ? 'A' : 'U' ?>
                        </div>
                        <span class="font-bold <?= $m['is_admin'] ? 'text-purple-400' : 'text-slate-300' ?>">
                            <?= $m['is_admin'] ? 'Staff Reply (You)' : e($ticket['username']) ?>
                        </span>
                    </div>
                    <span class="text-slate-500 font-mono"><?= format_date($m['created_at'], 'M d, H:i') ?></span>
                </div>
                <div class="text-sm text-slate-300 leading-relaxed whitespace-pre-line">
                    <?= e($m['message']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Admin Reply Box -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl">
        <h3 class="text-sm font-bold text-white mb-3">Post Staff Response</h3>
        <form method="POST" action="/admin/tickets/reply" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
            <div>
                <textarea name="message" rows="4" required placeholder="Write a response to the customer..." class="w-full px-4 py-3 rounded-2xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="submit" class="px-6 py-2.5 rounded-xl font-semibold bg-blue-600 hover:bg-blue-500 text-white text-xs shadow-lg shadow-blue-600/30 transition">
                    Send Response & Mark Answered
                </button>
            </div>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
