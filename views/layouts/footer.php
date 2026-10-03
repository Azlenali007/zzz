    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 bg-[#070913]/90 relative z-20 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div class="space-y-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center shadow-md shadow-blue-500/30">
                            <?= heroicon('bolt', 'w-5 h-5 text-white') ?>
                        </div>
                        <span class="text-lg font-bold tracking-tight text-white"><?= e(get_setting('site_name', 'ApexSMM')) ?></span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        <?= e(get_setting('site_tagline', 'High performance SMM services powered by direct provider integrations.')) ?>
                    </p>
                </div>
                <div>
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Platform</h4>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li><a href="/services" class="hover:text-blue-400 transition">Services List</a></li>
                        <li><a href="/how-it-works" class="hover:text-blue-400 transition">How It Works</a></li>
                        <li><a href="/faq" class="hover:text-blue-400 transition">Frequently Asked</a></li>
                        <li><a href="/api-docs" class="hover:text-blue-400 transition">Reseller API</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Customer Support</h4>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li><a href="/tickets" class="hover:text-blue-400 transition">Support Tickets</a></li>
                        <li><a href="/contact" class="hover:text-blue-400 transition">Contact Us</a></li>
                        <li><span class="text-slate-500">Telegram: <?= e(get_setting('contact_telegram', '@ApexSMMSupport')) ?></span></li>
                        <li><span class="text-slate-500">Email: <?= e(get_setting('contact_email', 'support@apexsmm.com')) ?></span></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Security & Quality</h4>
                    <p class="text-xs text-slate-400 mb-3 leading-relaxed">
                        Encrypted transactions, automated order routing, and enterprise-grade uptime.
                    </p>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-950/40 border border-emerald-500/30 text-emerald-400 text-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        All Systems Operational
                    </div>
                </div>
            </div>
            <div class="pt-6 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
                <p>&copy; <?= date('Y') ?> <?= e(get_setting('site_name', 'ApexSMM')) ?>. All rights reserved.</p>
                <div class="flex items-center gap-6">
                    <span>MySQL + Plain PHP Powered</span>
                    <span>No Mock Data Policy</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
