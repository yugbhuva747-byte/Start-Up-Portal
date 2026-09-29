<?php
/**
 * Database Singleton Connection Handler
 */

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): ?PDO {
        // Check if existing connection is still responsive
        if (self::$instance !== null) {
            try {
                self::$instance->query('SELECT 1');
            } catch (\Throwable $e) {
                // Server went away or connection dropped -> reset to reconnect
                self::$instance = null;
            }
        }

        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5,
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // If DB doesn't exist yet, we can catch it or allow setup.php to initialize
                error_log("Database connection error: " . $e->getMessage());
                return null;
            }
        }
        return self::$instance;
    }
}

// Global helper for PDO
function get_db(): ?PDO {
    return Database::getConnection();
}
