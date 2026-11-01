<?php
// config/db.php
class Database {
    // Default XAMPP MySQL credentials
    private static $host = 'localhost';
    private static $db   = 'lms_db';
    private static $user = 'root';
    private static $pass = '';

    public static function connect() {
        try {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=utf8mb4";
            $pdo = new PDO($dsn, self::$user, self::$pass);

            // Set PDO to throw exceptions on errors
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $pdo;
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}