<?php

function get_school_announcements(PDO $pdo, int $limit = 5): array
{
    $limit = max(1, min($limit, 20));
    $stmt = $pdo->prepare(
        'SELECT title, body, created_at
         FROM announcements
         WHERE is_published = 1
         ORDER BY created_at DESC, id DESC
         LIMIT ' . $limit
    );
    $stmt->execute();

    return $stmt->fetchAll();
}
