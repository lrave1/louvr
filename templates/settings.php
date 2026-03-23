<?php
/**
 * Settings page (admin only).
 * Variables: $settings
 */
use App\Middleware\CsrfMiddleware;

$pageTitle = 'Settings';
$e = fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
ob_start();
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-white">Settings</h2>
    <p class="text-gray-500 text-sm mt-1">Application configuration</p>
</div>

<form method="POST" action="/settings" class="bg-gray-900 border border-gray-800 rounded-xl p-6 max-w-2xl" data-loading>
    <?= CsrfMiddleware::field() ?>

    <div class="space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1.5">Company Name</label>
            <input type="text" name="company_name" value="<?= $e($settings['company_name'] ?? 'UBlinds') ?>"
                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1.5">Company Phone</label>
            <input type="text" name="company_phone" value="<?= $e($settings['company_phone'] ?? '') ?>"
                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1.5">Company Email</label>
            <input type="email" name="company_email" value="<?= $e($settings['company_email'] ?? '') ?>"
                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1.5">Default Appointment Duration (minutes)</label>
            <input type="number" name="default_appointment_duration" value="<?= $e($settings['default_appointment_duration'] ?? '60') ?>" min="15" step="15"
                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <div class="mt-6">
        <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">Save Settings</button>
    </div>
</form>

<h3 class="text-xl font-bold text-white mt-10 mb-4">Configurable Options</h3>
<p class="text-gray-500 text-sm mb-6">One item per line. These populate the dropdowns throughout the app.</p>

<form method="POST" action="/settings/options" class="space-y-6 max-w-2xl" data-loading>
    <?= CsrfMiddleware::field() ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <label class="block text-sm font-medium text-gray-400 mb-2">Lead Sources</label>
            <textarea name="lead_sources" rows="6"
                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
            ><?= $e(implode("\n", json_decode($settings['lead_sources'] ?? '[]', true) ?: [])) ?></textarea>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <label class="block text-sm font-medium text-gray-400 mb-2">Property Types</label>
            <textarea name="property_types" rows="6"
                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
            ><?= $e(implode("\n", json_decode($settings['property_types'] ?? '[]', true) ?: [])) ?></textarea>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <label class="block text-sm font-medium text-gray-400 mb-2">Products</label>
            <textarea name="products" rows="8"
                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
            ><?= $e(implode("\n", json_decode($settings['products'] ?? '[]', true) ?: [])) ?></textarea>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <label class="block text-sm font-medium text-gray-400 mb-2">Pipeline Statuses</label>
            <textarea name="pipeline_statuses" rows="8"
                class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
            ><?= $e(implode("\n", json_decode($settings['pipeline_statuses'] ?? '[]', true) ?: [])) ?></textarea>
            <p class="text-gray-600 text-xs mt-2">First status is the default for new leads. Last two are treated as closed (won/lost).</p>
        </div>
    </div>

    <div>
        <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">Save Options</button>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/app.php';
