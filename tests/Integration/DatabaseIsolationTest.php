<?php

use PHPUnit\Framework\TestCase;

final class DatabaseIsolationTest extends TestCase
{
    public function testDatabaseConnectionTargetsTheDedicatedTestSchema(): void
    {
        $databaseName = getenv('LMS_DB_NAME') ?: '';
        self::assertStringEndsWith('_test', $databaseName);

        $pdo = Database::connect();
        self::assertSame($databaseName, $pdo->query('SELECT DATABASE()')->fetchColumn());

        $tables = $pdo->query(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()'
        )->fetchAll(PDO::FETCH_COLUMN);
        $requiredTables = [
            'users',
            'classes',
            'class_enrollments',
            'assignments',
            'submissions',
            'quizzes',
            'student_profiles',
            'calendar_events',
        ];
        foreach ($requiredTables as $table) {
            self::assertContains($table, $tables);
        }

        $contactColumn = $pdo->query(
            "SELECT column_name FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'facebook_url'"
        )->fetchColumn();
        self::assertSame('facebook_url', $contactColumn);
        $photoColumn = $pdo->query(
            "SELECT column_name FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'profile_photo_path'"
        )->fetchColumn();
        self::assertSame('profile_photo_path', $photoColumn);
    }
}
