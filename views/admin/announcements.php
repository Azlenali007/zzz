<?php
$pageTitle = 'Announcements & Notifications';
$currentAdminRoute = 'announcements';
require_once __DIR__ . '/../layouts/admin_header.php';

$announcements = Database::fetchAll('
    SELECT n.*, u.username 
    FROM notifications n 
    LEFT JOIN users u ON n.user_id = u.id 
    ORDER BY n.id DESC 
    LIMIT 30
');
?>

<div class="space-y-8" x-data="{ createModal: false }">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">Broadcast Announcements</h2>
            <p class="text-xs text-slate-400 mt-0.5">Push notifications and platform news directly into user dashboards.</p>
        </div>
        <button @click="createModal = true" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 transition">
            <?= heroicon('plus', 'w-4 h-4') ?> New Announcement
        </button>
    </div>

    <!-- Announcements List -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
        <h3 class="text-base font-bold text-white">Recent Broadcasts</h3>

        <?php if (empty($announcements)): ?>
            <p class="text-xs text-slate-500 py-8 text-center">No announcements broadcasted yet.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($announcements as $a): ?>
                    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-sm text-white"><?= e($a['title']) ?></span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono <?= $a['user_id'] ? 'bg-purple-500/10 text-purple-400' : 'bg-blue-500/10 text-blue-400' ?>">
                                    <?= $a['user_id'] ? 'Target: @' . e($a['username']) : 'Global Broadcast' ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 leading-relaxed"><?= e($a['message']) ?></p>
                        </div>
                        <div class="text-right text-[11px] font-mono text-slate-500 shrink-0">
                            <?= format_date($a['created_at'], 'M d, H:i') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Create Announcement Modal -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="createModal = false">
        <div class="min-h-screen px-4 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="createModal = false"></div>
            <div class="relative bg-[#0e1326] border border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl z-10" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Publish Broadcast</h3>
                    <button @click="createModal = false" class="text-slate-400 hover:text-white"><?= heroicon('x-mark', 'w-5 h-5') ?></button>
                </div>
                <form method="POST" action="/admin/announcements/create" class="py-4 space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Announcement Title</label>
                        <input type="text" name="title" required placeholder="e.g. Instagram Service Updates or Payment Maintenance" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Notification Type</label>
                        <select name="type" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                            <option value="info">Information (Blue)</option>
                            <option value="success">Success (Green)</option>
                            <option value="warning">Warning / Alert (Amber)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Message Content</label>
                        <textarea name="message" rows="4" required placeholder="Write the message that users will see..." class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500"></textarea>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" @click="createModal = false" class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30">Broadcast Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
