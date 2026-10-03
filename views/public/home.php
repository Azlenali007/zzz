<?php
$pageTitle = 'Next-Gen Social Media Growth Platform';
$currentRoute = 'home';
require_once __DIR__ . '/../layouts/header.php';

// Real stats from database
$totalServices = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM services WHERE status = "active"')['cnt'] ?? 0);
$totalOrders = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM orders')['cnt'] ?? 0);
$completedOrders = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM orders WHERE status = "completed"')['cnt'] ?? 0);
$totalUsers = (int) (Database::fetchOne('SELECT COUNT(*) as cnt FROM users WHERE status = "active"')['cnt'] ?? 0);
?>

<div class="relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-28">
    
    <!-- Hero Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        
        <!-- Live System Badge -->
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-900/30 border border-blue-500/30 text-blue-400 text-xs font-semibold uppercase tracking-wider mb-8 gsap-fade-up">
            <span class="w-2 h-2 rounded-full bg-blue-400 animate-ping"></span>
            Direct Provider API Engine &bull; Zero Mock Data
        </div>

        <!-- Main Headline -->
        <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white max-w-4xl mx-auto leading-tight sm:leading-none mb-6 gsap-fade-up">
            Scale Your Social Reach With
            <span class="bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400 bg-clip-text text-transparent">Precision & Speed</span>
        </h1>

        <p class="text-lg sm:text-xl text-slate-400 max-w-2xl mx-auto mb-10 leading-relaxed gsap-fade-up">
            Enterprise-ready SMM panel built on pure PHP & MySQL. Automated order execution, live provider balance sync, and instant delivery across major networks.
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16 gsap-fade-up">
            <a href="/register" class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-semibold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white shadow-xl shadow-blue-600/30 hover:scale-[1.02] transition-all">
                Create Free Account
            </a>
            <a href="/services" class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-semibold bg-slate-900/80 hover:bg-slate-800 text-slate-200 border border-slate-700/80 hover:border-slate-600 transition-all flex items-center justify-center gap-2">
                <?= heroicon('list-bullet', 'w-5 h-5 text-blue-400') ?> Browse Services
            </a>
        </div>

        <!-- Real Database Metrics Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 max-w-5xl mx-auto text-left gsap-fade-up">
            <div class="p-6 rounded-2xl bg-gradient-to-b from-slate-900/90 to-slate-950/90 border border-slate-800/80 backdrop-blur-xl">
                <div class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Active Services</div>
                <div class="text-3xl font-extrabold text-white"><?= number_format($totalServices) ?></div>
                <div class="text-xs text-blue-400 mt-2 flex items-center gap-1">
                    <?= heroicon('check', 'w-3.5 h-3.5') ?> Live in Database
                </div>
            </div>
            <div class="p-6 rounded-2xl bg-gradient-to-b from-slate-900/90 to-slate-950/90 border border-slate-800/80 backdrop-blur-xl">
                <div class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Total Orders Placed</div>
                <div class="text-3xl font-extrabold text-white"><?= number_format($totalOrders) ?></div>
                <div class="text-xs text-emerald-400 mt-2 flex items-center gap-1">
                    <?= heroicon('bolt', 'w-3.5 h-3.5') ?> Automated Pipeline
                </div>
            </div>
            <div class="p-6 rounded-2xl bg-gradient-to-b from-slate-900/90 to-slate-950/90 border border-slate-800/80 backdrop-blur-xl">
                <div class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Completed Orders</div>
                <div class="text-3xl font-extrabold text-emerald-400"><?= number_format($completedOrders) ?></div>
                <div class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                    <?= heroicon('shield-check', 'w-3.5 h-3.5') ?> Verified Fulfillment
                </div>
            </div>
            <div class="p-6 rounded-2xl bg-gradient-to-b from-slate-900/90 to-slate-950/90 border border-slate-800/80 backdrop-blur-xl">
                <div class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Registered Clients</div>
                <div class="text-3xl font-extrabold text-white"><?= number_format($totalUsers) ?></div>
                <div class="text-xs text-purple-400 mt-2 flex items-center gap-1">
                    <?= heroicon('user', 'w-3.5 h-3.5') ?> Active Resellers
                </div>
            </div>
        </div>

    </div>

    <!-- Supported Platforms Banner -->
    <div class="mt-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h2 class="text-xs font-bold uppercase tracking-widest text-slate-400">Supported Networks & Platforms</h2>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
            <?php
            $platforms = [
                ['name' => 'Instagram', 'color' => 'from-pink-500 to-rose-600'],
                ['name' => 'TikTok', 'color' => 'from-cyan-400 to-blue-600'],
                ['name' => 'YouTube', 'color' => 'from-red-500 to-red-700'],
                ['name' => 'Telegram', 'color' => 'from-sky-400 to-blue-500'],
                ['name' => 'Twitter / X', 'color' => 'from-slate-300 to-slate-500'],
                ['name' => 'Facebook', 'color' => 'from-blue-600 to-indigo-700'],
                ['name' => 'Spotify', 'color' => 'from-emerald-400 to-green-600'],
                ['name' => 'Discord', 'color' => 'from-indigo-400 to-purple-600'],
            ];
            foreach ($platforms as $p): ?>
                <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800/80 text-center hover:border-slate-700 transition">
                    <div class="w-8 h-8 mx-auto mb-2 rounded-lg bg-gradient-to-tr <?= $p['color'] ?> flex items-center justify-center text-white font-black text-xs shadow-md">
                        <?= substr($p['name'], 0, 1) ?>
                    </div>
                    <span class="text-xs font-medium text-slate-300"><?= $p['name'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Feature Pillars -->
    <div class="mt-28 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl font-extrabold text-white">Engineered For High-Volume Resellers</h2>
            <p class="text-slate-400 mt-3 text-sm">Every feature designed for reliability, speed, and real-time order tracking.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="p-8 rounded-2xl bg-[#0e1326] border border-slate-800/80 hover:border-blue-500/40 transition">
                <div class="w-12 h-12 rounded-xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center text-blue-400 mb-6">
                    <?= heroicon('bolt', 'w-6 h-6') ?>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Automated Order Routing</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Orders are instantly submitted to configured upstream providers using standardized SMM API v2 protocol with automatic order ID tracking.
                </p>
            </div>
            <div class="p-8 rounded-2xl bg-[#0e1326] border border-slate-800/80 hover:border-blue-500/40 transition">
                <div class="w-12 h-12 rounded-xl bg-purple-600/20 border border-purple-500/30 flex items-center justify-center text-purple-400 mb-6">
                    <?= heroicon('code', 'w-6 h-6') ?>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">High-Speed Reseller API</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Connect your own external websites or bots. Standard JSON API actions for balance checks, service discovery, order placement, and status polling.
                </p>
            </div>
            <div class="p-8 rounded-2xl bg-[#0e1326] border border-slate-800/80 hover:border-blue-500/40 transition">
                <div class="w-12 h-12 rounded-xl bg-emerald-600/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-6">
                    <?= heroicon('shield-check', 'w-6 h-6') ?>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Secure Wallet & Transactions</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Rigorous double-entry transaction auditing prevents balance drift. Manual and automated gateway support with transaction proofs.
                </p>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
