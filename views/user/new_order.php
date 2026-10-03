<?php
$pageTitle = 'New Order';
$currentRoute = 'new_order';
require_once __DIR__ . '/../layouts/header.php';

// Fetch active categories
$categories = Database::fetchAll('SELECT * FROM categories WHERE status = "active" ORDER BY sort_order ASC, name ASC');

// Fetch all active services
$services = Database::fetchAll('
    SELECT s.*, c.name as category_name 
    FROM services s 
    JOIN categories c ON s.category_id = c.id 
    WHERE s.status = "active" 
    ORDER BY c.sort_order ASC, s.id ASC
');

$preselectedServiceId = isset($_GET['service_id']) ? (int) $_GET['service_id'] : 0;
$preselectedCatId = 0;
if ($preselectedServiceId > 0) {
    foreach ($services as $srv) {
        if ((int)$srv['id'] === $preselectedServiceId) {
            $preselectedCatId = (int)$srv['category_id'];
            break;
        }
    }
}
if ($preselectedCatId === 0 && !empty($categories)) {
    $preselectedCatId = (int) $categories[0]['id'];
}
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{
    categories: <?= htmlspecialchars(json_encode($categories), ENT_QUOTES, 'UTF-8') ?>,
    allServices: <?= htmlspecialchars(json_encode($services), ENT_QUOTES, 'UTF-8') ?>,
    selectedCat: <?= $preselectedCatId ?>,
    selectedServiceId: <?= $preselectedServiceId ?>,
    selectedService: null,
    quantity: 100,
    link: '',
    userBalance: <?= (float) $user['balance'] ?>,

    init() {
        this.updateSelectedService();
    },

    get filteredServices() {
        return this.allServices.filter(s => parseInt(s.category_id) === parseInt(this.selectedCat));
    },

    updateSelectedService() {
        const found = this.allServices.find(s => parseInt(s.id) === parseInt(this.selectedServiceId));
        if (found) {
            this.selectedService = found;
            if (this.quantity < parseInt(found.min_quantity)) {
                this.quantity = parseInt(found.min_quantity);
            }
        } else {
            const first = this.filteredServices[0];
            if (first) {
                this.selectedServiceId = first.id;
                this.selectedService = first;
                this.quantity = parseInt(first.min_quantity);
            } else {
                this.selectedService = null;
            }
        }
    },

    onCategoryChange() {
        const firstInCat = this.filteredServices[0];
        if (firstInCat) {
            this.selectedServiceId = firstInCat.id;
            this.selectedService = firstInCat;
            this.quantity = parseInt(firstInCat.min_quantity);
        } else {
            this.selectedServiceId = 0;
            this.selectedService = null;
        }
    },

    get calculatedPrice() {
        if (!this.selectedService || !this.quantity || this.quantity <= 0) return 0.0000;
        const ratePerThousand = parseFloat(this.selectedService.rate);
        return (parseFloat(this.quantity) / 1000) * ratePerThousand;
    },

    get hasEnoughBalance() {
        return this.userBalance >= this.calculatedPrice;
    }
}">

    <!-- Page Title -->
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-white">Place New Order</h1>
        <p class="text-slate-400 text-sm mt-1">Select your service, specify link and quantity, and execute instantly.</p>
    </div>

    <?php if (empty($services)): ?>
        <div class="p-16 rounded-2xl bg-[#0e1326] border border-slate-800/80 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-500">
                <?= heroicon('shopping-cart', 'w-7 h-7') ?>
            </div>
            <h3 class="text-lg font-bold text-white mb-1">No services available</h3>
            <p class="text-sm text-slate-400 max-w-md mx-auto">
                There are currently no active services configured in the database. Please check back shortly or contact support.
            </p>
        </div>
    <?php else: ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Order Form (2 Cols) -->
            <div class="lg:col-span-2 p-8 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-2xl">
                <form method="POST" action="/new-order" class="space-y-6">
                    <?= csrf_field() ?>

                    <!-- Category -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Platform / Category</label>
                        <select x-model="selectedCat" @change="onCategoryChange()" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 transition">
                            <template x-for="cat in categories" :key="cat.id">
                                <option :value="cat.id" x-text="cat.name" :selected="cat.id == selectedCat"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Service -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Service</label>
                        <select name="service_id" x-model="selectedServiceId" @change="updateSelectedService()" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 transition">
                            <template x-for="s in filteredServices" :key="s.id">
                                <option :value="s.id" x-text="'#' + s.id + ' - ' + s.name + ' ($' + parseFloat(s.rate).toFixed(4) + ' / 1K)'"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Target Link -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Target Link / URL</label>
                        <input type="url" name="link" x-model="link" required placeholder="https://instagram.com/p/... or channel link" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition">
                    </div>

                    <!-- Quantity -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Quantity</label>
                            <span class="text-xs text-slate-400 font-mono" x-show="selectedService">
                                Min: <strong class="text-white" x-text="selectedService ? selectedService.min_quantity : 10"></strong> / 
                                Max: <strong class="text-white" x-text="selectedService ? selectedService.max_quantity : 10000"></strong>
                            </span>
                        </div>
                        <input type="number" name="quantity" x-model="quantity" :min="selectedService ? selectedService.min_quantity : 1" :max="selectedService ? selectedService.max_quantity : 100000" required class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-blue-500 transition">
                    </div>

                    <!-- Calculated Total Price Box -->
                    <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-slate-400">Total Order Charge:</span>
                            <div class="text-2xl font-black text-emerald-400 font-mono" x-text="'$' + calculatedPrice.toFixed(4)"></div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-slate-400">Current Balance:</span>
                            <div class="text-sm font-semibold text-slate-200 font-mono"><?= format_currency($user['balance']) ?></div>
                        </div>
                    </div>

                    <!-- Insufficient Balance Warning -->
                    <div x-show="!hasEnoughBalance" x-cloak class="p-4 rounded-xl bg-rose-950/50 border border-rose-500/40 text-rose-200 text-xs flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <?= heroicon('exclamation-triangle', 'w-4 h-4 text-rose-400 shrink-0') ?>
                            <span>Insufficient wallet balance to place this order.</span>
                        </div>
                        <a href="/add-funds" class="font-bold underline hover:text-white">Add Funds</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" :disabled="!hasEnoughBalance || !link || quantity <= 0" class="w-full py-4 rounded-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white shadow-xl shadow-blue-600/30 transition text-base">
                        Submit Order
                    </button>
                </form>
            </div>

            <!-- Service Details Sidebar (1 Col) -->
            <div class="space-y-6">
                <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl space-y-4" x-show="selectedService">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <?= heroicon('information-circle', 'w-5 h-5 text-blue-400') ?> Service Specifications
                    </h3>

                    <div class="space-y-3 text-xs">
                        <div class="flex items-center justify-between py-2 border-b border-slate-800">
                            <span class="text-slate-400">Service ID:</span>
                            <span class="font-mono text-slate-200 font-bold" x-text="'#' + selectedService.id"></span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-slate-800">
                            <span class="text-slate-400">Rate per 1,000:</span>
                            <span class="font-mono text-emerald-400 font-bold" x-text="'$' + parseFloat(selectedService.rate).toFixed(4)"></span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-slate-800">
                            <span class="text-slate-400">Refill Guarantee:</span>
                            <span class="font-semibold" :class="selectedService.refill_supported == 1 ? 'text-emerald-400' : 'text-slate-500'" x-text="selectedService.refill_supported == 1 ? 'Supported' : 'No Refill'"></span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-slate-800">
                            <span class="text-slate-400">Cancel Feature:</span>
                            <span class="font-semibold" :class="selectedService.cancel_supported == 1 ? 'text-purple-400' : 'text-slate-500'" x-text="selectedService.cancel_supported == 1 ? 'Supported' : 'No Cancel'"></span>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Description & Instructions</h4>
                        <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800 text-xs text-slate-300 leading-relaxed whitespace-pre-line" x-text="selectedService.description ? selectedService.description : 'No special instructions provided.'"></div>
                    </div>
                </div>

                <div class="p-6 rounded-3xl bg-[#0e1326] border border-slate-800/80 shadow-xl text-xs text-slate-400 space-y-2">
                    <h4 class="font-bold text-slate-200">Before Placing Order:</h4>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Make sure your account or channel is public.</li>
                        <li>Do not submit multiple orders to the same link simultaneously.</li>
                        <li>Double-check link format before submission.</li>
                    </ul>
                </div>
            </div>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
