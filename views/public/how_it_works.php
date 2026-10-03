<?php
$pageTitle = 'How It Works';
$currentRoute = 'how_it_works';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center mb-16">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-900/30 border border-blue-500/30 text-blue-400 text-xs font-semibold uppercase tracking-wider mb-4">
            Workflow Overview
        </div>
        <h1 class="text-4xl font-extrabold text-white">How ApexSMM Works</h1>
        <p class="text-slate-400 text-base mt-3 max-w-xl mx-auto">
            From registration to order fulfillment in four simple, automated steps.
        </p>
    </div>

    <div class="space-y-8 relative before:absolute before:inset-0 before:left-8 before:w-0.5 before:bg-gradient-to-b before:from-blue-600 before:via-purple-600 before:to-transparent before:hidden md:before:block">
        
        <!-- Step 1 -->
        <div class="relative flex flex-col md:flex-row items-start gap-6 p-6 rounded-2xl bg-[#0e1326] border border-slate-800/80 hover:border-blue-500/40 transition">
            <div class="w-16 h-16 rounded-2xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center text-blue-400 font-extrabold text-xl shrink-0 shadow-lg shadow-blue-500/10">
                01
            </div>
            <div class="space-y-2">
                <h3 class="text-xl font-bold text-white">Create Your Account</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Sign up with your username and email address. Your account is activated immediately with dedicated API keys and an encrypted personal wallet.
                </p>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="relative flex flex-col md:flex-row items-start gap-6 p-6 rounded-2xl bg-[#0e1326] border border-slate-800/80 hover:border-purple-500/40 transition">
            <div class="w-16 h-16 rounded-2xl bg-purple-600/20 border border-purple-500/30 flex items-center justify-center text-purple-400 font-extrabold text-xl shrink-0 shadow-lg shadow-purple-500/10">
                02
            </div>
            <div class="space-y-2">
                <h3 class="text-xl font-bold text-white">Deposit Funds to Your Wallet</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Choose from available payment methods such as USDT Crypto, Manual Bank Wire, or Cards. Once verified, funds are directly credited to your real account balance.
                </p>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="relative flex flex-col md:flex-row items-start gap-6 p-6 rounded-2xl bg-[#0e1326] border border-slate-800/80 hover:border-blue-500/40 transition">
            <div class="w-16 h-16 rounded-2xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center text-blue-400 font-extrabold text-xl shrink-0 shadow-lg shadow-blue-500/10">
                03
            </div>
            <div class="space-y-2">
                <h3 class="text-xl font-bold text-white">Select Service & Place Order</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Select your target platform, choose from real active services, enter your target link and quantity. The exact price is automatically calculated and deducted with zero hidden fees.
                </p>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="relative flex flex-col md:flex-row items-start gap-6 p-6 rounded-2xl bg-[#0e1326] border border-slate-800/80 hover:border-emerald-500/40 transition">
            <div class="w-16 h-16 rounded-2xl bg-emerald-600/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-extrabold text-xl shrink-0 shadow-lg shadow-emerald-500/10">
                04
            </div>
            <div class="space-y-2">
                <h3 class="text-xl font-bold text-white">Automated Execution & Real-Time Tracking</h3>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Orders are routed straight to configured upstream providers. Watch real-time start counts, remaining amounts, and fulfillment statuses right from your orders dashboard.
                </p>
            </div>
        </div>

    </div>

    <!-- CTA Box -->
    <div class="mt-16 p-8 rounded-2xl bg-gradient-to-r from-blue-900/40 via-purple-900/30 to-slate-900 border border-blue-500/30 text-center">
        <h3 class="text-2xl font-bold text-white mb-2">Ready to Get Started?</h3>
        <p class="text-slate-400 text-sm max-w-lg mx-auto mb-6">Create your account in seconds and access high-speed social media services.</p>
        <a href="/register" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 transition">
            Create Free Account
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
