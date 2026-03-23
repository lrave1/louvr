<?php
/**
 * Dashboard template - pipeline overview, recent leads, rep performance.
 * All variables set by DashboardController::index()
 */
$pageTitle = 'Dashboard';
$statusColors = [
    'new' => '#3b82f6', 'New' => '#3b82f6',
    'assigned' => '#8b5cf6', 'Assigned' => '#8b5cf6',
    'booked' => '#f97316', 'Booked' => '#f97316',
    'quoted' => '#eab308', 'Quoted' => '#eab308',
    'won' => '#22c55e', 'Won' => '#22c55e',
    'lost' => '#ef4444', 'Lost' => '#ef4444',
];
$statusBg = [
    'new' => 'bg-blue-500/10 border-blue-500/30 text-blue-400', 'New' => 'bg-blue-500/10 border-blue-500/30 text-blue-400',
    'assigned' => 'bg-purple-500/10 border-purple-500/30 text-purple-400', 'Assigned' => 'bg-purple-500/10 border-purple-500/30 text-purple-400',
    'booked' => 'bg-orange-500/10 border-orange-500/30 text-orange-400', 'Booked' => 'bg-orange-500/10 border-orange-500/30 text-orange-400',
    'quoted' => 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400', 'Quoted' => 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400',
    'won' => 'bg-green-500/10 border-green-500/30 text-green-400', 'Won' => 'bg-green-500/10 border-green-500/30 text-green-400',
    'lost' => 'bg-red-500/10 border-red-500/30 text-red-400', 'Lost' => 'bg-red-500/10 border-red-500/30 text-red-400',
];
ob_start();
?>

<!-- Header -->
<div class="mb-8">
    <h2 class="text-2xl font-bold text-white">Dashboard</h2>
    <p class="text-gray-500 text-sm mt-1">Pipeline overview and performance metrics</p>
</div>

<!-- Pipeline Cards -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
    <?php foreach ($pipeline as $status => $count): ?>
    <a href="/leads?status=<?= $status ?>" class="block border rounded-xl p-4 transition-all hover:scale-105 <?= $statusBg[$status] ?? $statusBg[strtolower($status)] ?? 'bg-gray-500/10 border-gray-500/30 text-gray-400' ?>">
        <p class="text-xs font-medium uppercase tracking-wider opacity-80"><?= ucfirst($status) ?></p>
        <p class="text-3xl font-bold mt-1"><?= $count ?></p>
    </a>
    <?php endforeach; ?>
</div>

<!-- Stats Row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Total Leads</p>
        <p class="text-2xl font-bold text-white mt-1"><?= $totalLeads ?></p>
    </div>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Conversion Rate</p>
        <p class="text-2xl font-bold text-white mt-1"><?= $conversionRate ?>%</p>
    </div>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Lead Sources</p>
        <div class="flex flex-wrap gap-2 mt-2">
            <?php foreach ($sources as $s): ?>
            <span class="text-xs bg-gray-800 text-gray-300 px-2 py-1 rounded">
                <?= htmlspecialchars(str_replace('_', ' ', $s['source'])) ?>: <?= $s['count'] ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Leads -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl">
        <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between">
            <h3 class="font-semibold text-white">Recent Leads</h3>
            <a href="/leads" class="text-xs text-blue-400 hover:text-blue-300">View All</a>
        </div>
        <div class="divide-y divide-gray-800">
            <?php if (empty($recentLeads)): ?>
            <p class="p-5 text-gray-500 text-sm">No leads yet.</p>
            <?php endif; ?>
            <?php foreach ($recentLeads as $lead): ?>
            <a href="/leads/<?= $lead['id'] ?>" class="flex items-center justify-between px-5 py-3 hover:bg-gray-800/50 transition-colors">
                <div>
                    <p class="text-sm font-medium text-white"><?= htmlspecialchars($lead['customer_name']) ?></p>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars($lead['suburb'] ?: $lead['address']) ?> &middot; <?= htmlspecialchars(str_replace('_', ' ', $lead['source'])) ?></p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full border <?= $statusBg[$lead['status']] ?>"><?= ucfirst($lead['status']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Rep Performance -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl">
        <div class="px-5 py-4 border-b border-gray-800">
            <h3 class="font-semibold text-white">Rep Performance</h3>
        </div>
        <div class="divide-y divide-gray-800">
            <?php if (empty($repPerformance)): ?>
            <p class="p-5 text-gray-500 text-sm">No reps yet.</p>
            <?php endif; ?>
            <?php foreach ($repPerformance as $rep): ?>
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="text-sm font-medium text-white"><?= htmlspecialchars($rep['name']) ?></p>
                    <p class="text-xs text-gray-500"><?= $rep['total_leads'] ?> leads &middot; <?= $rep['won'] ?> won</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold <?= $rep['conversion_rate'] >= 50 ? 'text-green-400' : ($rep['conversion_rate'] >= 25 ? 'text-yellow-400' : 'text-gray-400') ?>">
                        <?= $rep['conversion_rate'] ?>%
                    </p>
                    <?php if ($rep['total_revenue'] > 0): ?>
                    <p class="text-xs text-gray-500">$<?= number_format($rep['total_revenue'], 0) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/app.php';
