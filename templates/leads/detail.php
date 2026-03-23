<?php
/**
 * Lead detail view with inline actions and timeline.
 * Variables: $lead, $timeline, $reps
 */
use App\Auth;
use App\Middleware\CsrfMiddleware;

$pageTitle = htmlspecialchars($lead['customer_name']);
$e = fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
$statusColors = [
    'new' => 'bg-blue-500', 'assigned' => 'bg-purple-500', 'booked' => 'bg-orange-500',
    'quoted' => 'bg-yellow-500', 'won' => 'bg-green-500', 'lost' => 'bg-red-500',
];
$statusBg = [
    'new'      => 'bg-blue-500/10 border-blue-500/30 text-blue-400',
    'assigned' => 'bg-purple-500/10 border-purple-500/30 text-purple-400',
    'booked'   => 'bg-orange-500/10 border-orange-500/30 text-orange-400',
    'quoted'   => 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400',
    'won'      => 'bg-green-500/10 border-green-500/30 text-green-400',
    'lost'     => 'bg-red-500/10 border-red-500/30 text-red-400',
];
$actionLabels = [
    'created'             => 'Created',
    'assigned'            => 'Assigned',
    'status_changed'      => 'Status Changed',
    'note_added'          => 'Note',
    'appointment_booked'  => 'Appointment Booked',
];
ob_start();
?>

