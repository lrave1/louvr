<?php
use App\Middleware\CsrfMiddleware;
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Louvr</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full bg-gray-950 flex items-center justify-center">
<div class="w-full max-w-sm mx-4">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-white tracking-wide">Louvr</h1>
        <p class="text-gray-500 text-sm mt-1">Lead Management System</p>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <?php if (!empty($error)): ?>
        <div class="mb-4 px-4 py-3 bg-red-900/30 border border-red-800 rounded-lg text-red-400 text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="/login" data-loading>
            <?= CsrfMiddleware::field() ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Email</label>
                <input type="email" name="email" required autofocus
                    class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                    placeholder="you@company.com">
            </div>
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Password</label>
                <input type="password" name="password" required
                    class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                    placeholder="Enter password">
            </div>
            <button type="submit"
                class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors text-sm">
                Sign In
            </button>
        </form>
    </div>
    <p class="text-center text-gray-600 text-xs mt-4">UBlinds Lead Management</p>
</div>
</body>
</html>
