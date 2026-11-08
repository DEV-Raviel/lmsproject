<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('admin');
$pdo = Database::connect();
$stmt = $pdo->prepare(
    'SELECT id, user_code, full_name, email, role
     FROM users
     WHERE id <> ?
     ORDER BY role, full_name'
);
$stmt->execute([$user['id']]);
$accounts = $stmt->fetchAll();
$flash = $_SESSION['recovery_flash'] ?? null;
unset($_SESSION['recovery_flash']);

$basePath = '../';
$pageTitle = 'Account Recovery';
$activePage = 'recovery';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <p class="eyebrow">ACCOUNT ADMINISTRATION</p>
        <h2>Account Recovery</h2>
        <p>Generate a one-time password reset link for an account. Links expire after 30 minutes.</p>
    </div>
    <?php if ($flash): ?>
        <p class="<?= !empty($flash['error']) ? 'notice error' : 'notice' ?>" role="status"><?= e($flash['message']) ?></p>
        <?php if (!empty($flash['reset_link'])): ?>
            <section class="recovery-link-card" aria-labelledby="recovery-link-title">
                <h3 id="recovery-link-title">Copy this reset link now</h3>
                <p>It is shown only once here. Share it directly with <?= e($flash['account_name']) ?>.</p>
                <label class="field">
                    <span>Password reset link</span>
                    <input type="url" readonly value="<?= e($flash['reset_link']) ?>" onclick="this.select()">
                </label>
                <a class="btn" href="<?= e($flash['reset_link']) ?>" target="_blank" rel="noopener noreferrer">Open reset page</a>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <section class="account-recovery-list" aria-label="Accounts">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Account</th><th>Email</th><th>Role</th><th>Recovery action</th></tr></thead>
                <tbody>
                <?php foreach ($accounts as $account): ?>
                    <tr>
                        <td><strong><?= e($account['full_name']) ?></strong><br><span class="muted"><?= e($account['user_code']) ?></span></td>
                        <td><?= e($account['email']) ?></td>
                        <td><?= e(ucfirst($account['role'])) ?></td>
                        <td>
                            <form method="post" action="../actions/account_recovery_action.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $account['id'] ?>">
                                <button class="btn btn-outline btn-sm" type="submit">Generate reset link</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$accounts): ?><tr><td colspan="4">No other accounts are available for recovery.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
