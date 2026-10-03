<?php
$pageTitle = 'Account Profile & API Key';
$currentRoute = 'profile';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

    <div>
        <h1 class="text-3xl font-extrabold text-white">Profile & Security</h1>
        <p class="text-slate-400 text-sm mt-1">Manage your credentials and reseller API keys.</p>
    </div>

    <!-- API Key Section -->
    <div class="p-8 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4" x-data="{ copied: false }">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <?= heroicon('code', 'w-5 h-5 text-blue-400') ?> Reseller API Access
                </h3>
                <p class="text-xs text-slate-400 mt-1">Use this secret key to authenticate requests to our Reseller API v2.</p>
            </div>
            <a href="/api-docs" class="text-xs font-semibold text-blue-400 hover:underline">API Documentation &rarr;</a>
        </div>

        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 font-mono text-xs">
            <span class="text-emerald-400 font-bold select-all truncate"><?= e($user['api_key'] ?: 'No API key generated yet.') ?></span>
            
            <div class="flex items-center gap-2">
                <button type="button" @click="navigator.clipboard.writeText('<?= e($user['api_key']) ?>'); copied = true; setTimeout(() => copied = false, 2000)" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 transition text-xs font-sans font-semibold">
                    <span x-show="!copied">Copy Key</span>
                    <span x-show="copied" x-cloak class="text-emerald-400 font-bold">Copied!</span>
                </button>

                <form method="POST" action="/profile/regenerate-api">
                    <?= csrf_field() ?>
                    <button type="submit" onclick="return confirm('Regenerating will invalidate your current API key immediately. Continue?')" class="px-3.5 py-2 rounded-xl bg-rose-950/40 hover:bg-rose-900/60 border border-rose-500/30 text-rose-300 transition text-xs font-sans font-semibold">
                        Regenerate
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Profile Details & Change Password Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Account Info -->
        <div class="p-8 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <?= heroicon('user', 'w-5 h-5 text-purple-400') ?> Account Details
            </h3>

            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between py-2.5 border-b border-slate-800">
                    <span class="text-slate-400">Username:</span>
                    <span class="text-white font-bold font-mono"><?= e($user['username']) ?></span>
                </div>
                <div class="flex items-center justify-between py-2.5 border-b border-slate-800">
                    <span class="text-slate-400">Email Address:</span>
                    <span class="text-white font-medium"><?= e($user['email']) ?></span>
                </div>
                <div class="flex items-center justify-between py-2.5 border-b border-slate-800">
                    <span class="text-slate-400">Account Role:</span>
                    <span class="uppercase font-bold text-blue-400 font-mono"><?= e($user['role']) ?></span>
                </div>
                <div class="flex items-center justify-between py-2.5 border-b border-slate-800">
                    <span class="text-slate-400">Member Since:</span>
                    <span class="text-slate-300 font-mono"><?= format_date($user['created_at'], 'M d, Y') ?></span>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <span class="text-slate-400">Account Status:</span>
                    <?= status_badge($user['status']) ?>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="p-8 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <?= heroicon('key', 'w-5 h-5 text-blue-400') ?> Change Password
            </h3>

            <form method="POST" action="/profile/change-password" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Current Password</label>
                    <input type="password" name="current_password" required placeholder="••••••••" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">New Password</label>
                    <input type="password" name="new_password" required minlength="6" placeholder="Min 6 characters" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Confirm New Password</label>
                    <input type="password" name="confirm_password" required minlength="6" placeholder="Repeat new password" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                <button type="submit" class="w-full py-3 rounded-xl font-semibold bg-blue-600 hover:bg-blue-500 text-white text-xs shadow-lg shadow-blue-600/30 transition">
                    Update Password
                </button>
            </form>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
