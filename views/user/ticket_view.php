<?php
$pageTitle = 'View Ticket #' . ($ticket['id'] ?? '');
$currentRoute = 'tickets';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
    
    <!-- Top Bar -->
    <div class="flex items-center justify-between">
        <a href="/tickets" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition">
            &larr; Back to Tickets
        </a>
        <div class="flex items-center gap-3">
            <span class="text-xs uppercase font-mono text-slate-500">Priority: <?= e($ticket['priority']) ?></span>
            <?= status_badge($ticket['status']) ?>
        </div>
    </div>

    <!-- Ticket Header Card -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl">
        <div class="flex items-start justify-between">
            <div>
                <span class="text-xs font-mono text-blue-400 font-bold">Ticket #<?= $ticket['id'] ?></span>
                <h1 class="text-xl font-bold text-white mt-1"><?= e($ticket['subject']) ?></h1>
                <p class="text-xs text-slate-500 mt-1 font-mono">Opened on <?= format_date($ticket['created_at'], 'M d, Y H:i') ?></p>
            </div>
        </div>
    </div>

    <!-- Message Thread -->
    <div class="space-y-4">
        <?php foreach ($messages as $m): ?>
            <div class="p-6 rounded-3xl border <?= $m['is_admin'] ? 'bg-purple-950/20 border-purple-500/30 ml-4 sm:ml-12' : 'bg-[#0e1326] border-slate-800/80 mr-4 sm:mr-12' ?>">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800/60 mb-3 text-xs">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-md <?= $m['is_admin'] ? 'bg-purple-600 text-white' : 'bg-blue-600/30 text-blue-400 border border-blue-500/30' ?> flex items-center justify-center font-bold text-[10px]">
                            <?= $m['is_admin'] ? 'A' : 'U' ?>
                        </div>
                        <span class="font-bold <?= $m['is_admin'] ? 'text-purple-400' : 'text-slate-300' ?>">
                            <?= $m['is_admin'] ? 'Support Administrator' : e($user['username']) ?>
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

    <!-- Reply Box -->
    <?php if ($ticket['status'] !== 'closed'): ?>
        <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl">
            <h3 class="text-sm font-bold text-white mb-3">Post a Reply</h3>
            <form method="POST" action="/tickets/reply" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                <div>
                    <textarea name="message" rows="4" required placeholder="Type your response here..." class="w-full px-4 py-3 rounded-2xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-xl font-semibold bg-blue-600 hover:bg-blue-500 text-white text-xs shadow-lg shadow-blue-600/30 transition">
                        Send Reply
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 text-center text-xs text-slate-500">
            This support ticket has been closed. Please open a new ticket if you require further assistance.
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
