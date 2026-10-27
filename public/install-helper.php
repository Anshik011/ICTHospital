<?php
/**
 * ICTHospital - Shared Hosting Web Setup & Migration Helper
 *
 * This utility assists shared hosting users (e.g., Hostinger, cPanel) who
 * lack SSH terminal access to run initial migrations, link storage, and
 * optimize Laravel production caches directly from a secured web interface.
 *
 * SECURITY NOTICE:
 * - This tool is disabled if storage/installed.lock exists.
 * - Requires authentication via INSTALL_HELPER_KEY specified in your .env file.
 * - Always click "Lock & Remove Helper" once your initial setup is complete.
 *
 * Copyright (c) ICT Innovations <https://www.ictinnovations.com>
 * Part of ICTHospital <https://www.icthospital.com>
 * Licensed under the GNU General Public License v3.0.
 */

declare(strict_types=1);

session_start();

$baseDir = dirname(__DIR__);
$lockFile = $baseDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'installed.lock';

// 1. Check if already locked
if (file_exists($lockFile)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Setup Locked - ICTHospital</title><style>body{font-family:system-ui,-apple-system,sans-serif;background:#f8fafc;color:#1e293b;padding:48px 24px;text-align:center;} .box{max-width:560px;margin:40px auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:32px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);} h1{font-size:24px;margin-bottom:12px;color:#0f172a;} p{font-size:15px;line-height:1.6;color:#475569;} a{color:#2563eb;text-decoration:none;font-weight:600;}</style></head><body><div class="box"><h1>Setup Locked</h1><p>The installation helper has already been locked for security. To re-enable it, remove <code>storage/installed.lock</code> via your hosting File Manager.</p><p><a href="/">&larr; Return to Hospital Portal</a></p></div></body></html>';
    exit;
}

// 2. Load .env variables manually to avoid early booting issues
$envFile = $baseDir . DIRECTORY_SEPARATOR . '.env';
$envVars = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            $envVars[$key] = $val;
        }
    }
}

// Determine secret key from .env or session
$expectedKey = $envVars['INSTALL_HELPER_KEY'] ?? '';

// Check query param or post
$providedKey = $_POST['key'] ?? $_GET['key'] ?? $_SESSION['install_helper_auth'] ?? '';

$isAuthenticated = false;
if (!empty($expectedKey) && hash_equals($expectedKey, (string)$providedKey)) {
    $isAuthenticated = true;
    $_SESSION['install_helper_auth'] = $providedKey;
}

// Handle self-lock / delete action
$message = '';
$messageType = '';
$actionOutput = '';

