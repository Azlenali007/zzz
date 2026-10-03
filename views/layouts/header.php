<?php
$user = auth_user();
$siteName = get_setting('site_name', 'ApexSMM');
$flash = get_flash();
$unreadNotifsCount = 0;
if ($user) {
    $unreadRow = Database::fetchOne('SELECT COUNT(*) as cnt FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0', [$user['id']]);
    $unreadNotifsCount = (int) ($unreadRow['cnt'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? $siteName) ?> - <?= e($siteName) ?></title>
    <meta name="description" content="<?= e(get_setting('site_description', 'High performance SMM Panel')) ?>">
    
    <!-- Vite Production Bundle -->
    <link rel="stylesheet" href="/dist/assets/style.css">
    <script src="/dist/assets/app.js" defer></script>
</head>
<body class="bg-[#070913] text-slate-100 min-h-screen flex flex-col antialiased selection:bg-blue-600 selection:text-white" x-data="{ mobileMenuOpen: false }">

    <!-- Ambient Background Glow -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-600/10 rounded-full blur-[120px]"></div>
        <div class="absolute top-1/3 -right-40 w-96 h-96 bg-purple-600/10 rounded-full blur-[140px]"></div>
    </div>

    <!-- Navigation Header -->
    <header class="relative z-30 border-b border-slate-800/80 bg-[#070913]/80 backdrop-blur-xl sticky top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <a href="<?= $user ? '/dashboard' : '/' ?>" class="flex items-center gap-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center shadow-lg shadow-blue-500/25 group-hover:scale-105 transition-transform duration-300">
                            <?= heroicon('bolt', 'w-6 h-6 text-white') ?>
                        </div>
                        <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-slate-200 to-blue-400 bg-clip-text text-transparent">
                            <?= e($siteName) ?>
                        </span>
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center gap-1.5 lg:gap-3 text-sm font-medium">
                    <?php if ($user): ?>
                        <a href="/dashboard" class="px-3 py-1.5 rounded-lg transition <?= ($currentRoute ?? '') === 'dashboard' ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' ?>">
                            Dashboard
                        </a>
                        <a href="/new-order" class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 <?= ($currentRoute ?? '') === 'new_order' ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' ?>">
                            <?= heroicon('plus', 'w-4 h-4 text-blue-400') ?> New Order
                        </a>
                        <a href="/orders" class="px-3 py-1.5 rounded-lg transition <?= ($currentRoute ?? '') === 'orders' ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' ?>">
                            Orders
                        </a>
                        <a href="/services" class="px-3 py-1.5 rounded-lg transition <?= ($currentRoute ?? '') === 'services' ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' ?>">
                            Services
                        </a>
                        <a href="/add-funds" class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 <?= ($currentRoute ?? '') === 'add_funds' ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' ?>">
                            <?= heroicon('wallet', 'w-4 h-4 text-emerald-400') ?> Add Funds
                        </a>
                        <a href="/tickets" class="px-3 py-1.5 rounded-lg transition <?= ($currentRoute ?? '') === 'tickets' ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' ?>">
                            Tickets
                        </a>
                        <a href="/api-docs" class="px-3 py-1.5 rounded-lg transition <?= ($currentRoute ?? '') === 'api_docs' ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' ?>">
                            API
                        </a>
                    <?php else: ?>
                        <a href="/" class="px-3 py-1.5 rounded-lg transition <?= ($currentRoute ?? '') === 'home' ? 'text-blue-400 font-semibold' : 'text-slate-300 hover:text-white' ?>">Home</a>
                        <a href="/services" class="px-3 py-1.5 rounded-lg transition <?= ($currentRoute ?? '') === 'services' ? 'text-blue-400 font-semibold' : 'text-slate-300 hover:text-white' ?>">Services</a>
                        <a href="/how-it-works" class="px-3 py-1.5 rounded-lg transition <?= ($currentRoute ?? '') === 'how_it_works' ? 'text-blue-400 font-semibold' : 'text-slate-300 hover:text-white' ?>">How It Works</a>
                        <a href="/faq" class="px-3 py-1.5 rounded-lg transition <?= ($currentRoute ?? '') === 'faq' ? 'text-blue-400 font-semibold' : 'text-slate-300 hover:text-white' ?>">FAQ</a>
                        <a href="/contact" class="px-3 py-1.5 rounded-lg transition <?= ($currentRoute ?? '') === 'contact' ? 'text-blue-400 font-semibold' : 'text-slate-300 hover:text-white' ?>">Support</a>
                    <?php endif; ?>
                </nav>

                <!-- Right Action Bar -->
                <div class="flex items-center gap-3">
                    <?php if ($user): ?>
                        
                        <!-- Balance Pill -->
                        <a href="/add-funds" class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 hover:border-slate-700 transition">
                            <span class="text-xs text-slate-400 font-medium">Balance:</span>
                            <span class="text-sm font-semibold text-emerald-400"><?= format_currency($user['balance']) ?></span>
                        </a>

                        <!-- Notifications Bell -->
                        <a href="/notifications" class="relative p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition">
                            <?= heroicon('bell', 'w-5 h-5') ?>
                            <?php if ($unreadNotifsCount > 0): ?>
                                <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                            <?php endif; ?>
                        </a>

                        <!-- User Profile Dropdown -->
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-slate-800/60 transition text-slate-300 hover:text-white">
                                <div class="w-8 h-8 rounded-lg bg-blue-600/20 border border-blue-500/30 flex items-center justify-center font-bold text-xs text-blue-400">
                                    <?= strtoupper(substr($user['username'], 0, 2)) ?>
                                </div>
                                <span class="hidden sm:inline text-sm font-medium"><?= e($user['username']) ?></span>
                            </button>

                            <div x-show="open" x-transition.opacity.duration.200ms class="absolute right-0 mt-2 w-52 rounded-xl bg-[#0e1326] border border-slate-800 shadow-2xl py-1.5 z-50 text-sm">
                                <div class="px-4 py-2 border-b border-slate-800/80">
                                    <p class="text-xs text-slate-400">Signed in as</p>
                                    <p class="font-medium text-white truncate"><?= e($user['email']) ?></p>
                                </div>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <a href="/admin" class="flex items-center gap-2.5 px-4 py-2 text-purple-400 hover:bg-purple-950/40 transition">
                                        <?= heroicon('shield-check', 'w-4 h-4') ?> Admin Panel
                                    </a>
                                <?php endif; ?>
                                <a href="/profile" class="flex items-center gap-2.5 px-4 py-2 text-slate-300 hover:bg-slate-800/60 transition">
                                    <?= heroicon('user', 'w-4 h-4 text-slate-400') ?> Profile & API Key
                                </a>
                                <a href="/add-funds" class="flex items-center gap-2.5 px-4 py-2 text-slate-300 hover:bg-slate-800/60 transition">
                                    <?= heroicon('wallet', 'w-4 h-4 text-slate-400') ?> Add Funds
                                </a>
                                <a href="/tickets" class="flex items-center gap-2.5 px-4 py-2 text-slate-300 hover:bg-slate-800/60 transition">
                                    <?= heroicon('chat-bubble', 'w-4 h-4 text-slate-400') ?> Support Tickets
                                </a>
                                <div class="border-t border-slate-800/80 my-1"></div>
                                <a href="/logout" class="flex items-center gap-2.5 px-4 py-2 text-rose-400 hover:bg-rose-950/30 transition">
                                    <?= heroicon('arrow-right-on-rectangle', 'w-4 h-4') ?> Sign Out
                                </a>
                            </div>
                        </div>

                    <?php else: ?>
                        <a href="/login" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white transition">
                            Sign In
                        </a>
                        <a href="/register" class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 hover:shadow-blue-500/50 transition">
                            Get Started
                        </a>
                    <?php endif; ?>

                    <!-- Mobile Menu Trigger -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60">
                        <?= heroicon('bars-3', 'w-6 h-6') ?>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Drawer -->
        <div x-show="mobileMenuOpen" x-transition.opacity class="md:hidden border-t border-slate-800 bg-[#070913]/95 px-4 pt-3 pb-5 space-y-2">
            <?php if ($user): ?>
                <div class="p-3 mb-2 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                    <div>
                        <div class="text-xs text-slate-400">Balance</div>
                        <div class="text-lg font-bold text-emerald-400"><?= format_currency($user['balance']) ?></div>
                    </div>
                    <a href="/add-funds" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white">Top Up</a>
                </div>
                <a href="/dashboard" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Dashboard</a>
                <a href="/new-order" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">New Order</a>
                <a href="/orders" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Orders</a>
                <a href="/services" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Services</a>
                <a href="/add-funds" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Add Funds</a>
                <a href="/tickets" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Tickets</a>
                <a href="/profile" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Profile & API</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="/admin" class="block px-3 py-2 rounded-lg text-base font-medium text-purple-400 hover:bg-purple-950/40">Admin Panel</a>
                <?php endif; ?>
                <a href="/logout" class="block px-3 py-2 rounded-lg text-base font-medium text-rose-400 hover:bg-rose-950/30">Sign Out</a>
            <?php else: ?>
                <a href="/" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Home</a>
                <a href="/services" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Services</a>
                <a href="/how-it-works" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">How It Works</a>
                <a href="/faq" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">FAQ</a>
                <a href="/contact" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Support</a>
                <div class="pt-2 border-t border-slate-800 flex gap-2">
                    <a href="/login" class="flex-1 text-center py-2 rounded-lg bg-slate-800 text-slate-200 font-medium">Sign In</a>
                    <a href="/register" class="flex-1 text-center py-2 rounded-lg bg-blue-600 text-white font-medium">Get Started</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Global Flash Notification Toast -->
    <?php if ($flash): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 z-20">
            <div class="rounded-xl p-4 flex items-center justify-between border <?= match($flash['type']) {
                'success' => 'bg-emerald-950/60 border-emerald-500/40 text-emerald-200',
                'error' => 'bg-rose-950/60 border-rose-500/40 text-rose-200',
                'warning' => 'bg-amber-950/60 border-amber-500/40 text-amber-200',
                default => 'bg-blue-950/60 border-blue-500/40 text-blue-200',
            } ?> shadow-xl">
                <div class="flex items-center gap-3">
                    <?= match($flash['type']) {
                        'success' => heroicon('check', 'w-5 h-5 text-emerald-400 shrink-0'),
                        'error' => heroicon('x-mark', 'w-5 h-5 text-rose-400 shrink-0'),
                        'warning' => heroicon('exclamation-triangle', 'w-5 h-5 text-amber-400 shrink-0'),
                        default => heroicon('information-circle', 'w-5 h-5 text-blue-400 shrink-0'),
                    } ?>
                    <span class="text-sm font-medium"><?= e($flash['message']) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Page Content Slot -->
    <main class="flex-1 relative z-10">
