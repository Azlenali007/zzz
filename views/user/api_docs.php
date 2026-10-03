<?php
$pageTitle = 'Reseller API Documentation';
$currentRoute = 'api_docs';
require_once __DIR__ . '/../layouts/header.php';

$apiUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api/v2';
$userApiKey = $user ? $user['api_key'] : 'YOUR_API_KEY';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

    <div>
        <h1 class="text-3xl font-extrabold text-white">Reseller API v2 Specification</h1>
        <p class="text-slate-400 text-sm mt-1">Standardized REST API for programmatic service integration and order routing.</p>
    </div>

    <!-- API Overview Card -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
        <h3 class="text-base font-bold text-white">General Parameters</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-mono">
            <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-1">
                <span class="text-slate-400 block font-sans">HTTP Method:</span>
                <span class="text-emerald-400 font-bold">POST</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-1">
                <span class="text-slate-400 block font-sans">API Endpoint URL:</span>
                <span class="text-blue-400 font-bold select-all"><?= e($apiUrl) ?></span>
            </div>
        </div>
        <p class="text-xs text-slate-400">
            Every request must include your secret API key in the <code class="text-blue-400 font-mono">key</code> parameter and the desired operation in the <code class="text-blue-400 font-mono">action</code> parameter.
        </p>
    </div>

    <!-- 1. Service List -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-white">1. Service List</h3>
            <span class="px-2.5 py-1 rounded-md text-xs font-mono bg-blue-500/10 text-blue-400 border border-blue-500/20 font-bold">action=services</span>
        </div>
        <p class="text-xs text-slate-400">Returns a list of all active services and pricing.</p>

        <div class="space-y-2">
            <span class="text-xs font-semibold text-slate-300">cURL Example:</span>
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-slate-300 overflow-x-auto">
curl -X POST "<?= e($apiUrl) ?>" \
  -d "key=<?= e($userApiKey) ?>" \
  -d "action=services"
            </div>
        </div>

        <div class="space-y-2">
            <span class="text-xs font-semibold text-slate-300">Success Response (JSON Array):</span>
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-emerald-400 overflow-x-auto">
[
  {
    "service": "1",
    "name": "Instagram Real Followers",
    "type": "Default",
    "category": "Instagram",
    "rate": "0.8500",
    "min": "100",
    "max": "10000",
    "refill": true,
    "cancel": true,
    "desc": "High quality real profile followers"
  }
]
            </div>
        </div>
    </div>

    <!-- 2. Add Order -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-white">2. Place New Order</h3>
            <span class="px-2.5 py-1 rounded-md text-xs font-mono bg-blue-500/10 text-blue-400 border border-blue-500/20 font-bold">action=add</span>
        </div>
        <p class="text-xs text-slate-400">Executes a new order against your account balance.</p>

        <div class="space-y-2">
            <span class="text-xs font-semibold text-slate-300">cURL Example:</span>
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-slate-300 overflow-x-auto">
curl -X POST "<?= e($apiUrl) ?>" \
  -d "key=<?= e($userApiKey) ?>" \
  -d "action=add" \
  -d "service=1" \
  -d "link=https://instagram.com/username" \
  -d "quantity=500"
            </div>
        </div>

        <div class="space-y-2">
            <span class="text-xs font-semibold text-slate-300">Success Response:</span>
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-emerald-400 overflow-x-auto">
{
  "order": 1042
}
            </div>
        </div>
    </div>

    <!-- 3. Order Status -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-white">3. Check Order Status</h3>
            <span class="px-2.5 py-1 rounded-md text-xs font-mono bg-blue-500/10 text-blue-400 border border-blue-500/20 font-bold">action=status</span>
        </div>
        <p class="text-xs text-slate-400">Checks the status, remains, and start count of an existing order.</p>

        <div class="space-y-2">
            <span class="text-xs font-semibold text-slate-300">cURL Example:</span>
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-slate-300 overflow-x-auto">
curl -X POST "<?= e($apiUrl) ?>" \
  -d "key=<?= e($userApiKey) ?>" \
  -d "action=status" \
  -d "order=1042"
            </div>
        </div>

        <div class="space-y-2">
            <span class="text-xs font-semibold text-slate-300">Success Response:</span>
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-emerald-400 overflow-x-auto">
{
  "charge": "0.4250",
  "start_count": "1200",
  "status": "Completed",
  "remains": "0",
  "currency": "USD"
}
            </div>
        </div>
    </div>

    <!-- 4. User Balance -->
    <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-white">4. User Balance</h3>
            <span class="px-2.5 py-1 rounded-md text-xs font-mono bg-blue-500/10 text-blue-400 border border-blue-500/20 font-bold">action=balance</span>
        </div>

        <div class="space-y-2">
            <span class="text-xs font-semibold text-slate-300">cURL Example:</span>
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-slate-300 overflow-x-auto">
curl -X POST "<?= e($apiUrl) ?>" \
  -d "key=<?= e($userApiKey) ?>" \
  -d "action=balance"
            </div>
        </div>

        <div class="space-y-2">
            <span class="text-xs font-semibold text-slate-300">Success Response:</span>
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-emerald-400 overflow-x-auto">
{
  "balance": "145.8200",
  "currency": "USD"
}
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
