<?php

class Database
{
    public static function connect(): PDO
    {
        $host = getenv('LMS_DB_HOST') ?: 'localhost';
        $database = getenv('LMS_DB_NAME') ?: 'eguro_lms';
        $username = getenv('LMS_DB_USER') ?: 'root';
        $password = getenv('LMS_DB_PASSWORD') ?: '';

        try {
            $pdo = new PDO(
                "mysql:host=$host;dbname=$database;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            return $pdo;
        } catch (PDOException $exception) {
            error_log('LMS database connection failed: ' . $exception->getMessage());
            http_response_code(503);
            exit('The service is temporarily unavailable. Please try again later.');
        }
    }
}