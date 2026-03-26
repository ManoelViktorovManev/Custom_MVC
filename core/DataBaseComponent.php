<?php

namespace App\Core;

/**
 * Database connection manager using the Singleton pattern.
 *
 * This class is responsible for establishing and maintaining a single
 * PDO database connection throughout the lifecycle of the application.
 * Database credentials are loaded from the `.env` file using the
 * `DATABASE_URL` configuration value.
 *
 * Responsibilities:
 * - Parse database configuration from environment file
 * - Create the database if it does not exist
 * - Initialize and expose a PDO connection
 * - Ensure a single shared database connection instance
 *
 * This class should not be instantiated directly. Use
 * {@see DataBaseComponent::getInstance()} instead.
 *
 * @since 2.0
 */
class DataBaseComponent
{
    private $db;
    private static $instance;

    /**
     * Private constructor.
     *
     * Prevents direct instantiation and initializes the database
     * connection using environment configuration.
     * @since 2.0
     */
    private function __construct()
    {
        $this->setDB();
    }

    /**
     * Provides a singleton instance of DataBaseComponent.
     *
     * Ensures that only one instance of DataBaseComponent is created and reused throughout the application.
     *
     * @return DataBaseComponent The singleton instance of DataBaseComponent.
     * @since 2.0
     */
    public static function getInstance(): DataBaseComponent
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initializes the PDO database connection using configuration from an environment file.
     *
     * Parses the `.env` file for database connection details, attempts to connect, creates
     * the database if it does not exist, and sets the database context to the specified database.
     * On connection errors, it outputs an error message.
     *
     * @return void
     * @since 2.0
     */
    private function setDB()
    {
        $envFile = parse_ini_file('.env');
        $dbInfo = $envFile['DATABASE_URL'];
        $parts = parse_url($dbInfo);
        $schema = $parts['scheme'];
        $host = $parts['host'];
        $port = $parts['port'];
        $user = $parts['user'];
        $pass = $parts['pass'];
        $dbName = ltrim($parts['path'], "/");
        try {
            $dsn = "{$schema}:host={$host};port={$port}";

            $pdo = new \PDO($dsn, $user, $pass);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}`");
            $pdo->exec("USE `{$dbName}`");

            $this->db = $pdo;
        } catch (\Exception $e) {
            echo "<h1>" . ($e->getMessage()) . "</h1>";
        }
    }

    /**
     * Retrieves the current database connection.
     *
     * @return \PDO The PDO instance representing the database connection.
     * @since 2.0
     */
    public function getDB()
    {
        return $this->db;
    }
}
