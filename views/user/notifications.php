<?php
$pageTitle = 'Notifications';
$currentRoute = 'notifications';
require_once __DIR__ . '/../layouts/header.php';

$userId = $user['id'];

// Mark all as read if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    if (csrf_verify()) {
        Database::execute('UPDATE notifications SET is_read = 1 WHERE user_id = ? OR user_id IS NULL', [$userId]);
        set_flash('success', 'All notifications marked as read.');
        redirect('/notifications');
    }
}

$notifications = Database::fetchAll('
    SELECT * FROM notifications 
    WHERE (user_id = ? OR user_id IS NULL) 
    ORDER BY id DESC 
    LIMIT 50
', [$userId]);
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-white">Notifications</h1>
            <p class="text-slate-400 text-sm mt-1">Platform updates, order alerts, and payment receipts.</p>
        </div>
        <?php if (!empty($notifications)): ?>
            <form method="POST" action="/notifications">
                <?= csrf_field() ?>
                <input type="hidden" name="mark_all_read" value="1">
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                    Mark All as Read
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="p-16 rounded-2xl bg-[#0e1326] border border-slate-800/80 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-500">
                <?= heroicon('bell', 'w-7 h-7') ?>
            </div>
            <h3 class="text-lg font-bold text-white mb-1">No notifications yet</h3>
            <p class="text-sm text-slate-400 max-w-md mx-auto">
                Order updates and important announcements will appear in this feed.
            </p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($notifications as $n): ?>
                <div class="p-5 rounded-2xl border <?= $n['is_read'] ? 'bg-[#0e1326] border-slate-800/80' : 'bg-slate-900/90 border-blue-500/40' ?> flex items-start gap-4 transition">
                    <div class="w-10 h-10 rounded-xl bg-blue-600/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0 mt-0.5">
                        <?= heroicon('bell', 'w-5 h-5') ?>
                    </div>
                    <div class="flex-1 space-y-1">
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-sm text-white"><?= e($n['title']) ?></h4>
                            <span class="text-[11px] font-mono text-slate-500"><?= format_date($n['created_at'], 'M d, Y H:i') ?></span>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed"><?= e($n['message']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
