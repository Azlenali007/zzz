<?php
$pageTitle = 'Contact & Support';
$currentRoute = 'contact';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center mb-12">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-900/30 border border-blue-500/30 text-blue-400 text-xs font-semibold uppercase tracking-wider mb-4">
            Direct Assistance
        </div>
        <h1 class="text-4xl font-extrabold text-white">Contact & Support</h1>
        <p class="text-slate-400 text-sm mt-3">We are here to assist with order issues, API inquiries, and high-volume billing.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- Channels -->
        <div class="space-y-4">
            <div class="p-6 rounded-2xl bg-[#0e1326] border border-slate-800/80">
                <div class="w-10 h-10 rounded-xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center text-blue-400 mb-3">
                    <?= heroicon('chat-bubble', 'w-5 h-5') ?>
                </div>
                <h4 class="font-bold text-white text-base">Support Tickets</h4>
                <p class="text-xs text-slate-400 mt-1 mb-4">For registered users with existing orders, tickets offer the fastest resolution.</p>
                <a href="/tickets" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-400 hover:text-blue-300">
                    Open Ticket <?= heroicon('arrow-right-on-rectangle', 'w-3.5 h-3.5') ?>
                </a>
            </div>

            <div class="p-6 rounded-2xl bg-[#0e1326] border border-slate-800/80">
                <div class="w-10 h-10 rounded-xl bg-sky-600/20 border border-sky-500/30 flex items-center justify-center text-sky-400 mb-3">
                    <?= heroicon('paper-airplane', 'w-5 h-5') ?>
                </div>
                <h4 class="font-bold text-white text-base">Telegram Channel</h4>
                <p class="text-xs text-slate-400 mt-1 mb-4">Urgent inquiries and service update announcements.</p>
                <span class="text-xs font-mono font-medium text-slate-300"><?= e(get_setting('contact_telegram', '@ApexSMMSupport')) ?></span>
            </div>

            <div class="p-6 rounded-2xl bg-[#0e1326] border border-slate-800/80">
                <div class="w-10 h-10 rounded-xl bg-purple-600/20 border border-purple-500/30 flex items-center justify-center text-purple-400 mb-3">
                    <?= heroicon('bolt', 'w-5 h-5') ?>
                </div>
                <h4 class="font-bold text-white text-base">Direct Email</h4>
                <p class="text-xs text-slate-400 mt-1 mb-4">Enterprise inquiries, partnership, and custom provider setup.</p>
                <span class="text-xs font-mono font-medium text-slate-300"><?= e(get_setting('contact_email', 'support@apexsmm.com')) ?></span>
            </div>
        </div>

        <!-- Contact / Quick Inquiry Card -->
        <div class="md:col-span-2 p-8 rounded-2xl bg-[#0e1326] border border-slate-800/80">
            <h3 class="text-xl font-bold text-white mb-2">Send an Inquiry</h3>
            <p class="text-xs text-slate-400 mb-6">Need help with an order or account? Send our team a direct message.</p>

            <form method="POST" action="/contact" class="space-y-4">
                <?= csrf_field() ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Your Name</label>
                        <input type="text" name="name" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Email Address</label>
                        <input type="email" name="email" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Subject / Order ID (Optional)</label>
                    <input type="text" name="subject" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Your Message</label>
                    <textarea name="message" rows="5" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500"></textarea>
                </div>

                <button type="submit" class="px-6 py-3 rounded-xl font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 transition text-sm">
                    Submit Inquiry
                </button>
            </form>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
