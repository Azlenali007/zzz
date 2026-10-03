<?php
$pageTitle = 'Sign In';
$currentRoute = 'login';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full p-8 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-2xl relative overflow-hidden">
        
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/25">
                <?= heroicon('bolt', 'w-7 h-7 text-white') ?>
            </div>
            <h2 class="text-2xl font-extrabold text-white">Welcome Back</h2>
            <p class="text-xs text-slate-400 mt-1">Sign in to your <?= e(get_setting('site_name', 'ApexSMM')) ?> dashboard</p>
        </div>

        <form method="POST" action="/login" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Username or Email</label>
                <div class="relative">
                    <input type="text" name="login" required autofocus placeholder="username or email" class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition">
                    <div class="absolute left-3 top-3 text-slate-500">
                        <?= heroicon('user', 'w-4 h-4') ?>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Password</label>
                </div>
                <div class="relative">
                    <input type="password" name="password" required placeholder="••••••••" class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition">
                    <div class="absolute left-3 top-3 text-slate-500">
                        <?= heroicon('key', 'w-4 h-4') ?>
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3 rounded-xl font-semibold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white shadow-lg shadow-blue-600/30 transition text-sm">
                    Sign In
                </button>
            </div>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-800/80 text-center text-xs text-slate-400">
            Don't have an account? 
            <a href="/register" class="text-blue-400 font-semibold hover:underline">Create one now</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
