<?php

require_once __DIR__ . '/../../src/Database.php';

class Truncate
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }
    
    /**
     * Truncate multiple database tables safely.
     *
     * @param array $tables List of table names to truncate.
     * @return bool True on success, false on failure.
     */
    public function truncateTables(array $tables)
    {
        try {
            // Use the PDO instance already stored in your Database object
            $pdo = $this->db->pdo;

            // 1. Disable foreign key checks to allow truncating related tables
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            foreach ($tables as $table) {
                // Sanitize table name with backticks to prevent SQL errors
                $pdo->exec("TRUNCATE TABLE `$table`");
            }

            // 2. Re-enable foreign key checks
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

            return true;
        } catch (Exception $e) {
            // Log error using your Logger class
            Logger::log("Truncate Failed: " . $e->getMessage());
            return false;
        }
    }
}
