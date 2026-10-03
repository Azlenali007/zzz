<?php
$pageTitle = 'Category Management';
$currentAdminRoute = 'categories';
require_once __DIR__ . '/../layouts/admin_header.php';

$categories = Database::fetchAll('
    SELECT c.*, COUNT(s.id) as service_count 
    FROM categories c 
    LEFT JOIN services s ON c.id = s.category_id 
    GROUP BY c.id 
    ORDER BY c.sort_order ASC, c.id ASC
');
?>

<div class="space-y-6" x-data="{ createModal: false, editModal: false, activeCat: {} }">
    
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">Categories</h2>
            <p class="text-xs text-slate-400 mt-0.5">Organize services by platform (Instagram, Telegram, YouTube, etc.)</p>
        </div>
        <button @click="createModal = true" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 transition">
            <?= heroicon('plus', 'w-4 h-4') ?> New Category
        </button>
    </div>

    <!-- Category List -->
    <?php if (empty($categories)): ?>
        <div class="p-16 rounded-2xl bg-[#0e1326] border border-slate-800/80 text-center">
            <div class="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-500">
                <?= heroicon('funnel', 'w-6 h-6') ?>
            </div>
            <h3 class="text-base font-bold text-white mb-1">No categories found</h3>
            <p class="text-xs text-slate-400 max-w-sm mx-auto">Create a category to group your SMM services.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-slate-800/80 bg-[#0e1326] shadow-xl">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-900/60 font-semibold uppercase tracking-wider text-slate-400 text-[10px]">
                        <th class="py-4 px-4 sm:px-6">ID</th>
                        <th class="py-4 px-4">Category Name</th>
                        <th class="py-4 px-4 text-center">Sort Order</th>
                        <th class="py-4 px-4 text-center">Services</th>
                        <th class="py-4 px-4 text-center">Status</th>
                        <th class="py-4 px-4 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-mono">
                    <?php foreach ($categories as $c): ?>
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-4 px-4 sm:px-6 text-slate-500 font-bold">#<?= $c['id'] ?></td>
                            <td class="py-4 px-4 font-sans font-bold text-slate-200"><?= e($c['name']) ?></td>
                            <td class="py-4 px-4 text-center text-slate-400"><?= $c['sort_order'] ?></td>
                            <td class="py-4 px-4 text-center text-blue-400 font-bold"><?= $c['service_count'] ?></td>
                            <td class="py-4 px-4 text-center font-sans"><?= status_badge($c['status']) ?></td>
                            <td class="py-4 px-4 sm:px-6 text-right font-sans space-x-1 whitespace-nowrap">
                                <button type="button" @click="activeCat = <?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>; editModal = true" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                                    Edit
                                </button>
                                <form method="POST" action="/admin/categories/delete" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="category_id" value="<?= $c['id'] ?>">
                                    <button type="submit" onclick="return confirm('Delete this category and disassociate its services?')" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-rose-950/40 border border-rose-500/30 text-rose-300 hover:bg-rose-900/60 transition">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Create Category Modal -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="createModal = false">
        <div class="min-h-screen px-4 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="createModal = false"></div>
            <div class="relative bg-[#0e1326] border border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl z-10" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Create New Category</h3>
                    <button @click="createModal = false" class="text-slate-400 hover:text-white"><?= heroicon('x-mark', 'w-5 h-5') ?></button>
                </div>
                <form method="POST" action="/admin/categories/create" class="py-4 space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Category Name</label>
                        <input type="text" name="name" required placeholder="e.g. Instagram Followers" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Sort Order</label>
                        <input type="number" name="sort_order" value="0" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" @click="createModal = false" class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30">Save Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="editModal = false">
        <div class="min-h-screen px-4 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="editModal = false"></div>
            <div class="relative bg-[#0e1326] border border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl z-10" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Edit Category</h3>
                    <button @click="editModal = false" class="text-slate-400 hover:text-white"><?= heroicon('x-mark', 'w-5 h-5') ?></button>
                </div>
                <form method="POST" action="/admin/categories/update" class="py-4 space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="category_id" :value="activeCat.id">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Category Name</label>
                        <input type="text" name="name" x-model="activeCat.name" required class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Sort Order</label>
                        <input type="number" name="sort_order" x-model="activeCat.sort_order" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Status</label>
                        <select name="status" x-model="activeCat.status" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
