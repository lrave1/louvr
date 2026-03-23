<?php
/**
 * New lead creation form.
 * Variables: $reps, $errors, $old
 */
use App\Models\Lead;
use App\Middleware\CsrfMiddleware;

$pageTitle = 'New Lead';
$e = fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
$oldData = $old ?? [];
$oldVal = fn($k, $d = '') => $e($oldData[$k] ?? $d);
ob_start();
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-white">New Lead</h2>
    <p class="text-gray-500 text-sm mt-1">Create a new lead in the pipeline</p>
</div>

<?php if (!empty($errors)): ?>
<div class="mb-6 px-4 py-3 bg-red-900/30 border border-red-800 rounded-lg">
    <?php foreach ($errors as $err): ?>
    <p class="text-red-400 text-sm"><?= htmlspecialchars($err) ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="/leads" class="bg-gray-900 border border-gray-800 rounded-xl p-6" data-loading>
    <?= CsrfMiddleware::field() ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- Customer Name -->
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1.5">Customer Name *</label>
            <input type="text" name="customer_name" value="<?= $oldVal('customer_name') ?>" required
                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Full name">
        </div>

        <!-- Phone -->
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1.5">Phone</label>
            <input type="tel" name="customer_phone" value="<?= $oldVal('customer_phone') ?>"
                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="0400 000 000">
        </div>

        <!-- Email -->
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1.5">Email</label>
            <input type="email" name="customer_email" value="<?= $oldVal('customer_email') ?>"
                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="customer@email.com">
        </div>

        <!-- Address -->
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1.5">Address</label>
            <input type="text" name="address" value="<?= $oldVal('address') ?>"
                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Street address">
        </div>

        <!-- Suburb -->
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1.5">Suburb</label>
            <input type="text" name="suburb" value="<?= $oldVal('suburb') ?>"
                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Suburb">
        </div>

        <!-- State + Postcode -->
        <div class="flex gap-3">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-400 mb-1.5">State</label>
                <select name="state" class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select</option>
                    <?php foreach (['NSW', 'VIC', 'QLD', 'WA', 'SA', 'TAS', 'ACT', 'NT'] as $st): ?>
                    <option value="<?= $st ?>" <?= ($oldData['state'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-28">
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Postcode</label>
                <input type="text" name="postcode" value="<?= $oldVal('postcode') ?>" maxlength="4"
                    class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="2000">
            </div>
        </div>

        <!-- Property Type -->
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1.5">Property Type</label>
            <select name="property_type" class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <?php foreach ($options['property_types'] as $pt): ?>
                <option value="<?= $e($pt) ?>" <?= ($old['property_type'] ?? '') === $pt ? 'selected' : '' ?>><?= $e($pt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Source -->
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1.5">Source</label>
            <select name="source" class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <?php foreach ($options['sources'] as $s): ?>
                <option value="<?= $s ?>" <?= ($oldData['source'] ?? 'phone') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Products Interested -->
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1.5">Products Interested</label>
            <input type="text" name="products_interested" value="<?= $oldVal('products_interested') ?>"
                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Blinds, shutters, curtains...">
        </div>

        <!-- Assign Rep -->
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1.5">Assign to Rep</label>
            <select name="assigned_to" class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Unassigned</option>
                <?php foreach ($reps as $rep): ?>
                <option value="<?= $rep['id'] ?>" <?= ($oldData['assigned_to'] ?? '') == $rep['id'] ? 'selected' : '' ?>><?= $e($rep['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Notes -->
    <div class="mt-5">
        <label class="block text-sm font-medium text-gray-400 mb-1.5">Notes</label>
        <textarea name="notes" rows="3"
            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
            placeholder="Any additional notes..."><?= $oldVal('notes') ?></textarea>
    </div>

    <div class="mt-6 flex justify-end gap-3">
        <a href="/leads" class="px-4 py-2.5 text-gray-400 hover:text-white text-sm transition-colors">Cancel</a>
        <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">Create Lead</button>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
