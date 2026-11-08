<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If user is already logged in, redirect them directly to their respective dashboard
if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? '';
    match ($role) {
        'admin' => header('Location: admin/dashboard.php'),
        'dean' => header('Location: admin/manage_announcements.php'),
        'teacher' => header('Location: teacher/dashboard.php'),
        'student' => header('Location: student/dashboard.php'),
        default => null,
    };
    if (in_array($role, ['admin', 'dean', 'teacher', 'student'], true)) {
        exit;
    }
}

// Handle alert messages from query params
$errorMessage = '';
if (isset($_GET['error'])) {
    $errorMessage = match ($_GET['error']) {
        'invalid_credentials' => 'Invalid User ID / Email or Password.',
        'missing_fields'     => 'Please fill in all required fields.',
        'rate_limited'       => 'Too many unsuccessful attempts. Wait 15 minutes before trying again.',
        'csrf_expired'       => 'Your sign-in page expired. Reload it and try again.',
        default               => 'An error occurred during sign in.',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome back - e-GURO LMS</title>
    <style>
        :root {
            /* e-GURO LMS Light Theme Colors */
            --bg-body: #f3f6fb;
            --bg-main: #f3f6fb;
            --bg-card: #ffffff;
            --bg-input: #ffffff;
            --border: #dce5f0;
            --border-color: #dce5f0;
            --border-focus: #315d91;
            --text-primary: #1d3554;
            --text-secondary: #647991;
            --accent: #173b70;
            --accent-hover: #102b53;
            --accent-text: #ffffff;
            --logo-bg: #102b53;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--bg-main);
            /* Subtle dot grid pattern using the global border color */
            background-image: radial-gradient(var(--border) 1px, transparent 0);
            background-size: 24px 24px;
        }

        .login-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 12px 32px rgba(16, 43, 83, 0.1);
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-placeholder {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 48px;
            height: 48px;
            background: var(--logo-bg);
            border-radius: 12px;
            margin-bottom: 16px;
            font-weight: 800;
            font-size: 20px;
            color: white;
            letter-spacing: -1px;
            box-shadow: 0 4px 8px rgba(16, 43, 83, 0.16);
        }

        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--logo-bg);
            letter-spacing: -0.02em;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--logo-bg);
        }

        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            background-color: var(--bg-input);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 14px 16px;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-group input:focus {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(49, 93, 145, 0.15);
        }

        .form-group input::placeholder {
            color: #94a3b8;
        }

        .btn-submit {
            width: 100%;
            background-color: var(--accent);
            color: var(--accent-text);
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-submit:hover {
            background-color: var(--accent-hover);
        }

        .alert-error {
            background: #edf3fa;
            color: #102b53;
            border-left: 4px solid #5478a5;
            padding: 12px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 24px;
            text-align: left;
        }

    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <div class="logo-placeholder">eG</div>
            <h1>Welcome back</h1>
            <p>Enter your details to access your portal.</p>
        </div>

        <div id="alertBox">
            <?php if (!empty($errorMessage)): ?>
                <div class="alert-error">
                    <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </div>

        <form action="actions/auth_action.php" method="POST" id="loginForm">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="loginIdentifier">Email Address or User ID</label>
                <input type="text" id="loginIdentifier" name="identifier" required placeholder="name@eguro.edu.ph">
            </div>

            <div class="form-group">
                <label for="loginPassword">Password</label>
                <input type="password" id="loginPassword" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-submit">Sign In</button>
        </form>
    </div>
</body>
</html>