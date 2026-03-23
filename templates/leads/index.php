<?php
/**
 * Leads listing with filters, search, and pagination.
 * Variables: $result (paginated), $filters, $reps
 */
use App\Models\Lead;
use App\Middleware\CsrfMiddleware;

$pageTitle = 'Leads';
$statusBg = [
    'new'      => 'bg-blue-500/10 border-blue-500/30 text-blue-400',
    'assigned' => 'bg-purple-500/10 border-purple-500/30 text-purple-400',
    'booked'   => 'bg-orange-500/10 border-orange-500/30 text-orange-400',
    'quoted'   => 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400',
    'won'      => 'bg-green-500/10 border-green-500/30 text-green-400',
    'lost'     => 'bg-red-500/10 border-red-500/30 text-red-400',
];
$e = fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
ob_start();
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-white">Leads</h2>
        <p class="text-gray-500 text-sm mt-1"><?= $result['total'] ?> total leads</p>
    </div>
    <a href="/leads/create" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">+ New Lead</a>
</div>

<!-- Filters -->
<div class="bg-gray-900 border border-gray-800 rounded-xl p-4 mb-6">
    <form method="GET" action="/leads" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
        <input type="text" name="search" value="<?= $e($filters['search']) ?>" placeholder="Search name/email/phone..."
            class="col-span-2 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <select name="status" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Statuses</option>
            <?php foreach ($options['statuses'] as $s): ?>
            <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="assigned_to" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Reps</option>
            <?php foreach ($reps as $rep): ?>
            <option value="<?= $rep['id'] ?>" <?= $filters['assigned_to'] == $rep['id'] ? 'selected' : '' ?>><?= $e($rep['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="source" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Sources</option>
            <?php foreach ($options['sources'] as $s): ?>
            <option value="<?= $s ?>" <?= $filters['source'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white text-sm rounded-lg transition-colors">Filter</button>
            <a href="/leads" class="px-3 py-2 text-gray-400 hover:text-white text-sm transition-colors flex items-center">Clear</a>
        </div>
    </form>
</div>

<!-- Leads Table -->
<div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-800/50">
            <tr class="text-left text-xs text-gray-400 uppercase tracking-wider">
                <th class="px-5 py-3">Customer</th>
                <th class="px-5 py-3 hidden md:table-cell">Contact</th>
                <th class="px-5 py-3 hidden lg:table-cell">Location</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3 hidden md:table-cell">Rep</th>
                <th class="px-5 py-3 hidden lg:table-cell">Source</th>
                <th class="px-5 py-3 hidden lg:table-cell">Created</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-800">
            <?php if (empty($result['data'])): ?>
            <tr><td colspan="7" class="px-5 py-8 text-center text-gray-500">No leads found.</td></tr>
            <?php endif; ?>
            <?php foreach ($result['data'] as $lead): ?>
            <tr class="hover:bg-gray-800/50 cursor-pointer transition-colors" onclick="window.location='/leads/<?= $lead['id'] ?>'">
                <td class="px-5 py-3">
                    <p class="font-medium text-white"><?= $e($lead['customer_name']) ?></p>
                    <p class="text-xs text-gray-500 md:hidden"><?= $e($lead['customer_phone']) ?></p>
                </td>
                <td class="px-5 py-3 hidden md:table-cell">
                    <p class="text-gray-300"><?= $e($lead['customer_phone']) ?></p>
                    <p class="text-xs text-gray-500"><?= $e($lead['customer_email']) ?></p>
                </td>
                <td class="px-5 py-3 text-gray-400 hidden lg:table-cell"><?= $e($lead['suburb']) ?><?= $lead['state'] ? ', ' . $e($lead['state']) : '' ?></td>
                <td class="px-5 py-3">
                    <span class="text-xs px-2 py-1 rounded-full border <?= $statusBg[$lead['status']] ?? $statusBg[strtolower($lead['status'])] ?? 'bg-gray-500/10 border-gray-500/30 text-gray-400' ?>"><?= ucfirst($lead['status']) ?></span>
                </td>
                <td class="px-5 py-3 text-gray-400 hidden md:table-cell"><?= $e($lead['rep_name'] ?? 'Unassigned') ?></td>
                <td class="px-5 py-3 text-gray-400 hidden lg:table-cell"><?= ucfirst(str_replace('_', ' ', $lead['source'])) ?></td>
                <td class="px-5 py-3 text-gray-500 text-xs hidden lg:table-cell"><?= date('M j', strtotime($lead['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($result['total_pages'] > 1): ?>
<div class="flex items-center justify-between mt-4">
    <p class="text-sm text-gray-500">Page <?= $result['page'] ?> of <?= $result['total_pages'] ?></p>
    <div class="flex gap-2">
        <?php
        $queryBase = http_build_query(array_filter($filters));
        for ($p = 1; $p <= $result['total_pages']; $p++):
            $active = $p === $result['page'];
        ?>
        <a href="/leads?<?= $queryBase ?>&page=<?= $p ?>"
            class="px-3 py-1.5 text-sm rounded-lg <?= $active ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' ?>">
            <?= $p ?>
        </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
