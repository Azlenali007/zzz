<?php
$pageTitle = 'Website Settings';
$currentAdminRoute = 'settings';
require_once __DIR__ . '/../layouts/admin_header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div>
        <h2 class="text-2xl font-bold text-white">System Settings</h2>
        <p class="text-xs text-slate-400 mt-0.5">Configure platform branding, localization, and access controls.</p>
    </div>

    <form method="POST" action="/admin/settings/update" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Branding -->
        <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <?= heroicon('bolt', 'w-5 h-5 text-blue-400') ?> Branding & Information
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Site Name</label>
                    <input type="text" name="site_name" value="<?= e(get_setting('site_name', 'ApexSMM')) ?>" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Site Tagline</label>
                    <input type="text" name="site_tagline" value="<?= e(get_setting('site_tagline', 'Next-Gen High Performance SMM Panel')) ?>" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Site Meta Description</label>
                <textarea name="site_description" rows="2" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500"><?= e(get_setting('site_description', '')) ?></textarea>
            </div>
        </div>

        <!-- Localization & Currency -->
        <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <?= heroicon('wallet', 'w-5 h-5 text-emerald-400') ?> Currency & Localization
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Currency Code</label>
                    <input type="text" name="currency" value="<?= e(get_setting('currency', 'USD')) ?>" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono uppercase focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Currency Symbol</label>
                    <input type="text" name="currency_symbol" value="<?= e(get_setting('currency_symbol', '$')) ?>" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">System Timezone</label>
                    <select name="timezone" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                        <?php 
                        $currentTimezone = get_setting('timezone', 'UTC');
                        foreach (['UTC', 'America/New_York', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Dubai', 'Asia/Kolkata', 'Asia/Singapore', 'Asia/Tokyo'] as $tz): ?>
                            <option value="<?= $tz ?>" <?= $currentTimezone === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Contact Support Settings -->
        <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <?= heroicon('chat-bubble', 'w-5 h-5 text-purple-400') ?> Public Contact Details
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Official Support Email</label>
                    <input type="email" name="contact_email" value="<?= e(get_setting('contact_email', 'support@apexsmm.com')) ?>" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Official Telegram Handle</label>
                    <input type="text" name="contact_telegram" value="<?= e(get_setting('contact_telegram', '@ApexSMMSupport')) ?>" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- System Controls -->
        <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <?= heroicon('cog', 'w-5 h-5 text-slate-400') ?> Access Controls
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                    <div>
                        <span class="text-sm font-semibold text-white block">User Registration</span>
                        <span class="text-xs text-slate-400">Allow new clients to sign up</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="registration_enabled" value="1" <?= get_setting('registration_enabled', '1') === '1' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                    <div>
                        <span class="text-sm font-semibold text-white block">Maintenance Mode</span>
                        <span class="text-xs text-slate-400">Restrict access for non-admins</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="maintenance_mode" value="1" <?= get_setting('maintenance_mode', '0') === '1' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-8 py-3 rounded-2xl font-bold bg-blue-600 hover:bg-blue-500 text-white shadow-xl shadow-blue-600/30 transition text-sm">
                Save System Settings
            </button>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
