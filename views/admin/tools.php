<?php
$pageTitle = 'System Tools & Diagnostics';
$currentAdminRoute = 'tools';
require_once __DIR__ . '/../layouts/admin_header.php';

// Fetch audit logs
$auditLogs = Database::fetchAll('
    SELECT a.*, u.username 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.id DESC 
    LIMIT 30
');

// Existing database backups
$backupFiles = [];
if (is_dir(STORAGE_PATH . '/backups')) {
    $files = scandir(STORAGE_PATH . '/backups');
    foreach ($files as $f) {
        if (str_ends_with($f, '.sql')) {
            $backupFiles[] = [
                'name' => $f,
                'size' => round(filesize(STORAGE_PATH . '/backups/' . $f) / 1024, 2) . ' KB',
                'time' => date('M d, Y H:i:s', filemtime(STORAGE_PATH . '/backups/' . $f)),
            ];
        }
    }
}

// Error log contents
$errorLogContent = '';
if (file_exists(STORAGE_PATH . '/logs/error.log')) {
    $errorLogContent = file_get_contents(STORAGE_PATH . '/logs/error.log');
}
?>

<div class="space-y-8">

    <div>
        <h2 class="text-2xl font-bold text-white">System Tools & Diagnostics</h2>
        <p class="text-xs text-slate-400 mt-0.5">Automated cron workers, database backups, and runtime diagnostic audit trails.</p>
    </div>

    <!-- Quick Tools Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Automated Cron Worker -->
        <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <?= heroicon('bolt', 'w-5 h-5 text-amber-400') ?> Automated Order Synchronizer (Cron)
                </h3>
                <span class="text-xs font-mono text-slate-400">Last run: <?= e(get_setting('cron_last_run', 'Never')) ?></span>
            </div>
            
            <p class="text-xs text-slate-400 leading-relaxed">
                The cron engine polls active upstream providers, synchronizes order start counts, remaining amounts, and completion statuses.
            </p>

            <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-mono text-slate-300">
                <span class="text-slate-500 block mb-1">Recommended Server Crontab:</span>
                * * * * * php <?= ROOT_PATH ?>/cron.php >/dev/null 2>&1
            </div>

            <form method="POST" action="/admin/tools/run-cron">
                <?= csrf_field() ?>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 transition flex items-center gap-2">
                    <?= heroicon('arrow-path', 'w-4 h-4') ?> Execute Cron Engine Manually
                </button>
            </form>
        </div>

        <!-- Database Backup Engine -->
        <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <?= heroicon('server', 'w-5 h-5 text-purple-400') ?> Database Snapshot & Backup
            </h3>
            
            <p class="text-xs text-slate-400 leading-relaxed">
                Generate an instant complete SQL dump of all tables, services, user accounts, and audit ledgers.
            </p>

            <form method="POST" action="/admin/tools/backup-db">
                <?= csrf_field() ?>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-semibold bg-purple-600 hover:bg-purple-500 text-white shadow-lg shadow-purple-600/30 transition flex items-center gap-2">
                    <?= heroicon('document-arrow-down', 'w-4 h-4') ?> Create New SQL Backup
                </button>
            </form>

            <?php if (!empty($backupFiles)): ?>
                <div class="space-y-2 pt-2">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase">Existing Backups:</span>
                    <?php foreach ($backupFiles as $bf): ?>
                        <div class="p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between text-xs font-mono">
                            <span class="text-slate-300 truncate max-w-xs"><?= e($bf['name']) ?> (<?= $bf['size'] ?>)</span>
                            <span class="text-slate-500"><?= $bf['time'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- System Diagnostics Card -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
        <h3 class="text-base font-bold text-white flex items-center gap-2">
            <?= heroicon('wrench-screwdriver', 'w-5 h-5 text-emerald-400') ?> Runtime Environment Diagnostics
        </h3>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs font-mono">
            <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
                <span class="text-slate-500 block font-sans">PHP Version:</span>
                <span class="text-white font-bold"><?= PHP_VERSION ?></span>
            </div>
            <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
                <span class="text-slate-500 block font-sans">Database Engine:</span>
                <span class="text-emerald-400 font-bold">MariaDB / MySQL</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
                <span class="text-slate-500 block font-sans">Memory Usage:</span>
                <span class="text-white font-bold"><?= round(memory_get_usage() / 1024 / 1024, 2) ?> MB</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
                <span class="text-slate-500 block font-sans">PDO Extensions:</span>
                <span class="text-blue-400 font-bold">pdo_mysql, curl</span>
            </div>
        </div>
    </div>

    <!-- Audit Logs Ledger -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
        <h3 class="text-base font-bold text-white flex items-center gap-2">
            <?= heroicon('shield-check', 'w-5 h-5 text-blue-400') ?> System Audit Log Trail
        </h3>

        <?php if (empty($auditLogs)): ?>
            <p class="text-xs text-slate-500 py-6 text-center">No audit entries logged yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-mono">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px]">
                            <th class="pb-3 px-2">ID</th>
                            <th class="pb-3 px-2 font-sans">Action</th>
                            <th class="pb-3 px-2 font-sans">Operator / User</th>
                            <th class="pb-3 px-2 font-sans">Details</th>
                            <th class="pb-3 px-2">IP Address</th>
                            <th class="pb-3 px-2 text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php foreach ($auditLogs as $al): ?>
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-2.5 px-2 text-slate-500">#<?= $al['id'] ?></td>
                                <td class="py-2.5 px-2 font-sans font-bold text-blue-400"><?= e($al['action']) ?></td>
                                <td class="py-2.5 px-2 font-sans text-slate-300"><?= e($al['username'] ?: 'System') ?></td>
                                <td class="py-2.5 px-2 font-sans text-slate-400 max-w-sm truncate"><?= e($al['details']) ?></td>
                                <td class="py-2.5 px-2 text-slate-500"><?= e($al['ip_address']) ?></td>
                                <td class="py-2.5 px-2 text-right text-slate-500 whitespace-nowrap"><?= format_date($al['created_at'], 'M d, H:i:s') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Error Log Viewer -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <?= heroicon('exclamation-triangle', 'w-5 h-5 text-rose-400') ?> Error Log Output
            </h3>
            <span class="text-xs text-slate-500 font-mono">storage/logs/error.log</span>
        </div>

        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 text-xs font-mono text-slate-400 max-h-48 overflow-y-auto whitespace-pre-wrap">
            <?= !empty($errorLogContent) ? e($errorLogContent) : 'No error log entries. Clean system state.' ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