<!-- Header -->
<div class="mb-6 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <a href="/leads" class="text-gray-500 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-white"><?= $e($lead['customer_name']) ?></h2>
            <p class="text-gray-500 text-sm">#<?= $lead['id'] ?> &middot; Created <?= date('M j, Y g:ia', strtotime($lead['created_at'])) ?></p>
        </div>
    </div>
    <span class="text-sm px-3 py-1.5 rounded-full border font-medium <?= $statusBg[$lead['status']] ?>"><?= ucfirst($lead['status']) ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column: Details & Actions -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Customer Info -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-white">Customer Details</h3>
                <button onclick="document.getElementById('editDetails').classList.toggle('hidden')" class="text-xs text-blue-400 hover:text-blue-300">Edit</button>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Phone:</span> <span class="text-white ml-1"><?= $e($lead['customer_phone']) ?: '-' ?></span></div>
                <div><span class="text-gray-500">Email:</span> <span class="text-white ml-1"><?= $e($lead['customer_email']) ?: '-' ?></span></div>
                <div><span class="text-gray-500">Address:</span> <span class="text-white ml-1"><?= $e($lead['address']) ?: '-' ?></span></div>
                <div><span class="text-gray-500">Suburb:</span> <span class="text-white ml-1"><?= $e($lead['suburb']) ?><?= $lead['state'] ? ', ' . $e($lead['state']) . ' ' . $e($lead['postcode']) : '' ?></span></div>
                <div><span class="text-gray-500">Property:</span> <span class="text-white ml-1 capitalize"><?= $e($lead['property_type']) ?></span></div>
                <div><span class="text-gray-500">Source:</span> <span class="text-white ml-1"><?= ucfirst(str_replace('_', ' ', $lead['source'])) ?></span></div>
                <div class="col-span-2"><span class="text-gray-500">Products:</span> <span class="text-white ml-1"><?= $e($lead['products_interested']) ?: '-' ?></span></div>
            </div>

            <!-- Edit form (hidden by default) -->
            <div id="editDetails" class="hidden mt-4 pt-4 border-t border-gray-800">
                <form method="POST" action="/leads/<?= $lead['id'] ?>" data-loading>
                    <?= CsrfMiddleware::field() ?>
                    <input type="hidden" name="action" value="update_details">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <label class="text-gray-500 text-xs">Name</label>
                            <input name="customer_name" value="<?= $e($lead['customer_name']) ?>" class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs">Phone</label>
                            <input name="customer_phone" value="<?= $e($lead['customer_phone']) ?>" class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs">Email</label>
                            <input name="customer_email" value="<?= $e($lead['customer_email']) ?>" class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs">Address</label>
                            <input name="address" value="<?= $e($lead['address']) ?>" class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs">Suburb</label>
                            <input name="suburb" value="<?= $e($lead['suburb']) ?>" class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="text-gray-500 text-xs">State</label>
                                <input name="state" value="<?= $e($lead['state']) ?>" class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="w-24">
                                <label class="text-gray-500 text-xs">Postcode</label>
                                <input name="postcode" value="<?= $e($lead['postcode']) ?>" class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs">Products Interested</label>
                            <input name="products_interested" value="<?= $e($lead['products_interested']) ?>" class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs">Property Type</label>
                            <select name="property_type" class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="residential" <?= $lead['property_type'] === 'residential' ? 'selected' : '' ?>>Residential</option>
                                <option value="commercial" <?= $lead['property_type'] === 'commercial' ? 'selected' : '' ?>>Commercial</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Appointment -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h3 class="font-semibold text-white mb-4">Appointment</h3>
            <?php if ($lead['appointment_date']): ?>
            <div class="flex items-center gap-4 text-sm mb-4">
                <div class="flex items-center gap-2 text-white">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <?= date('l, M j Y', strtotime($lead['appointment_date'])) ?>
                </div>
                <div class="flex items-center gap-2 text-white">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?= date('g:ia', strtotime($lead['appointment_time'])) ?> (<?= $lead['appointment_duration'] ?>min)
                </div>
            </div>
            <?php endif; ?>
            <form method="POST" action="/leads/<?= $lead['id'] ?>" class="grid grid-cols-3 gap-3" data-loading>
                <?= CsrfMiddleware::field() ?>
                <input type="hidden" name="action" value="book_appointment">
                <div>
                    <label class="text-gray-500 text-xs">Date</label>
                    <input type="date" name="appointment_date" value="<?= $e($lead['appointment_date']) ?>" class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-gray-500 text-xs">Time</label>
                    <input type="time" name="appointment_time" value="<?= $e($lead['appointment_time']) ?>" class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-gray-500 text-xs">Duration (min)</label>
                    <div class="flex gap-2 mt-1">
                        <input type="number" name="appointment_duration" value="<?= $lead['appointment_duration'] ?? 60 ?>" min="15" step="15" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm rounded-lg transition-colors whitespace-nowrap">Book</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Quote -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h3 class="font-semibold text-white mb-4">Quote</h3>
            <?php if ($lead['quoted_amount']): ?>
            <p class="text-2xl font-bold text-white mb-3">$<?= number_format($lead['quoted_amount'], 2) ?></p>
            <?php endif; ?>
            <form method="POST" action="/leads/<?= $lead['id'] ?>" class="flex gap-3" data-loading>
                <?= CsrfMiddleware::field() ?>
                <input type="hidden" name="action" value="update_quote">
                <input type="number" name="quoted_amount" value="<?= $e($lead['quoted_amount']) ?>" step="0.01" min="0" placeholder="Enter amount"
                    class="flex-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm rounded-lg transition-colors">Update Quote</button>
            </form>
        </div>

        <!-- Add Note -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h3 class="font-semibold text-white mb-4">Add Note</h3>
            <form method="POST" action="/leads/<?= $lead['id'] ?>" data-loading>
                <?= CsrfMiddleware::field() ?>
                <input type="hidden" name="action" value="add_note">
                <textarea name="note" rows="3" required placeholder="Add a note..."
                    class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                <div class="mt-2 flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white text-sm rounded-lg transition-colors">Add Note</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: Status & Timeline -->
    <div class="space-y-6">

        <!-- Status Change -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h3 class="font-semibold text-white mb-4">Update Status</h3>
            <form id="statusForm" method="POST" action="/leads/<?= $lead['id'] ?>" data-loading>
                <?= CsrfMiddleware::field() ?>
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="status" id="statusInput" value="">
                <div class="grid grid-cols-2 gap-2">
                    <?php foreach ($options['statuses'] as $s): ?>
                    <?php $sLower = strtolower($s); $currentLower = strtolower($lead['status']); ?>
                    <button type="button"
                        class="px-3 py-2 text-xs font-medium rounded-lg border transition-all <?= $currentLower === $sLower ? ($statusBg[$s] ?? $statusBg[$sLower] ?? 'bg-gray-500/10 border-gray-500/30 text-gray-400') . ' ring-2 ring-offset-1 ring-offset-gray-900' : 'border-gray-700 text-gray-400 hover:text-white hover:border-gray-500' ?>"
                        onclick="<?php $closedStatuses = array_slice($options['statuses'], -2); if (in_array($s, $closedStatuses) && $currentLower !== $sLower): ?>showConfirm('Mark as <?= $e($s) ?>', 'Are you sure you want to mark this lead as <?= $e($s) ?>?', () => { document.getElementById('statusInput').value='<?= $e($s) ?>'; document.getElementById('statusForm').submit(); })<?php else: ?>document.getElementById('statusInput').value='<?= $e($s) ?>'; document.getElementById('statusForm').submit()<?php endif; ?>">
                        <?= $e($s) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>

        <!-- Assign Rep -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h3 class="font-semibold text-white mb-4">Assigned Rep</h3>
            <form method="POST" action="/leads/<?= $lead['id'] ?>" data-loading>
                <?= CsrfMiddleware::field() ?>
                <input type="hidden" name="action" value="assign_rep">
                <select name="assigned_to" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2">
                    <option value="">Unassigned</option>
                    <?php foreach ($reps as $rep): ?>
                    <option value="<?= $rep['id'] ?>" <?= $lead['assigned_to'] == $rep['id'] ? 'selected' : '' ?>><?= $e($rep['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition-colors">Assign</button>
            </form>
        </div>

        <!-- Timeline -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h3 class="font-semibold text-white mb-4">Activity Timeline</h3>
            <div class="space-y-4">
                <?php if (empty($timeline)): ?>
                <p class="text-gray-500 text-sm">No activity yet.</p>
                <?php endif; ?>
                <?php foreach ($timeline as $event): ?>
                <div class="flex gap-3">
                    <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full <?= $statusColors[$event['new_value'] ?? ''] ?? 'bg-gray-600' ?>"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-white"><?= $e($actionLabels[$event['action']] ?? $event['action']) ?></p>
                        <?php if ($event['old_value'] || $event['new_value']): ?>
                        <p class="text-xs text-gray-500">
                            <?php if ($event['old_value']): ?><span class="line-through"><?= $e($event['old_value']) ?></span> &rarr; <?php endif; ?>
                            <?= $e($event['new_value']) ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($event['note']): ?>
                        <p class="text-sm text-gray-300 mt-1"><?= nl2br($e($event['note'])) ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-600 mt-1"><?= $e($event['user_name'] ?? 'System') ?> &middot; <?= date('M j, g:ia', strtotime($event['created_at'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
