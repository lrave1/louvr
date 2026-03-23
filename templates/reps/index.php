<?php
/**
 * Rep management page (admin only).
 * Variables: $users, $errors
 */
use App\Middleware\CsrfMiddleware;

$pageTitle = 'Manage Reps';
$e = fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
ob_start();
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-white">Manage Users</h2>
        <p class="text-gray-500 text-sm mt-1"><?= count($users) ?> users</p>
    </div>
    <button onclick="document.getElementById('newRepForm').classList.toggle('hidden')"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">+ Add User</button>
</div>

<?php if (!empty($errors)): ?>
<div class="mb-6 px-4 py-3 bg-red-900/30 border border-red-800 rounded-lg">
    <?php foreach ($errors as $err): ?>
    <p class="text-red-400 text-sm"><?= htmlspecialchars($err) ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- New Rep Form (hidden) -->
<div id="newRepForm" class="hidden bg-gray-900 border border-gray-800 rounded-xl p-5 mb-6">
    <h3 class="font-semibold text-white mb-4">Create New User</h3>
    <form method="POST" action="/reps" data-loading>
        <?= CsrfMiddleware::field() ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="text-gray-500 text-xs">Name *</label>
                <input type="text" name="name" required class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-gray-500 text-xs">Email *</label>
                <input type="email" name="email" required class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-gray-500 text-xs">Phone</label>
                <input type="text" name="phone" class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-gray-500 text-xs">Role</label>
                <select name="role" class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="rep">Rep</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div>
                <label class="text-gray-500 text-xs">Password * (min 8 chars)</label>
                <input type="password" name="password" required minlength="8" class="w-full mt-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">Create User</button>
            </div>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-800/50">
            <tr class="text-left text-xs text-gray-400 uppercase tracking-wider">
                <th class="px-5 py-3">User</th>
                <th class="px-5 py-3">Role</th>
                <th class="px-5 py-3 hidden lg:table-cell">Coverage</th>
                <th class="px-5 py-3 hidden md:table-cell">Phone</th>
                <th class="px-5 py-3 hidden md:table-cell">Leads</th>
                <th class="px-5 py-3 hidden md:table-cell">Conv. Rate</th>
                <th class="px-5 py-3">Status</th>

                <th class="px-5 py-3">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-800">
            <?php foreach ($users as $user): ?>
            <tr class="<?= $user['is_active'] ? '' : 'opacity-50' ?>">
                <td class="px-5 py-3">
                    <p class="font-medium text-white"><?= $e($user['name']) ?></p>
                    <p class="text-xs text-gray-500"><?= $e($user['email']) ?></p>
                </td>
                <td class="px-5 py-3">
                    <span class="text-xs px-2 py-1 rounded-full <?= $user['role'] === 'admin' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/30' : 'bg-gray-700 text-gray-300' ?>"><?= ucfirst($user['role']) ?></span>
                </td>
                <td class="px-5 py-3 hidden lg:table-cell">
                    <?php if (!empty($user['states'])): ?>
                    <span class="text-xs text-gray-400"><?= $e($user['states']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($user['postcodes'])): ?>
                    <span class="text-xs text-gray-600 block"><?= $e($user['postcodes']) ?></span>
                    <?php endif; ?>
                    <?php if (empty($user['states']) && empty($user['postcodes'])): ?>
                    <span class="text-xs text-gray-600">-</span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3 text-gray-400 hidden md:table-cell"><?= $e($user['phone']) ?: '-' ?></td>
                <td class="px-5 py-3 text-gray-400 hidden md:table-cell"><?= $user['total_leads'] ?></td>
                <td class="px-5 py-3 hidden md:table-cell">
                    <span class="<?= $user['conversion_rate'] >= 50 ? 'text-green-400' : 'text-gray-400' ?>"><?= $user['conversion_rate'] ?>%</span>
                </td>
                <td class="px-5 py-3">
                    <span class="text-xs <?= $user['is_active'] ? 'text-green-400' : 'text-red-400' ?>"><?= $user['is_active'] ? 'Active' : 'Inactive' ?></span>
                </td>
                
                <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                        <!-- Toggle Active -->
                        <form method="POST" action="/reps/<?= $user['id'] ?>" class="inline">
                            <?= CsrfMiddleware::field() ?>
                            <input type="hidden" name="action" value="toggle_active">
                            <button type="submit" class="text-xs px-2 py-1 rounded <?= $user['is_active'] ? 'bg-red-900/30 text-red-400 hover:bg-red-900/50' : 'bg-green-900/30 text-green-400 hover:bg-green-900/50' ?> transition-colors"
                                onclick="event.preventDefault(); showConfirm('<?= $user['is_active'] ? 'Deactivate' : 'Activate' ?> User', 'Are you sure you want to <?= $user['is_active'] ? 'deactivate' : 'activate' ?> <?= $e($user['name']) ?>?', () => this.closest('form').submit())">
                                <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>

                        <!-- Edit (inline toggle) -->
                        <button onclick="document.getElementById('edit-<?= $user['id'] ?>').classList.toggle('hidden')"
                            class="text-xs px-2 py-1 rounded bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors">Edit</button>
                    </div>
                </td>
            </tr>
            <!-- Edit Row -->
            <tr id="edit-<?= $user['id'] ?>" class="hidden bg-gray-800/30">
                <td colspan="8" class="px-5 py-3">
                    <form method="POST" action="/reps/<?= $user['id'] ?>" class="flex flex-wrap gap-3 items-end" data-loading>
                        <?= CsrfMiddleware::field() ?>
                        <input type="hidden" name="action" value="update">
                        <div>
                            <label class="text-gray-500 text-xs">Name</label>
                            <input name="name" value="<?= $e($user['name']) ?>" class="mt-1 px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs">Email</label>
                            <input name="email" value="<?= $e($user['email']) ?>" class="mt-1 px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs">Phone</label>
                            <input name="phone" value="<?= $e($user['phone']) ?>" class="mt-1 px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs">Role</label>
                            <select name="role" class="mt-1 px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="rep" <?= $user['role'] === 'rep' ? 'selected' : '' ?>>Rep</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs">States</label>
                            <input name="states" value="<?= $e($user['states'] ?? '') ?>" placeholder="QLD, NSW, VIC" class="mt-1 px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs">Postcodes</label>
                            <input name="postcodes" value="<?= $e($user['postcodes'] ?? '') ?>" placeholder="4000-4179, 4500" class="mt-1 px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-48">
                        </div>
                        <div>
                            <label class="text-gray-500 text-xs">New Password (optional)</label>
                            <input type="password" name="password" minlength="8" placeholder="Leave blank to keep" class="mt-1 px-3 py-1.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">Save</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