if ($isAuthenticated && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Autoload Laravel framework for Artisan tasks
    if (in_array($action, ['migrate', 'storage_link', 'optimize', 'db_test'], true)) {
        if (!file_exists($baseDir . '/vendor/autoload.php')) {
            $message = 'Composer vendor directory not found! Please run composer install or upload the complete vendor folder.';
            $messageType = 'error';
        } else {
            require_once $baseDir . '/vendor/autoload.php';
            $app = require_once $baseDir . '/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
        }
    }

    if ($action === 'db_test') {
        try {
            Illuminate\Support\Facades\DB::connection()->getPdo();
            $dbName = Illuminate\Support\Facades\DB::connection()->getDatabaseName();
            $message = "Database connection successful! Connected to: {$dbName}";
            $messageType = 'success';
        } catch (\Throwable $e) {
            $message = 'Database connection failed: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'migrate') {
        try {
            $exitCode = Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $actionOutput = Illuminate\Support\Facades\Artisan::output();

            // Generate passport encryption keys natively via OpenSSL if missing
            $privKeyFile = $baseDir . '/storage/oauth-private.key';
            $pubKeyFile = $baseDir . '/storage/oauth-public.key';
            if (!file_exists($privKeyFile) || !file_exists($pubKeyFile)) {
                try {
                    $res = @openssl_pkey_new([
                        'private_key_bits' => 4096,
                        'private_key_type' => OPENSSL_KEYTYPE_RSA,
                    ]);
                    if ($res) {
                        @openssl_pkey_export($res, $privKey);
                        $details = @openssl_pkey_get_details($res);
                        $pubKey = $details['key'] ?? '';
                        if (!empty($privKey) && !empty($pubKey)) {
                            @file_put_contents($privKeyFile, $privKey);
                            @file_put_contents($pubKeyFile, $pubKey);
                            @chmod($privKeyFile, 0600);
                            @chmod($pubKeyFile, 0600);
                            $actionOutput .= "\nOAuth encryption keys generated successfully.";
                        }
                    }
                } catch (\Throwable $keyEx) {
                    // Non-fatal fallback
                }
            }

            $message = $exitCode === 0 ? 'Migrations executed successfully!' : 'Migration finished with exit code: ' . $exitCode;
            $messageType = $exitCode === 0 ? 'success' : 'warning';
        } catch (\Throwable $e) {
            $message = 'Migration failed: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'storage_link') {
        try {
            $publicStorage = $baseDir . '/public/storage';
            $target = $baseDir . '/storage/app/public';
            if (!file_exists($target)) {
                mkdir($target, 0755, true);
            }
            if (file_exists($publicStorage) || is_link($publicStorage)) {
                @unlink($publicStorage);
            }
            if (@symlink($target, $publicStorage)) {
                $message = 'Storage symlink created successfully via symlink()!';
                $messageType = 'success';
            } else {
                // Fallback to Artisan
                $exitCode = Illuminate\Support\Facades\Artisan::call('storage:link');
                $actionOutput = Illuminate\Support\Facades\Artisan::output();
                $message = 'Storage link command executed: ' . ($exitCode === 0 ? 'Success' : 'Check output');
                $messageType = $exitCode === 0 ? 'success' : 'warning';
            }
        } catch (\Throwable $e) {
            $message = 'Storage link failed: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'optimize') {
        try {
            Illuminate\Support\Facades\Artisan::call('optimize:clear');
            $clearOut = Illuminate\Support\Facades\Artisan::output();
            Illuminate\Support\Facades\Artisan::call('config:cache');
            $configOut = Illuminate\Support\Facades\Artisan::output();
            Illuminate\Support\Facades\Artisan::call('route:cache');
            $routeOut = Illuminate\Support\Facades\Artisan::output();
            Illuminate\Support\Facades\Artisan::call('view:cache');
            $viewOut = Illuminate\Support\Facades\Artisan::output();

            $actionOutput = trim($clearOut . "\n" . $configOut . "\n" . $routeOut . "\n" . $viewOut);
            $message = 'Application caches generated successfully!';
            $messageType = 'success';
        } catch (\Throwable $e) {
            $message = 'Optimization failed: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'lock') {
        file_put_contents($lockFile, 'Installed and locked at ' . date('c'));
        if (isset($_POST['delete_file']) && $_POST['delete_file'] === '1') {
            @unlink(__FILE__);
            header('Location: /');
            exit;
        }
        $message = 'Setup successfully locked. install-helper.php is now permanently disabled.';
        $messageType = 'success';
        header('Location: /install-helper.php');
        exit;
    }
}

// System Environment Checks
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '8.2.0', '>=');
$requiredExtensions = ['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'bcmath', 'gd', 'xml'];
$extensionStatus = [];
foreach ($requiredExtensions as $ext) {
    $extensionStatus[$ext] = extension_loaded($ext);
}

$storageWritable = is_writable($baseDir . '/storage');
$bootstrapWritable = is_writable($baseDir . '/bootstrap/cache');
$envExists = file_exists($envFile);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Hosting Setup Helper - ICTHospital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary: #475569;
            --neutral-bg: #f8fafc;
            --neutral-card: #ffffff;
            --neutral-border: #e2e8f0;
            --neutral-dark: #0f172a;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #d97706;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--neutral-bg);
            color: var(--neutral-dark);
            line-height: 1.5;
            padding: 32px 16px;
        }
        .container {
            max-width: 760px;
            margin: 0 auto;
        }
        .header {
            margin-bottom: 24px;
            text-align: center;
        }
        .header h1 {
            font-size: 26px;
            font-weight: 700;
            color: var(--neutral-dark);
            letter-spacing: -0.02em;
        }
        .header p {
            color: var(--secondary);
            font-size: 15px;
            margin-top: 6px;
        }
        .card {
            background: var(--neutral-card);
            border: 1px solid var(--neutral-border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--neutral-dark);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 6px;
        }
        .badge-success { background: #dcfce7; color: var(--success); }
        .badge-danger { background: #fee2e2; color: var(--danger); }
        .badge-warning { background: #fef3c7; color: var(--warning); }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; }
        .alert-error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-warning { background: #fef3c7; border: 1px solid #fde68a; color: #92400e; }
        .check-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        @media (max-width: 600px) {
            .check-grid { grid-template-columns: 1fr; }
        }
        .check-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            background: var(--neutral-bg);
            border: 1px solid var(--neutral-border);
            border-radius: 8px;
            font-size: 14px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }
        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }
        .btn-secondary {
            background-color: #ffffff;
            color: var(--secondary);
            border-color: var(--neutral-border);
        }
        .btn-secondary:hover {
            background-color: var(--neutral-bg);
            color: var(--neutral-dark);
        }
        .btn-danger {
            background-color: var(--danger);
            color: #ffffff;
        }
        .btn-danger:hover {
            background-color: #b91c1c;
        }
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--neutral-dark);
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            font-size: 14px;
            border: 1px solid var(--neutral-border);
            border-radius: 8px;
            outline: none;
            background: #ffffff;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        pre.terminal {
            background: #0f172a;
            color: #f8fafc;
            padding: 14px;
            border-radius: 8px;
            font-size: 13px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            overflow-x: auto;
            white-space: pre-wrap;
            margin-top: 12px;
        }
        .auth-box {
            text-align: center;
            padding: 32px 16px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>ICTHospital Setup Helper</h1>
        <p>Turnkey Database Migration & Shared Hosting Environment Manager</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if (!$isAuthenticated): ?>
        <div class="card auth-box">
            <h2 class="card-title" style="justify-content:center;">Authentication Required</h2>
            <p style="color:var(--secondary);font-size:14px;margin-bottom:20px;">
                For security on shared hosting, please enter the secret key specified in your <code>.env</code> file under <code>INSTALL_HELPER_KEY</code>.
            </p>
            <?php if (empty($expectedKey)): ?>
                <div class="alert alert-warning" style="text-align:left;">
                    <strong>Setup Notice:</strong> <code>INSTALL_HELPER_KEY</code> is currently empty in your <code>.env</code> file.<br>
                    Please open <code>.env</code> via your Hostinger/cPanel File Manager, add a random key such as:<br>
                    <code>INSTALL_HELPER_KEY=secret_setup_token_<?= bin2hex(random_bytes(6)) ?></code><br>
                    and refresh this page.
                </div>
            <?php endif; ?>
            <form method="POST" action="/install-helper.php" style="max-width:380px;margin:0 auto;text-align:left;">
                <div class="form-group">
                    <label for="key">Setup Secret Key</label>
                    <input type="password" name="key" id="key" class="form-control" placeholder="Enter INSTALL_HELPER_KEY" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Authenticate & Continue</button>
            </form>
        </div>
    <?php else: ?>

        <!-- Server Readiness Check -->
        <div class="card">
            <h2 class="card-title">1. Server Readiness & PHP Extensions</h2>
            <div class="check-grid">
                <div class="check-item">
                    <span>PHP Version (&ge; 8.2)</span>
                    <span class="badge <?= $phpOk ? 'badge-success' : 'badge-danger' ?>">
                        <?= htmlspecialchars($phpVersion) ?> <?= $phpOk ? '&#10003;' : '&#10007;' ?>
                    </span>
                </div>
                <div class="check-item">
                    <span>.env Config File</span>
                    <span class="badge <?= $envExists ? 'badge-success' : 'badge-danger' ?>">
                        <?= $envExists ? 'Found' : 'Missing' ?>
                    </span>
                </div>
                <div class="check-item">
                    <span>Storage Directory Writable</span>
                    <span class="badge <?= $storageWritable ? 'badge-success' : 'badge-danger' ?>">
                        <?= $storageWritable ? 'Writable' : 'Read-Only' ?>
                    </span>
                </div>
                <div class="check-item">
                    <span>Bootstrap Cache Writable</span>
                    <span class="badge <?= $bootstrapWritable ? 'badge-success' : 'badge-danger' ?>">
                        <?= $bootstrapWritable ? 'Writable' : 'Read-Only' ?>
                    </span>
                </div>
                <?php foreach ($extensionStatus as $ext => $loaded): ?>
                    <div class="check-item">
                        <span>Extension: <code><?= htmlspecialchars($ext) ?></code></span>
                        <span class="badge <?= $loaded ? 'badge-success' : 'badge-danger' ?>">
                            <?= $loaded ? 'Enabled' : 'Missing' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Setup Actions -->
        <div class="card">
            <h2 class="card-title">2. Installation & Optimization Actions</h2>
            <p style="color:var(--secondary);font-size:14px;margin-bottom:16px;">
                Execute the standard Laravel commands needed to initialize ICTHospital without SSH:
            </p>

            <div class="actions-grid">
                <form method="POST" action="/install-helper.php">
                    <input type="hidden" name="action" value="db_test">
                    <button type="submit" class="btn btn-secondary" style="width:100%;">
                        Test DB Connection
                    </button>
                </form>

                <form method="POST" action="/install-helper.php">
                    <input type="hidden" name="action" value="migrate">
                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        Run Migrations (--force)
                    </button>
                </form>

                <form method="POST" action="/install-helper.php">
                    <input type="hidden" name="action" value="storage_link">
                    <button type="submit" class="btn btn-secondary" style="width:100%;">
                        Link Public Storage
                    </button>
                </form>

                <form method="POST" action="/install-helper.php">
                    <input type="hidden" name="action" value="optimize">
                    <button type="submit" class="btn btn-secondary" style="width:100%;">
                        Cache & Optimize
                    </button>
                </form>
            </div>

            <?php if (!empty($actionOutput)): ?>
                <h3 style="font-size:14px;font-weight:600;margin-top:20px;color:var(--neutral-dark);">Artisan Output:</h3>
                <pre class="terminal"><?= htmlspecialchars($actionOutput) ?></pre>
            <?php endif; ?>
        </div>

        <!-- Security Lockout -->
        <div class="card" style="border-color:#fecaca;">
            <h2 class="card-title" style="color:var(--danger);">3. Security Lockout (Crucial)</h2>
            <p style="color:var(--secondary);font-size:14px;margin-bottom:16px;">
                Once your database is migrated and storage is linked, lock this helper to permanently protect your application from unauthorized access.
            </p>
            <form method="POST" action="/install-helper.php">
                <input type="hidden" name="action" value="lock">
                <div class="form-group" style="margin-bottom:16px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:400;">
                        <input type="checkbox" name="delete_file" value="1" checked>
                        <span>Also delete <code>public/install-helper.php</code> from disk immediately</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Lock installation helper and proceed to hospital login?');">
                    Lock Setup & Return to Hospital Portal
                </button>
            </form>
        </div>

    <?php endif; ?>
</div>

</body>
</html>
