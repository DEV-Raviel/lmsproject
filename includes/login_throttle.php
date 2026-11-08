<?php

const LOGIN_THROTTLE_MAX_FAILURES = 5;

function login_throttle_bucket_keys(string $identifier, string $ipAddress): array
{
    $normalizedIdentifier = strtolower(trim($identifier));
    $normalizedIp = filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : 'unknown';

    return [
        hash('sha256', 'identifier:' . $normalizedIdentifier),
        hash('sha256', 'ip:' . $normalizedIp),
    ];
}

function login_throttle_is_locked(PDO $pdo, array $bucketKeys): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM login_attempts
         WHERE bucket_key IN (?, ?) AND locked_until > NOW()
         LIMIT 1'
    );
    $stmt->execute($bucketKeys);
    return (bool) $stmt->fetchColumn();
}

function login_throttle_record_failure(PDO $pdo, array $bucketKeys): void
{
    sort($bucketKeys);
    try {
        $pdo->beginTransaction();
        $insert = $pdo->prepare(
            'INSERT IGNORE INTO login_attempts (bucket_key, failure_count, window_started_at)
             VALUES (?, 0, NOW())'
        );
        $update = $pdo->prepare(
            'UPDATE login_attempts
             SET failure_count = IF(
                    window_started_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE),
                    1,
                    failure_count + 1
                 ),
                 window_started_at = IF(
                    window_started_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE),
                    NOW(),
                    window_started_at
                 ),
                 locked_until = IF(
                    IF(window_started_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE), 1, failure_count + 1) >= ?,
                    DATE_ADD(NOW(), INTERVAL 15 MINUTE),
                    NULL
                 )
             WHERE bucket_key = ?'
        );

        foreach ($bucketKeys as $key) {
            $insert->execute([$key]);
            $update->execute([LOGIN_THROTTLE_MAX_FAILURES, $key]);
        }
        $pdo->commit();
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}

function login_throttle_clear(PDO $pdo, array $bucketKeys): void
{
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE bucket_key IN (?, ?)');
    $stmt->execute($bucketKeys);
}
