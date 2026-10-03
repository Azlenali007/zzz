<?php
$user = auth_user();
$siteName = get_setting('site_name', 'ApexSMM');
$flash = get_flash();

// Pending stats for admin badges
$pendingPaymentsCount = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM payments WHERE status = "pending"')['cnt'] ?? 0);
$openTicketsCount = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM tickets WHERE status = "open"')['cnt'] ?? 0);
$pendingOrdersCount = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM orders WHERE status = "pending"')['cnt'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Admin Control') ?> - <?= e($siteName) ?> Admin</title>
    
    <!-- Vite Production Bundle -->
    <link rel="stylesheet" href="/dist/assets/style.css">
    <script src="/dist/assets/app.js" defer></script>
</head>
<body class="bg-[#070913] text-slate-100 min-h-screen flex antialiased" x-data="{ sidebarOpen: false }">

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition.opacity class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"></div>

    <!-- Admin Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-[#0a0e1e] border-r border-slate-800/80 flex flex-col transition-transform duration-300 ease-in-out">
        
        <!-- Logo -->
        <div class="h-16 px-6 flex items-center justify-between border-b border-slate-800/80">
            <a href="/admin" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-purple-600 to-blue-600 flex items-center justify-center shadow-lg shadow-purple-500/20">
                    <?= heroicon('shield-check', 'w-5 h-5 text-white') ?>
                </div>
                <div>
                    <span class="font-bold tracking-tight text-white"><?= e($siteName) ?></span>
                    <span class="text-[10px] uppercase tracking-wider block font-semibold text-purple-400">Administration</span>
                </div>
            </a>
            <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">
                <?= heroicon('x-mark', 'w-5 h-5') ?>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-4 py-5 space-y-1 text-sm font-medium">
            <a href="/admin" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= ($currentAdminRoute ?? '') === 'dashboard' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                <?= heroicon('home', 'w-5 h-5') ?>
                <span>Dashboard</span>
            </a>

            <div class="pt-4 pb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Core Management</div>
            
            <a href="/admin/orders" class="flex items-center justify-between px-3 py-2.5 rounded-xl transition <?= ($currentAdminRoute ?? '') === 'orders' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                <div class="flex items-center gap-3">
                    <?= heroicon('shopping-cart', 'w-5 h-5') ?>
                    <span>Orders</span>
                </div>
                <?php if ($pendingOrdersCount > 0): ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30"><?= $pendingOrdersCount ?></span>
                <?php endif; ?>
            </a>

            <a href="/admin/services" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= ($currentAdminRoute ?? '') === 'services' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                <?= heroicon('list-bullet', 'w-5 h-5') ?>
                <span>Services & Rates</span>
            </a>

            <a href="/admin/categories" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= ($currentAdminRoute ?? '') === 'categories' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                <?= heroicon('funnel', 'w-5 h-5') ?>
                <span>Categories</span>
            </a>

            <a href="/admin/providers" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= ($currentAdminRoute ?? '') === 'providers' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                <?= heroicon('server', 'w-5 h-5') ?>
                <span>Provider APIs & Sync</span>
            </a>

            <div class="pt-4 pb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Finance & Users</div>

            <a href="/admin/users" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= ($currentAdminRoute ?? '') === 'users' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                <?= heroicon('users', 'w-5 h-5') ?>
                <span>User Accounts</span>
            </a>

            <a href="/admin/payments" class="flex items-center justify-between px-3 py-2.5 rounded-xl transition <?= ($currentAdminRoute ?? '') === 'payments' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                <div class="flex items-center gap-3">
                    <?= heroicon('credit-card', 'w-5 h-5') ?>
                    <span>Payments & Gateways</span>
                </div>
                <?php if ($pendingPaymentsCount > 0): ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30"><?= $pendingPaymentsCount ?></span>
                <?php endif; ?>
            </a>

            <a href="/admin/tickets" class="flex items-center justify-between px-3 py-2.5 rounded-xl transition <?= ($currentAdminRoute ?? '') === 'tickets' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                <div class="flex items-center gap-3">
                    <?= heroicon('chat-bubble', 'w-5 h-5') ?>
                    <span>Support Tickets</span>
                </div>
                <?php if ($openTicketsCount > 0): ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-500/20 text-rose-300 border border-rose-500/30"><?= $openTicketsCount ?></span>
                <?php endif; ?>
            </a>

            <div class="pt-4 pb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Settings & Tools</div>

            <a href="/admin/announcements" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= ($currentAdminRoute ?? '') === 'announcements' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                <?= heroicon('bell', 'w-5 h-5') ?>
                <span>Announcements</span>
            </a>

            <a href="/admin/settings" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= ($currentAdminRoute ?? '') === 'settings' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                <?= heroicon('cog', 'w-5 h-5') ?>
                <span>System Settings</span>
            </a>

            <a href="/admin/tools" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition <?= ($currentAdminRoute ?? '') === 'tools' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                <?= heroicon('wrench-screwdriver', 'w-5 h-5') ?>
                <span>System Tools & Logs</span>
            </a>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-slate-800/80 space-y-2">
            <a href="/dashboard" class="flex items-center justify-center gap-2 w-full py-2 px-3 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                <?= heroicon('arrow-right-on-rectangle', 'w-4 h-4') ?> Return to Client Panel
            </a>
            <div class="flex items-center justify-between text-xs text-slate-400 px-1 pt-1">
                <span>MySQL Active</span>
                <span>v1.0.0</span>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        
        <!-- Admin Topbar -->
        <header class="h-16 border-b border-slate-800/80 bg-[#070913]/90 backdrop-blur-md px-6 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-slate-400 hover:text-white">
                    <?= heroicon('bars-3', 'w-6 h-6') ?>
                </button>
                <h1 class="text-lg font-bold text-white"><?= e($pageTitle ?? 'Administration') ?></h1>
            </div>
            
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-500/10 text-purple-400 border border-purple-500/20">
                    <?= heroicon('shield-check', 'w-3.5 h-3.5') ?> Admin Mode
                </span>
                <span class="text-sm text-slate-300 font-medium"><?= e($user['username']) ?></span>
                <a href="/logout" class="p-2 rounded-lg text-rose-400 hover:bg-rose-950/30 transition" title="Log Out">
                    <?= heroicon('arrow-right-on-rectangle', 'w-5 h-5') ?>
                </a>
            </div>
        </header>

        <!-- Admin Flash Notifications -->
        <?php if ($flash): ?>
            <div class="px-6 pt-4">
                <div class="rounded-xl p-4 flex items-center gap-3 border <?= match($flash['type']) {
                    'success' => 'bg-emerald-950/60 border-emerald-500/40 text-emerald-200',
                    'error' => 'bg-rose-950/60 border-rose-500/40 text-rose-200',
                    'warning' => 'bg-amber-950/60 border-amber-500/40 text-amber-200',
                    default => 'bg-blue-950/60 border-blue-500/40 text-blue-200',
                } ?>">
                    <?= match($flash['type']) {
                        'success' => heroicon('check', 'w-5 h-5 text-emerald-400 shrink-0'),
                        'error' => heroicon('x-mark', 'w-5 h-5 text-rose-400 shrink-0'),
                        'warning' => heroicon('exclamation-triangle', 'w-5 h-5 text-amber-400 shrink-0'),
                        default => heroicon('information-circle', 'w-5 h-5 text-blue-400 shrink-0'),
                    } ?>
                    <span class="text-sm font-medium"><?= e($flash['message']) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Content Slot -->
        <main class="flex-1 p-6">
