<?php
$pageTitle = 'Support Tickets';
$currentRoute = 'tickets';
require_once __DIR__ . '/../layouts/header.php';

$userId = $user['id'];
$tickets = Database::fetchAll('SELECT * FROM tickets WHERE user_id = ? ORDER BY id DESC', [$userId]);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{ createModalOpen: false }">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-white">Support Tickets</h1>
            <p class="text-slate-400 text-sm mt-1">Submit inquiries regarding orders, refill requests, or billing.</p>
        </div>
        <button @click="createModalOpen = true" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 transition text-sm">
            <?= heroicon('plus', 'w-4 h-4') ?> Open New Ticket
        </button>
    </div>

    <!-- Tickets Table -->
    <?php if (empty($tickets)): ?>
        <div class="p-16 rounded-2xl bg-[#0e1326] border border-slate-800/80 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-500">
                <?= heroicon('chat-bubble', 'w-7 h-7') ?>
            </div>
            <h3 class="text-lg font-bold text-white mb-1">No support tickets found</h3>
            <p class="text-sm text-slate-400 max-w-md mx-auto">
                You have not opened any support requests yet. If you have an inquiry, open a new ticket above.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-slate-800/80 bg-[#0e1326] shadow-xl">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-900/60 font-semibold uppercase tracking-wider text-slate-400 text-[11px]">
                        <th class="py-4 px-4 sm:px-6">Ticket ID</th>
                        <th class="py-4 px-4">Subject</th>
                        <th class="py-4 px-4 text-center">Priority</th>
                        <th class="py-4 px-4 text-center">Status</th>
                        <th class="py-4 px-4 text-right">Created</th>
                        <th class="py-4 px-4 sm:px-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php foreach ($tickets as $t): ?>
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-4 px-4 sm:px-6 font-mono text-slate-500 font-bold">#<?= $t['id'] ?></td>
                            <td class="py-4 px-4 font-semibold text-slate-200">
                                <a href="/tickets/view?id=<?= $t['id'] ?>" class="hover:text-blue-400 transition">
                                    <?= e($t['subject']) ?>
                                </a>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase <?= match($t['priority']) {
                                    'high' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
                                    'low' => 'bg-slate-800 text-slate-400',
                                    default => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
                                } ?>">
                                    <?= e($t['priority']) ?>
                                </span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <?= status_badge($t['status']) ?>
                            </td>
                            <td class="py-4 px-4 text-right font-mono text-slate-500 whitespace-nowrap">
                                <?= format_date($t['created_at'], 'M d, H:i') ?>
                            </td>
                            <td class="py-4 px-4 sm:px-6 text-right whitespace-nowrap">
                                <a href="/tickets/view?id=<?= $t['id'] ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 transition">
                                    View Thread
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Create Ticket Modal -->
    <div x-show="createModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="createModalOpen = false">
        <div class="min-h-screen px-4 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="createModalOpen = false"></div>

            <div class="relative bg-[#0e1326] border border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl z-10" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <h3 class="text-lg font-bold text-white">Create New Support Ticket</h3>
                    <button @click="createModalOpen = false" class="text-slate-400 hover:text-white">
                        <?= heroicon('x-mark', 'w-5 h-5') ?>
                    </button>
                </div>

                <form method="POST" action="/tickets/create" class="py-4 space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Subject</label>
                        <input type="text" name="subject" required placeholder="e.g. Order #123 Refill or Payment Query" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Priority</label>
                        <select name="priority" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                            <option value="medium">Medium</option>
                            <option value="high">High (Urgent)</option>
                            <option value="low">Low</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Message / Details</label>
                        <textarea name="message" rows="5" required placeholder="Describe your issue with relevant order IDs or TXIDs..." class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500"></textarea>
                    </div>

                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" @click="createModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30">Submit Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
