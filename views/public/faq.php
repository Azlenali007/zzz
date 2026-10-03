<?php
$pageTitle = 'Frequently Asked Questions';
$currentRoute = 'faq';
require_once __DIR__ . '/../layouts/header.php';

$faqs = [
    [
        'q' => 'What is an SMM Panel?',
        'a' => 'An SMM (Social Media Marketing) panel is an online store where customers and resellers can purchase social media marketing services such as followers, likes, views, comments, and member growth across platforms like Instagram, YouTube, TikTok, Telegram, and Facebook.'
    ],
    [
        'q' => 'How long does order delivery take?',
        'a' => 'Delivery speed depends on the specific service chosen. Many services begin within 1 to 15 minutes of placement, while drip-feed or high-quantity orders may deliver gradually according to platform safety guidelines. You can view the start counts and remaining amounts in your Orders dashboard.'
    ],
    [
        'q' => 'What payment methods do you support?',
        'a' => 'We support secure automated and manual payment methods including USDT (TRC20 / ERC20 crypto), manual bank wire transfers, and configured card processing. Check our Add Funds page for currently active methods.'
    ],
    [
        'q' => 'What is the Refill Guarantee?',
        'a' => 'Services marked with the Refill badge feature refill protection. If natural drops occur within the refill warranty period, you can click the Refill button in your orders table or submit a ticket, and our system will automatically replenish the lost count.'
    ],
    [
        'q' => 'Do you provide a Reseller API?',
        'a' => 'Yes! We provide a complete standard SMM API v2 that allows you to connect external websites, scripts, or bots directly to our panel for automated service sync, balance inquiries, and order routing. Visit the API page for full parameters and your API key.'
    ],
    [
        'q' => 'Can I cancel an order once placed?',
        'a' => 'Orders that have not yet begun processing or services marked with cancel support can be cancelled via the orders view or through our support team. Once an order is fully completed or in mid-delivery, cancellation is typically locked by the network.'
    ],
];
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16" x-data="{ activeAccordion: 0 }">
    <div class="text-center mb-16">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-900/30 border border-blue-500/30 text-blue-400 text-xs font-semibold uppercase tracking-wider mb-4">
            Help Center
        </div>
        <h1 class="text-4xl font-extrabold text-white">Frequently Asked Questions</h1>
        <p class="text-slate-400 text-sm mt-3">Find instant answers to common questions about services, payments, and delivery.</p>
    </div>

    <div class="space-y-4">
        <?php foreach ($faqs as $idx => $faq): ?>
            <div class="rounded-2xl bg-[#0e1326] border border-slate-800/80 overflow-hidden transition">
                <button @click="activeAccordion = activeAccordion === <?= $idx ?> ? null : <?= $idx ?>" class="w-full px-6 py-5 flex items-center justify-between text-left font-semibold text-slate-200 hover:text-white transition">
                    <span><?= e($faq['q']) ?></span>
                    <div class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-400 shrink-0 ml-4">
                        <span x-show="activeAccordion !== <?= $idx ?>"><?= heroicon('plus', 'w-4 h-4') ?></span>
                        <span x-show="activeAccordion === <?= $idx ?>" x-cloak><?= heroicon('x-mark', 'w-4 h-4') ?></span>
                    </div>
                </button>
                <div x-show="activeAccordion === <?= $idx ?>" x-collapse x-cloak class="px-6 pb-5 text-sm text-slate-400 leading-relaxed border-t border-slate-800/50 pt-4">
                    <?= e($faq['a']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-12 text-center text-sm text-slate-400">
        Still have questions? <a href="/contact" class="text-blue-400 hover:underline font-medium">Contact our 24/7 Support Team</a>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
