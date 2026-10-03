<?php
$isLocked = file_exists(CONFIG_PATH . '/installed.lock');

// Requirement Checks
$requirements = [
    'PHP >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'PDO Extension' => extension_loaded('pdo'),
    'PDO MySQL Driver' => extension_loaded('pdo_mysql'),
    'cURL Extension' => extension_loaded('curl'),
    'Mbstring Extension' => extension_loaded('mbstring'),
    'JSON Extension' => extension_loaded('json'),
    'Storage Writable' => is_writable(STORAGE_PATH),
];
$allRequirementsMet = !in_array(false, $requirements, true);

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApexSMM - Production Web Installer</title>
    
    <!-- Vite Production Bundle -->
    <link rel="stylesheet" href="/dist/assets/style.css">
    <script src="/dist/assets/app.js" defer></script>
</head>
<body class="bg-[#070913] text-slate-100 min-h-screen flex items-center justify-center p-4 selection:bg-blue-600 selection:text-white">

    <div class="max-w-xl w-full p-8 rounded-3xl bg-[#0e1326] border border-slate-800 shadow-2xl relative overflow-hidden" x-data="{
        step: 1,
        dbHost: '<?= e(DB_HOST) ?>',
        dbPort: '<?= e(DB_PORT) ?>',
        dbName: '<?= e(DB_NAME) ?>',
        dbUser: '<?= e(DB_USER) ?>',
        dbPass: '<?= e(DB_PASS) ?>',
        adminUser: 'admin',
        adminEmail: 'admin@apexsmm.com',
        adminPass: '',
        adminConfirm: '',
        testingDb: false,
        dbStatus: null,

        async testDatabase() {
            this.testingDb = true;
            this.dbStatus = null;
            try {
                const res = await fetch('/install/test-db', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        host: this.dbHost,
                        port: this.dbPort,
                        name: this.dbName,
                        user: this.dbUser,
                        pass: this.dbPass
                    })
                });
                const data = await res.json();
                this.dbStatus = data;
            } catch (e) {
                this.dbStatus = { success: false, message: 'Network or server error during test.' };
            } finally {
                this.testingDb = false;
            }
        }
    }">

        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center mx-auto mb-3 shadow-lg shadow-blue-500/25">
                <?= heroicon('bolt', 'w-7 h-7 text-white') ?>
            </div>
            <h1 class="text-2xl font-black text-white">ApexSMM Production Installer</h1>
            <p class="text-xs text-slate-400 mt-1">Plain PHP + MySQL Automated Deployment Wizard</p>
        </div>

        <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-xl text-xs font-semibold <?= $flash['type'] === 'success' ? 'bg-emerald-950/60 border border-emerald-500/40 text-emerald-200' : 'bg-rose-950/60 border border-rose-500/40 text-rose-200' ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($isLocked): ?>
            <div class="p-8 rounded-2xl bg-slate-900/80 border border-slate-800 text-center space-y-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mx-auto">
                    <?= heroicon('shield-check', 'w-6 h-6') ?>
                </div>
                <h3 class="text-lg font-bold text-white">Installation Locked</h3>
                <p class="text-xs text-slate-400 leading-relaxed max-w-sm mx-auto">
                    ApexSMM has already been configured and locked. To re-run the installer, delete <code class="text-blue-400 font-mono">config/installed.lock</code>.
                </p>
                <div class="pt-2">
                    <a href="/login" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl font-semibold bg-blue-600 hover:bg-blue-500 text-white text-xs shadow-lg shadow-blue-600/30 transition">
                        Proceed to Login &rarr;
                    </a>
                </div>
            </div>
        <?php else: ?>

            <!-- Progress Tracker -->
            <div class="flex items-center justify-between mb-8 px-4 text-xs font-semibold">
                <div class="flex items-center gap-2" :class="step >= 1 ? 'text-blue-400 font-bold' : 'text-slate-500'">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center" :class="step >= 1 ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-400'">1</span>
                    <span>Checks</span>
                </div>
                <div class="h-0.5 w-10 bg-slate-800"></div>
                <div class="flex items-center gap-2" :class="step >= 2 ? 'text-blue-400 font-bold' : 'text-slate-500'">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center" :class="step >= 2 ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-400'">2</span>
                    <span>Database</span>
                </div>
                <div class="h-0.5 w-10 bg-slate-800"></div>
                <div class="flex items-center gap-2" :class="step >= 3 ? 'text-blue-400 font-bold' : 'text-slate-500'">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center" :class="step >= 3 ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-400'">3</span>
                    <span>Admin</span>
                </div>
            </div>

            <!-- Step 1: Requirements Check -->
            <div x-show="step === 1" class="space-y-4">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Step 1: Environment Requirements</h3>
                <div class="divide-y divide-slate-800/80 border border-slate-800 rounded-2xl bg-slate-900/60 p-4 space-y-2 text-xs">
                    <?php foreach ($requirements as $reqName => $passed): ?>
                        <div class="flex items-center justify-between py-2">
                            <span class="text-slate-300"><?= $reqName ?></span>
                            <?php if ($passed): ?>
                                <span class="text-emerald-400 font-semibold flex items-center gap-1">
                                    <?= heroicon('check', 'w-4 h-4') ?> OK
                                </span>
                            <?php else: ?>
                                <span class="text-rose-400 font-semibold flex items-center gap-1">
                                    <?= heroicon('x-mark', 'w-4 h-4') ?> Missing
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="button" @click="step = 2" <?= !$allRequirementsMet ? 'disabled' : '' ?> class="px-6 py-2.5 rounded-xl font-semibold bg-blue-600 hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs shadow-lg shadow-blue-600/30 transition">
                        Next: Database Setup &rarr;
                    </button>
                </div>
            </div>

            <!-- Step 2: Database Configuration -->
            <div x-show="step === 2" x-cloak class="space-y-4">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Step 2: MySQL Connection</h3>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Host</label>
                        <input type="text" x-model="dbHost" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white font-mono focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Port</label>
                        <input type="text" x-model="dbPort" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white font-mono focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Database Name</label>
                    <input type="text" x-model="dbName" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white font-mono focus:border-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Username</label>
                        <input type="text" x-model="dbUser" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white font-mono focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Password</label>
                        <input type="password" x-model="dbPass" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white font-mono focus:border-blue-500">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="button" @click="testDatabase()" :disabled="testingDb" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition">
                        <span x-show="!testingDb">Test Connection</span>
                        <span x-show="testingDb">Testing...</span>
                    </button>
                </div>

                <div x-show="dbStatus !== null" class="p-3 rounded-xl text-xs font-medium" :class="dbStatus && dbStatus.success ? 'bg-emerald-950/60 border border-emerald-500/40 text-emerald-300' : 'bg-rose-950/60 border border-rose-500/40 text-rose-300'" x-text="dbStatus ? dbStatus.message : ''"></div>

                <div class="flex justify-between pt-4">
                    <button type="button" @click="step = 1" class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 text-slate-300">&larr; Back</button>
                    <button type="button" @click="step = 3" class="px-6 py-2.5 rounded-xl font-semibold bg-blue-600 hover:bg-blue-500 text-white text-xs shadow-lg shadow-blue-600/30">Next: Admin Account &rarr;</button>
                </div>
            </div>

            <!-- Step 3: Admin Account & Finalize -->
            <form method="POST" action="/install/execute" x-show="step === 3" x-cloak class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="db_host" :value="dbHost">
                <input type="hidden" name="db_port" :value="dbPort">
                <input type="hidden" name="db_name" :value="dbName">
                <input type="hidden" name="db_user" :value="dbUser">
                <input type="hidden" name="db_pass" :value="dbPass">

                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Step 3: Initial Administrator</h3>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Admin Username</label>
                    <input type="text" name="admin_user" x-model="adminUser" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Admin Email</label>
                    <input type="email" name="admin_email" x-model="adminEmail" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Admin Password</label>
                    <input type="password" name="admin_pass" x-model="adminPass" required minlength="6" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white focus:border-blue-500">
                </div>

                <div class="flex justify-between pt-4">
                    <button type="button" @click="step = 2" class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 text-slate-300">&larr; Back</button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs shadow-xl shadow-blue-600/30">
                        Complete Installation
                    </button>
                </div>
            </form>

        <?php endif; ?>

    </div>

</body>
</html>
