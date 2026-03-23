<?php
use App\Auth;
use App\Middleware\CsrfMiddleware;

$currentPath = strtok($_SERVER['REQUEST_URI'], '?');
$isActive = fn(string $path) => str_starts_with($currentPath, $path) ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Louvr') ?> - Louvr</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: { 50: '#1a1a2e', 100: '#16213e', 200: '#0f3460', 300: '#1a1a2e' }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .toast { animation: slideIn 0.3s ease, fadeOut 0.3s ease 3s forwards; }
        @keyframes slideIn { from { transform: translateY(-1rem); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes fadeOut { to { opacity: 0; visibility: hidden; } }
        .sidebar-link { transition: all 0.15s ease; }
    </style>
</head>
<body class="h-full bg-gray-950 text-gray-100">
<div class="flex h-full">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col flex-shrink-0">
        <div class="p-5 border-b border-gray-800">
            <h1 class="text-xl font-bold tracking-wide text-white">Louvr</h1>
            <p class="text-xs text-gray-500 mt-0.5">Lead Management</p>
        </div>
        <nav class="flex-1 p-3 space-y-1">
            <a href="/" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?= $isActive('/') && $currentPath === '/' ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            <a href="/leads" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?= $isActive('/leads') ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Leads
            </a>
            <a href="/leads/create" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?= $isActive('/leads/create') ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                New Lead
            </a>
            <?php if (Auth::isAdmin()): ?>
            <a href="/reps" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?= $isActive('/reps') ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                Manage Reps
            </a>
            <a href="/settings" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?= $isActive('/settings') ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>
            <?php endif; ?>
        </nav>
        <div class="p-3 border-t border-gray-800">
            <div class="flex items-center justify-between px-3 py-2">
                <div>
                    <p class="text-sm font-medium text-white"><?= htmlspecialchars(Auth::name()) ?></p>
                    <p class="text-xs text-gray-500 capitalize"><?= htmlspecialchars(Auth::role()) ?></p>
                </div>
                <a href="/logout" class="text-gray-500 hover:text-red-400 transition-colors" title="Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 overflow-y-auto">
        <?php
        // Toast notifications
        if (!empty($_SESSION['toast'])):
            $toast = $_SESSION['toast'];
            unset($_SESSION['toast']);
            $toastColors = [
                'success' => 'bg-green-600',
                'error'   => 'bg-red-600',
                'info'    => 'bg-blue-600',
            ];
            $bg = $toastColors[$toast['type']] ?? 'bg-gray-600';
        ?>
        <div class="toast fixed top-4 right-4 z-50 <?= $bg ?> text-white px-5 py-3 rounded-lg shadow-lg text-sm font-medium">
            <?= htmlspecialchars($toast['message']) ?>
        </div>
        <?php endif; ?>

        <div class="p-6 lg:p-8">
            <?= $content ?? '' ?>
        </div>
    </main>
</div>

<!-- Confirm modal -->
<div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60">
    <div class="bg-gray-900 rounded-xl border border-gray-700 p-6 max-w-sm w-full mx-4">
        <h3 class="text-lg font-semibold text-white mb-2" id="confirmTitle">Confirm</h3>
        <p class="text-gray-400 text-sm mb-5" id="confirmMessage">Are you sure?</p>
        <div class="flex justify-end gap-3">
            <button onclick="closeConfirm()" class="px-4 py-2 text-sm text-gray-400 hover:text-white transition-colors">Cancel</button>
            <button id="confirmBtn" class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">Confirm</button>
        </div>
    </div>
</div>

<script>
function showConfirm(title, message, onConfirm) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    const modal = document.getElementById('confirmModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('confirmBtn').onclick = () => { closeConfirm(); onConfirm(); };
}
function closeConfirm() {
    const modal = document.getElementById('confirmModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
// Loading state for forms
document.querySelectorAll('form[data-loading]').forEach(form => {
    form.addEventListener('submit', () => {
        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-4 w-4 inline mr-1" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Processing...';
        }
    });
});
</script>
</body>
</html>
