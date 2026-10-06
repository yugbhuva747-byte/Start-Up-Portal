<?php
/**
 * Database Singleton Connection Handler
 * High-reliability multi-tier connection with automatic live hosting fallbacks
 */

class Database {
    private static ?PDO $instance = null;
    private static ?string $lastError = null;

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
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 4,
            ];

            // Build prioritized connection strategies
            $strategies = [];

            // 1. Primary Strategy: Configured Host & Port from .env
            $primaryPort = (string)DB_PORT;
            $strategies[] = [
                'dsn' => "mysql:host=" . DB_HOST . ";port=" . $primaryPort . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                'user' => DB_USER,
                'pass' => DB_PASS,
                'desc' => "Primary Host (" . DB_HOST . ":" . $primaryPort . ")"
            ];

            // 2. Fallback for Live Deployments: If custom port (like 3307) fails on live hosting, try standard 3306
            if ($primaryPort !== '3306') {
                $strategies[] = [
                    'dsn' => "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=utf8mb4",
                    'user' => DB_USER,
                    'pass' => DB_PASS,
                    'desc' => "Standard Port 3306 (" . DB_HOST . ":3306)"
                ];
            }

            // 3. Fallback for cPanel / Linux Live Servers: 'localhost' invokes native Unix domain socket
            if (DB_HOST === '127.0.0.1') {
                $strategies[] = [
                    'dsn' => "mysql:host=localhost;dbname=" . DB_NAME . ";charset=utf8mb4",
                    'user' => DB_USER,
                    'pass' => DB_PASS,
                    'desc' => "Localhost Socket (cPanel standard)"
                ];
            }

            // 4. Fallback for explicit or discovered Unix socket files on Linux shared hosting
            if (defined('DB_SOCKET') && !empty(DB_SOCKET)) {
                $strategies[] = [
                    'dsn' => "mysql:unix_socket=" . DB_SOCKET . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    'user' => DB_USER,
                    'pass' => DB_PASS,
                    'desc' => "Explicit Unix Socket"
                ];
            } else {
                $knownSockets = ['/var/lib/mysql/mysql.sock', '/tmp/mysql.sock', '/run/mysqld/mysqld.sock'];
                foreach ($knownSockets as $sock) {
                    if (@file_exists($sock)) {
                        $strategies[] = [
                            'dsn' => "mysql:unix_socket=" . $sock . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                            'user' => DB_USER,
                            'pass' => DB_PASS,
                            'desc' => "Autodetected Socket ($sock)"
                        ];
                        break;
                    }
                }
            }

            // Execute connection attempts in sequence
            foreach ($strategies as $strat) {
                try {
                    self::$instance = new PDO($strat['dsn'], $strat['user'], $strat['pass'], $options);
                    self::$lastError = null;
                    return self::$instance;
                } catch (PDOException $e) {
                    self::$lastError = $e->getMessage();
                    error_log("Database connection attempt failed ({$strat['desc']}): " . $e->getMessage());
                }
            }
            return self::$instance;
        }
        return self::$instance;
    }

    public static function getLastError(): ?string {
        return self::$lastError;
    }
}

// Global helper for PDO
function get_db(): ?PDO {
    return Database::getConnection();
}
