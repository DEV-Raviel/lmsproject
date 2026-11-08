<?php
// includes/functions.php - small helpers shared by every role

/** Escape any value for safe HTML output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_valid_facebook_url(string $url): bool
{
    $parts = parse_url($url);
    if (!is_array($parts)
        || strtolower($parts['scheme'] ?? '') !== 'https'
        || empty($parts['host'])
        || !isset($parts['path'])
        || trim($parts['path'], '/') === ''
        || isset($parts['user'])
        || isset($parts['pass'])
        || (isset($parts['port']) && $parts['port'] !== 443)) {
        return false;
    }

    $host = strtolower(rtrim($parts['host'], '.'));
    return $host === 'facebook.com'
        || $host === 'fb.com'
        || str_ends_with($host, '.facebook.com');
}

function profile_photo_src(
    ?string $path,
    int $userId,
    string $basePath = '',
    ?int $classId = null
): ?string
{
    if (!is_valid_profile_photo_path($path) || $userId < 1) {
        return null;
    }

    $query = ['id' => $userId];
    if ($classId !== null && $classId > 0) {
        $query['class_id'] = $classId;
    }

    return $basePath . 'student/profile-photo.php?' . http_build_query($query);
}

function is_valid_profile_photo_path(?string $path): bool
{
    return $path !== null
        && preg_match('#\Auploads/profile_photos/[a-f0-9]{32}\.jpg\z#', $path) === 1;
}

function profile_initials(string $name): string
{
    $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= mb_strtoupper(mb_substr($word, 0, 1));
    }

    return $initials !== '' ? $initials : 'U';
}

/** "9 days ago", "3 months ago" ... */
function time_ago(string $datetime): string
{
    $seconds = max(0, time() - strtotime($datetime));
    $units = [2592000 => 'month', 86400 => 'day', 3600 => 'hour', 60 => 'minute'];
    foreach ($units as $size => $name) {
        if ($seconds >= $size) {
            $n = (int) floor($seconds / $size);
            return $n . ' ' . $name . ($n > 1 ? 's' : '') . ' ago';
        }
    }
    return 'just now';
}

function abort_404(string $message = 'Page not found.'): void
{
    http_response_code(404);
    exit(e($message));
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_token_is_valid(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf'])
        && is_string($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

/**
 * Print a table. $rows is a list of rows; each cell is plain text
 * or ['href' => '...', 'text' => '...'] for a link.
 */
function render_table(array $headers, array $rows, string $empty = 'Nothing to show yet.'): void
{
    if (!$rows) {
        echo '<p class="empty">' . e($empty) . '</p>';
        return;
    }
    echo '<div class="table-responsive" role="region" aria-label="Scrollable results" tabindex="0">';
    echo '<table class="data-table"><thead><tr>';
    foreach ($headers as $h) {
        echo '<th>' . e($h) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            if (is_array($cell) && isset($cell['href'])) {
                echo '<td><a href="' . e($cell['href']) . '">' . e($cell['text']) . '</a></td>';
            } else {
                echo '<td>' . e($cell) . '</td>';
            }
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

/** Print a label/value table: render_details(['Full Name' => 'Ana', ...]) */
function render_details(array $pairs): void
{
    echo '<table class="data-table details"><tbody>';
    foreach ($pairs as $label => $value) {
        echo '<tr><th>' . e($label) . '</th><td>' . e($value) . '</td></tr>';
    }
    echo '</tbody></table>';
}
